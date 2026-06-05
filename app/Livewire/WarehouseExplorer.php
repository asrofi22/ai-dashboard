<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\WarehouseTable;
use App\Models\WarehouseColumn;
use App\Models\DataQualityRecommendation;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseExplorer extends Component
{
    public string $activeTableName = 'fact_sales';
    public string $activeTab = 'schema'; // schema | preview | profiling | catalog | lineage

    // For catalog generation status
    public bool $isGeneratingCatalog = false;

    public function selectTable(string $name): void
    {
        $this->activeTableName = $name;
        // Keep active tab, but fallback to schema if lineage/catalog doesn't exist
    }

    public function selectTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function generateCatalog(): void
    {
        $this->isGeneratingCatalog = true;

        try {
            $table = WarehouseTable::where('name', $this->activeTableName)->first();
            if (!$table) return;

            $columns = WarehouseColumn::where('table_id', $table->id)->get()
                ->map(fn($c) => ['name' => $c->name, 'type' => $c->data_type])
                ->toArray();

            $gemini = app(GeminiService::class);
            $doc = $gemini->generateTableCatalog($this->activeTableName, $columns);

            if ($doc) {
                $table->description = $doc['description'] ?? $table->description;
                
                // Parse dashboards used
                $dashboards = $doc['business_use'] ?? '';
                $table->dashboards_used = is_string($dashboards) 
                    ? array_map('trim', explode(',', $dashboards)) 
                    : $dashboards;

                // Parse key columns
                $keyCols = $doc['key_columns'] ?? '';
                $table->key_columns = is_string($keyCols) 
                    ? array_map('trim', explode(',', $keyCols)) 
                    : $keyCols;

                $table->business_owner = $doc['business_owner'] ?? $table->business_owner;
                $table->save();
            }
        } catch (\Exception $e) {
            Log::error("WarehouseExplorer::generateCatalog error: " . $e->getMessage());
        }

        $this->isGeneratingCatalog = false;
    }

    public function render()
    {
        $tables = WarehouseTable::orderBy('name')->get();
        $selectedTable = WarehouseTable::where('name', $this->activeTableName)->first();

        $schema = [];
        $previewData = ['columns' => [], 'rows' => []];
        $profiling = [];
        $recommendations = [];

        if ($selectedTable) {
            // 1. Fetch Schema Columns
            $schema = WarehouseColumn::where('table_id', $selectedTable->id)->get();

            // 2. Fetch Data Preview (read-only execution)
            if ($this->activeTab === 'preview') {
                try {
                    $allowedTables = ['fact_sales', 'fact_payment', 'dim_customer', 'dim_product'];
                    if (in_array($this->activeTableName, $allowedTables)) {
                        $rawResult = DB::select("SELECT * FROM {$this->activeTableName} LIMIT 15");
                        if (!empty($rawResult)) {
                            $columns = array_keys((array) $rawResult[0]);
                            $rows = array_map(fn($r) => (array) $r, $rawResult);
                            $previewData = [
                                'columns' => $columns,
                                'rows' => $rows
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("WarehouseExplorer Preview Query error: " . $e->getMessage());
                }
            }

            // 3. Fetch Profiling Recommendations & outlier stats
            if ($this->activeTab === 'profiling') {
                $recommendations = DataQualityRecommendation::where('table_name', $this->activeTableName)->get();
            }
        }

        return view('livewire.warehouse-explorer', [
            'tables' => $tables,
            'selectedTable' => $selectedTable,
            'schema' => $schema,
            'previewData' => $previewData,
            'recommendations' => $recommendations
        ]);
    }
}
