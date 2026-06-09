<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\StudioPipeline;
use App\Models\EtlConnection;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StudioAssistant extends Component
{
    public string $prompt = '';
    public bool $isGenerating = false;
    public $generatedPlan = null;
    public string $errorMessage = '';
    public string $successMessage = '';

    // Metadata Scanner and Selection properties
    public $connectionsList = [];
    public $sourceConnectionId = null;
    public $databaseMetadata = [];
    
    // Scheduling properties
    public string $scheduleInterval = 'manual';
    public string $customCron = '';
    
    // Tabbed Result Preview property
    public string $activeTab = 'visual';

    protected array $rules = [
        'prompt' => 'required|min:10',
        'sourceConnectionId' => 'required'
    ];

    protected array $messages = [
        'sourceConnectionId.required' => 'Silakan pilih koneksi database sumber terlebih dahulu.'
    ];

    public function mount(): void
    {
        $this->loadConnections();
    }

    public function loadConnections(): void
    {
        $this->connectionsList = EtlConnection::where('status', 'active')
            ->orderBy('name')
            ->get()
            ->toArray();

        if (!empty($this->connectionsList)) {
            $this->sourceConnectionId = $this->connectionsList[0]['id'];
            $this->updatedSourceConnectionId($this->sourceConnectionId);
        }
    }

    public function updatedSourceConnectionId($value): void
    {
        $conn = EtlConnection::find($value);
        if ($conn) {
            $meta = $conn->metadata ?? [];
            $tables = [];
            
            foreach (array_merge($meta['tables'] ?? [], $meta['views'] ?? []) as $t) {
                $columns = [];
                if (!empty($t['columns'])) {
                    $cols = is_array($t['columns']) ? $t['columns'] : array_map('trim', explode(',', $t['columns']));
                    foreach ($cols as $c) {
                        $columns[] = [
                            'name' => $c,
                            'type' => str_contains(strtolower($c), 'id') ? 'bigint' : 'varchar',
                            'pk' => str_contains(strtolower($c), 'id') || str_contains(strtolower($c), 'key')
                        ];
                    }
                }
                
                $tables[] = [
                    'table' => $t['name'],
                    'columns' => $columns
                ];
            }

            $this->databaseMetadata = [
                'schema' => strtolower($conn->driver) === 'pgsql' ? 'public' : 'dbo',
                'tables' => $tables
            ];
        } else {
            $this->databaseMetadata = [];
        }
    }

    public function generatePipeline(): void
    {
        $this->validate();
        $this->isGenerating = true;
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->generatedPlan = null;

        try {
            $gemini = app(GeminiService::class);

            // Construct connection context from all active connections
            $connectionContext = [];
            $allConnections = EtlConnection::where('status', 'active')->get();
            foreach ($allConnections as $conn) {
                $meta = $conn->metadata ?? [];
                $tablesSummary = [];
                foreach (array_merge($meta['tables'] ?? [], $meta['views'] ?? []) as $t) {
                    $cols = is_array($t['columns']) ? implode(', ', $t['columns']) : $t['columns'];
                    $tablesSummary[] = ['name' => $t['name'], 'columns' => $cols, 'rows' => $t['row_count'] ?? 0];
                }
                $connectionContext[] = [
                    'id'     => $conn->id,
                    'name'   => $conn->name,
                    'driver' => $conn->driver,
                    'tables' => $tablesSummary,
                ];
            }

            $plan = $gemini->generateEtlStudioPipeline($this->prompt, $connectionContext);

            if ($plan && !empty($plan['pipeline_name'])) {
                $this->generatedPlan = $plan;
                $this->activeTab = 'visual';
            } else {
                $this->errorMessage = 'Gagal menghasilkan pipeline. Coba ubah atau perjelas deskripsi Anda.';
            }
        } catch (\Exception $e) {
            Log::error("StudioAssistant::generatePipeline error: " . $e->getMessage());
            $this->errorMessage = 'Terjadi kesalahan sistem saat menghubungi AI: ' . $e->getMessage();
        }

        $this->isGenerating = false;
    }

    public function selectCandidateSource(string $tableName): void
    {
        if (!$this->generatedPlan) return;

        // Find the candidate source in the generated plan
        $candidate = null;
        foreach ($this->generatedPlan['candidate_sources'] ?? [] as $cand) {
            if ($cand['table'] === $tableName) {
                $candidate = $cand;
                break;
            }
        }

        if (!$candidate) return;

        // Clear any previous status messages
        $this->errorMessage = '';
        $this->successMessage = '';

        // Update selected source table
        $this->generatedPlan['source_table'] = $tableName;
        
        // Find source columns from metadata
        $sourceColumns = [];
        $sourceConnName = $candidate['connection'] ?? ($this->generatedPlan['source_connection_name'] ?? '');
        $conn = EtlConnection::where('name', $sourceConnName)->first() 
            ?? EtlConnection::find($this->sourceConnectionId)
            ?? EtlConnection::first();
        
        if ($conn) {
            $this->generatedPlan['source_connection_name'] = $conn->name;
            $meta = $conn->metadata ?? [];
            foreach (array_merge($meta['tables'] ?? [], $meta['views'] ?? []) as $t) {
                if ($t['name'] === $tableName) {
                    $sourceColumns = is_array($t['columns']) ? $t['columns'] : array_map('trim', explode(',', $t['columns']));
                    break;
                }
            }
        }

        // Target columns from existing plan
        $targetColumns = $this->generatedPlan['reasoning']['target_columns'] ?? [];

        // Generate new column mapping using local/AI service
        $gemini = app(GeminiService::class);
        $newMapping = $gemini->generateStudioColumnMapping($sourceColumns, $targetColumns);

        if ($newMapping) {
            $this->generatedPlan['column_mapping'] = $newMapping;
        }

        // Regenerate SQL Preview & Pipeline Steps description
        $sqlMappingLines = [];
        foreach ($this->generatedPlan['column_mapping'] ?? [] as $map) {
            $src = $map['source'];
            $tgt = $map['target'];
            if (str_contains($src, '[Kalkulasi')) {
                $sqlMappingLines[] = "    (first_name || ' ' || last_name) AS {$tgt}";
            } elseif (str_contains($src, '[Serial')) {
                $sqlMappingLines[] = "    NEXTVAL('seq_{$tgt}') AS {$tgt}";
            } else {
                $sqlMappingLines[] = "    {$src} AS {$tgt}";
            }
        }
        $this->generatedPlan['sql_preview'] = "SELECT\n" . implode(",\n", $sqlMappingLines) . "\nFROM {$tableName}";

        // Update PDI Blueprint steps
        if (!empty($this->generatedPlan['json_definition'])) {
            $this->generatedPlan['json_definition']['steps'][0]['table'] = $tableName;
        }
        if (!empty($this->generatedPlan['pipeline_steps'])) {
            $this->generatedPlan['pipeline_steps'][0]['name'] = "Table Input: " . $tableName;
            $this->generatedPlan['pipeline_steps'][0]['outputs'] = $sourceColumns;
        }
        
        $this->successMessage = "Tabel sumber berhasil diubah ke '{$tableName}'! Pemetaan kolom dan SQL preview telah diperbarui otomatis.";
    }

    public function savePipeline(): void
    {
        if (!$this->generatedPlan) return;

        try {
            $likeOperator = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $planSrcName = $this->generatedPlan['source_connection_name'] ?? '';
            $planTgtName = $this->generatedPlan['target_connection_name'] ?? '';

            // Find source connection
            $sourceConn = EtlConnection::where('name', $planSrcName)->first()
                ?? EtlConnection::where('name', $likeOperator, '%' . $planSrcName . '%')->first()
                ?? EtlConnection::where('type', 'Database')->first()
                ?? EtlConnection::first();

            // Find target connection
            $targetConn = EtlConnection::where('name', $planTgtName)->first()
                ?? EtlConnection::where('name', $likeOperator, '%' . $planTgtName . '%')->first()
                ?? EtlConnection::where('type', 'Database')->whereKeyNot($sourceConn?->id)->first()
                ?? EtlConnection::first();

            if (!$sourceConn || !$targetConn) {
                $this->errorMessage = 'Gagal menyimpan. Pastikan Anda telah membuat koneksi sumber dan target di modul Connections terlebih dahulu.';
                return;
            }

            // Verify mapping exists
            $mapping = [];
            foreach ($this->generatedPlan['column_mapping'] ?? [] as $m) {
                $mapping[] = [
                    'source' => $m['source'] ?? '',
                    'target' => $m['target'] ?? ''
                ];
            }

            // ── Generate Visual Canvas Nodes and Connections ─────────────────
            $nodes = [];
            $connections = [];
            
            // 1. Source Node
            $srcDriver = strtolower($sourceConn->driver);
            $srcNodeName = match($srcDriver) {
                'pgsql' => 'PostgreSQL Input',
                'mysql' => 'MySQL Input',
                'oracle' => 'Oracle Input',
                'csv' => 'CSV Input',
                'excel' => 'Excel Input',
                'sharepoint' => 'SharePoint File Input',
                default => 'Table Input'
            };
            $nodes[] = [
                'id' => 'source',
                'label' => $srcNodeName,
                'type' => 'input',
                'name' => 'source',
                'x' => 50,
                'y' => 150
            ];
            
            // 2. Transformations Nodes
            $prevNodeId = 'source';
            $transforms = $this->generatedPlan['transformations'] ?? [];
            foreach ($transforms as $idx => $t) {
                $nodeId = 'trans_' . $idx;
                $nodes[] = [
                    'id' => $nodeId,
                    'label' => $t,
                    'type' => 'transform',
                    'name' => $t,
                    'x' => 220 + ($idx * 170),
                    'y' => 150
                ];
                
                $connections[] = [
                    'fromNodeId' => $prevNodeId,
                    'fromPortType' => 'out',
                    'toNodeId' => $nodeId,
                    'toPortType' => 'in'
                ];
                $prevNodeId = $nodeId;
            }
            
            // 3. Target Node
            $tgtDriver = strtolower($targetConn->driver);
            $tgtNodeName = match($tgtDriver) {
                'pgsql' => 'PostgreSQL Output',
                'mysql' => 'MySQL Output',
                'oracle' => 'Oracle Output',
                'csv' => 'CSV Output',
                'excel' => 'Excel Output',
                default => 'Table Output'
            };
            $nodes[] = [
                'id' => 'target',
                'label' => $tgtNodeName,
                'type' => 'output',
                'name' => 'target',
                'x' => 220 + (count($transforms) * 170),
                'y' => 150
            ];
            
            $connections[] = [
                'fromNodeId' => $prevNodeId,
                'fromPortType' => 'out',
                'toNodeId' => 'target',
                'toPortType' => 'in'
            ];

            $canvasData = [
                'nodes' => $nodes,
                'connections' => $connections,
                'panX' => 0,
                'panY' => 0
            ];

            // Resolve schedule interval
            $schedule = $this->scheduleInterval;
            if ($schedule === 'custom') {
                $schedule = $this->customCron ?: 'manual';
            }

            // Resolve unique name
            $baseName = $this->generatedPlan['pipeline_name'] ?? 'etl_studio_pipeline';
            $name = $baseName;
            $counter = 1;
            while (StudioPipeline::where('name', $name)->exists()) {
                $name = $baseName . '_' . $counter;
                $counter++;
            }

            $pipeline = StudioPipeline::create([
                'name' => $name,
                'source_connection_id' => $sourceConn->id,
                'source_table' => $this->generatedPlan['source_table'] ?? 'customers_raw',
                'transformations' => $this->generatedPlan['transformations'] ?? [],
                'target_connection_id' => $targetConn->id,
                'target_table' => $this->generatedPlan['target_table'] ?? 'dim_customer',
                'column_mapping' => $mapping,
                'is_active' => 'active',
                'canvas_data' => $canvasData,
                'schedule_interval' => $schedule
            ]);

            $this->successMessage = "✅ Pipeline '{$pipeline->name}' berhasil disimpan ke sistem! Anda sekarang dapat menjalankannya di submenu Pipeline Runs.";
            $this->generatedPlan = null;
            $this->prompt = '';
        } catch (\Exception $e) {
            Log::error("StudioAssistant::savePipeline error: " . $e->getMessage());
            $this->errorMessage = 'Gagal menyimpan pipeline ke database: ' . $e->getMessage();
        }
    }

    public function getAirflowDagCode(): string
    {
        if (!$this->generatedPlan) {
            return '';
        }
        return app(\App\Services\AirflowDagGeneratorService::class)->generate($this->generatedPlan);
    }

    public function render()
    {
        return view('livewire.studio-assistant');
    }
}
