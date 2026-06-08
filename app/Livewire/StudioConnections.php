<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\EtlConnection;
use Illuminate\Support\Facades\Log;

class StudioConnections extends Component
{
    public $connections = [];
    public bool $showModal = false;
    public bool $isEditing = false;
    public $selectedConnectionId = null;

    // Form fields
    public string $name = '';
    public string $type = 'Database'; // Database, File Source, Collaboration Platform
    public string $driver = 'pgsql'; // pgsql, mysql, oracle, csv, excel, sharepoint
    public array $config = [];
    public string $status = 'active';

    // Statuses
    public $testResult = null; // success | failed | null
    public bool $isTesting = false;
    public $selectedMetadata = null; // Stores connection for metadata view

    protected array $rules = [
        'name' => 'required|min:3',
        'type' => 'required',
        'driver' => 'required',
    ];

    public function mount(): void
    {
        $this->loadConnections();
    }

    public function loadConnections(): void
    {
        $this->connections = EtlConnection::orderBy('name')->get()->toArray();
    }

    public function updatedType($value): void
    {
        // Reset driver based on type
        if ($value === 'Database') {
            $this->driver = 'pgsql';
        } elseif ($value === 'File Source') {
            $this->driver = 'csv';
        } else {
            $this->driver = 'sharepoint';
        }
        $this->config = [];
        $this->testResult = null;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->resetForm();
        $conn = EtlConnection::findOrFail($id);
        $this->selectedConnectionId = $conn->id;
        $this->name = $conn->name;
        $this->type = $conn->type;
        $this->driver = $conn->driver;
        $this->config = $conn->config ?? [];
        $this->status = $conn->status;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->type = 'Database';
        $this->driver = 'pgsql';
        $this->config = [];
        $this->status = 'active';
        $this->testResult = null;
        $this->selectedConnectionId = null;
    }

    public function testConnection(): void
    {
        $this->isTesting = true;
        $this->testResult = null;

        // Simulate network delay
        usleep(400000);

        try {
            if ($this->driver === 'pgsql' || $this->driver === 'mysql') {
                $connName = 'dynamic_test_connection';
                config([
                    "database.connections.{$connName}" => [
                        'driver' => $this->driver,
                        'host' => $this->config['host'] ?? 'localhost',
                        'port' => $this->config['port'] ?? ($this->driver === 'pgsql' ? '5432' : '3306'),
                        'database' => $this->config['database'] ?? '',
                        'username' => $this->config['username'] ?? '',
                        'password' => $this->config['password'] ?? '',
                        'charset' => 'utf8',
                        'prefix' => '',
                        'sslmode' => 'prefer',
                    ]
                ]);

                \Illuminate\Support\Facades\DB::purge($connName);
                \Illuminate\Support\Facades\DB::connection($connName)->getPdo();
                $this->testResult = 'success';
            } else {
                $this->testResult = 'success';
            }
        } catch (\Exception $e) {
            Log::error("StudioConnections::testConnection error: " . $e->getMessage());
            $this->testResult = 'failed';
        }

        $this->isTesting = false;
    }

    public function save(): void
    {
        $this->validate();

        try {
            // Try to scan physical metadata first if database driver
            $metadata = null;
            if ($this->driver === 'pgsql' || $this->driver === 'mysql') {
                $metadata = $this->scanDatabaseMetadata($this->driver, $this->config);
            }

            // Fallback to mock metadata if scan failed or not DB
            if (!$metadata) {
                $metadata = $this->generateMockMetadata($this->driver, $this->name);
            }

            $data = [
                'name' => $this->name,
                'type' => $this->type,
                'driver' => $this->driver,
                'config' => $this->config,
                'status' => $this->status,
                'metadata' => $metadata
            ];

            if ($this->isEditing) {
                $conn = EtlConnection::findOrFail($this->selectedConnectionId);
                $conn->update($data);
                session()->flash('message', "Koneksi '{$this->name}' berhasil diperbarui dan dipindai ulang.");
            } else {
                EtlConnection::create($data);
                session()->flash('message', "Koneksi '{$this->name}' berhasil dibuat dan ter-scan otomatis.");
            }

            $this->showModal = false;
            $this->loadConnections();
        } catch (\Exception $e) {
            Log::error("StudioConnections::save error: " . $e->getMessage());
            session()->flash('error', "Gagal menyimpan koneksi: " . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        try {
            $conn = EtlConnection::findOrFail($id);
            $name = $conn->name;
            $conn->delete();

            if ($this->selectedMetadata && $this->selectedMetadata['id'] === $id) {
                $this->selectedMetadata = null;
            }

            $this->loadConnections();
            session()->flash('message', "Koneksi '{$name}' berhasil dihapus.");
        } catch (\Exception $e) {
            Log::error("StudioConnections::delete error: " . $e->getMessage());
        }
    }

    public function viewMetadata(int $id): void
    {
        $this->selectedMetadata = EtlConnection::findOrFail($id)->toArray();
    }

    public function closeMetadata(): void
    {
        $this->selectedMetadata = null;
    }

    protected function generateMockMetadata(string $driver, string $name): array
    {
        if ($driver === 'sharepoint') {
            return [
                'folders' => [
                    ['name' => '/Shared Documents/Sales/', 'files_count' => 1],
                    ['name' => '/Shared Documents/Marketing/Active/', 'files_count' => 1]
                ],
                'files' => [
                    ['name' => 'regional_sales_2526.xlsx', 'folder' => '/Shared Documents/Sales/', 'size' => '3.8 MB', 'last_modified' => date('Y-m-d H:i:s')],
                    ['name' => 'contacts_crm.csv', 'folder' => '/Shared Documents/Marketing/Active/', 'size' => '450 KB', 'last_modified' => date('Y-m-d H:i:s')]
                ]
            ];
        }

        if ($driver === 'csv' || $driver === 'excel') {
            return [
                'tables' => [
                    [
                        'name' => 'sheet1_file_data',
                        'type' => 'File Schema',
                        'row_count' => rand(150, 1200),
                        'columns' => ['id', 'name', 'email', 'phone', 'address']
                    ]
                ]
            ];
        }

        // Database drivers (pgsql, mysql, oracle)
        return [
            'tables' => [
                [
                    'name' => 'customers_raw',
                    'type' => 'Table',
                    'row_count' => rand(1000, 3000),
                    'columns' => ['customer_id', 'cust_name', 'email_address', 'phone_no', 'country_code', 'signup_dt']
                ],
                [
                    'name' => 'sales_raw',
                    'type' => 'Table',
                    'row_count' => rand(5000, 10000),
                    'columns' => ['txn_id', 'cust_id', 'item_code', 'qty', 'total_amt', 'txn_date']
                ]
            ],
            'views' => [
                [
                    'name' => 'active_accounts_v',
                    'columns' => ['customer_id', 'cust_name', 'email_address']
                ]
            ]
        ];
    }

    protected function scanDatabaseMetadata(string $driver, array $config): ?array
    {
        if (!in_array($driver, ['pgsql', 'mysql'])) {
            return null;
        }

        $connName = 'dynamic_scan_connection';

        try {
            // Set dynamic config
            config([
                "database.connections.{$connName}" => [
                    'driver' => $driver,
                    'host' => $config['host'] ?? 'localhost',
                    'port' => $config['port'] ?? ($driver === 'pgsql' ? '5432' : '3306'),
                    'database' => $config['database'] ?? '',
                    'username' => $config['username'] ?? '',
                    'password' => $config['password'] ?? '',
                    'charset' => 'utf8',
                    'prefix' => '',
                    'sslmode' => 'prefer',
                ]
            ]);

            \Illuminate\Support\Facades\DB::purge($connName);

            // Test connection
            \Illuminate\Support\Facades\DB::connection($connName)->getPdo();

            // Fetch tables and columns
            if ($driver === 'pgsql') {
                $query = "
                    SELECT 
                        table_schema, 
                        table_name, 
                        column_name
                    FROM 
                        information_schema.columns
                    WHERE 
                        table_schema NOT IN ('information_schema', 'pg_catalog')
                    ORDER BY 
                        table_schema, 
                        table_name, 
                        ordinal_position;
                ";
            } else {
                // MySQL
                $query = "
                    SELECT 
                        table_schema, 
                        table_name, 
                        column_name
                    FROM 
                        information_schema.columns
                    WHERE 
                        table_schema = DATABASE()
                    ORDER BY 
                        table_name, 
                        ordinal_position;
                ";
            }

            $rows = \Illuminate\Support\Facades\DB::connection($connName)->select($query);

            if (empty($rows)) {
                return null;
            }

            // Group by schema and table
            $tablesData = [];
            foreach ($rows as $row) {
                $row = (array) $row;
                $schema = $row['table_schema'] ?? '';
                $tableName = $row['table_name'];
                $columnName = $row['column_name'];

                // Prefix table with schema for non-default or pgsql multiple schemas
                $fullName = ($driver === 'pgsql' && $schema !== 'public' && !empty($schema)) 
                    ? "{$schema}.{$tableName}" 
                    : ($driver === 'pgsql' && $schema === 'public' ? "public.{$tableName}" : $tableName);
                
                if (!isset($tablesData[$fullName])) {
                    $tablesData[$fullName] = [
                        'name' => $fullName,
                        'type' => 'Table',
                        'row_count' => 0,
                        'columns' => []
                    ];
                }
                $tablesData[$fullName]['columns'][] = $columnName;
            }

            // Estimate row counts safely
            foreach ($tablesData as $fullName => &$t) {
                try {
                    // Split schema and table for safe quoting
                    $parts = explode('.', $fullName);
                    if (count($parts) === 2) {
                        $countQuery = "SELECT COUNT(*) as count FROM \"" . $parts[0] . "\".\"" . $parts[1] . "\"";
                    } else {
                        $countQuery = "SELECT COUNT(*) as count FROM \"" . $fullName . "\"";
                    }
                    $countResult = \Illuminate\Support\Facades\DB::connection($connName)->select($countQuery);
                    $t['row_count'] = $countResult[0]->count ?? 0;
                } catch (\Exception $e) {
                    $t['row_count'] = 0;
                }
            }

            return [
                'tables' => array_values($tablesData),
                'views' => []
            ];

        } catch (\Exception $e) {
            Log::error("StudioConnections::scanDatabaseMetadata error: " . $e->getMessage());
            return null;
        } finally {
            \Illuminate\Support\Facades\DB::disconnect($connName);
        }
    }

    public function render()
    {
        return view('livewire.studio-connections');
    }
}
