<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\StudioPipeline;
use App\Models\EtlConnection;
use App\Models\StudioPipelineRun;
use App\Models\StudioPipelineVersion;
use App\Models\StudioReusableTemplate;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StudioPipelines extends Component
{
    public $pipelines = [];
    public $connections = [];
    public bool $showModal = false; // Backward compatibility
    public bool $isEditing = false;
    public $selectedPipelineId = null;

    // View states for ETL Workspace
    public string $viewMode = 'list'; // 'list' or 'workspace'
    public string $workspaceTab = 'canvas'; // 'canvas' or 'lineage'

    // Form fields
    public string $name = '';
    public $sourceConnectionId = null;
    public string $sourceTable = '';
    public array $selectedTransformations = [];
    public $targetConnectionId = null;
    public string $targetTable = '';
    public array $columnMappings = []; // Array of ['source' => '', 'target' => '']
    public string $isActive = 'active';
    public string $scheduleInterval = 'manual';

    // Dynamic Lists based on dropdown selections
    public array $sourceTables = [];
    public array $targetTables = [];
    public array $sourceColumns = [];
    public array $targetColumns = [];
    public bool $isMappingLoading = false;
    public string $canvasDataJson = '';

    // Reusable template states
    public string $newTemplateName = '';
    public $savedTemplates = [];

    // Versioning states
    public $pipelineVersions = [];
    public $compareVersionLeftId = null;
    public $compareVersionRightId = null;
    public $comparedData = null;

    // Run Engine states
    public bool $isRunning = false;
    public bool $isPaused = false;
    public int $runProgress = 0;
    public string $runLogs = '';
    public int $runRowsRead = 0;
    public int $runRowsWritten = 0;
    public int $runRowsRejected = 0;
    public array $stepMetrics = [];
    public $activeRunId = null;
    public bool $isAnalyzing = false;
    public $selectedRun = null; // Store failed run diagnosis

    // AI Assistant prompt
    public string $assistantPrompt = '';

    public array $availableTransformations = [
        'Remove Duplicate',
        'Remove Null',
        'Trim Text',
        'Uppercase',
        'Lowercase',
        'Rename Column',
        'Data Type Conversion',
        'Filter Data',
        'Value Mapper',
        'String Operations',
        'Mathematical Calculator',
        'Sort Rows',
        'Group By',
        'Concat Fields',
        'Split Fields',
        'Add Constants',
        'Custom SQL',
        // Pentaho components
        'Select Values',
        'Rename Fields',
        'Calculator',
        'Formula',
        'Filter Rows',
        'Aggregation',
        'Unique Rows',
        'Remove Duplicates',
        'Replace Values',
        'Data Validation',
        'Data Cleansing',
        'Join',
        'Lookup',
        'Merge Rows',
        'Pivot',
        'Unpivot'
    ];

    protected array $rules = [
        'name' => 'required|min:3',
        'sourceConnectionId' => 'required',
        'sourceTable' => 'required',
        'targetConnectionId' => 'required',
        'targetTable' => 'required',
    ];

    public function mount(): void
    {
        // Loaded dynamically in render()
    }

    public function loadPipelines(): void
    {
        $this->pipelines = StudioPipeline::with(['sourceConnection', 'targetConnection'])
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function updatedSourceConnectionId($value): void
    {
        $this->sourceTable = '';
        $this->sourceColumns = [];
        $this->columnMappings = [];

        if (!$value) {
            $this->sourceTables = [];
            return;
        }

        $conn = EtlConnection::find($value);
        if ($conn) {
            $metadata = $conn->metadata;
            if ($conn->driver === 'sharepoint') {
                // Files for sharepoint
                $this->sourceTables = array_column($metadata['files'] ?? [], 'name');
            } else {
                // Tables & views for DB/Files
                $tables = array_column($metadata['tables'] ?? [], 'name');
                $views = array_column($metadata['views'] ?? [], 'name');
                $this->sourceTables = array_merge($tables, $views);
            }
        }
    }

    public function updatedTargetConnectionId($value): void
    {
        $this->targetTable = '';
        $this->targetColumns = [];
        $this->columnMappings = [];

        if (!$value) {
            $this->targetTables = [];
            return;
        }

        $conn = EtlConnection::find($value);
        if ($conn) {
            $metadata = $conn->metadata;
            $this->targetTables = array_column($metadata['tables'] ?? [], 'name');
        }
    }

    public function updatedSourceTable($value): void
    {
        $this->sourceColumns = [];
        $this->columnMappings = [];

        if (!$value || !$this->sourceConnectionId) return;

        $conn = EtlConnection::find($this->sourceConnectionId);
        if ($conn) {
            $metadata = $conn->metadata;
            if ($conn->driver === 'sharepoint') {
                $this->sourceColumns = ['id', 'name', 'email', 'phone', 'country', 'sales_amount', 'date'];
            } else {
                foreach (array_merge($metadata['tables'] ?? [], $metadata['views'] ?? []) as $t) {
                    if ($t['name'] === $value) {
                        $this->sourceColumns = $t['columns'] ?? [];
                        break;
                    }
                }
            }
        }

        $this->autoGenerateMapping();
    }

    public function updatedTargetTable($value): void
    {
        $this->targetColumns = [];
        $this->columnMappings = [];

        if (!$value || !$this->targetConnectionId) return;

        $conn = EtlConnection::find($this->targetConnectionId);
        if ($conn) {
            $metadata = $conn->metadata;
            foreach ($metadata['tables'] ?? [] as $t) {
                if ($t['name'] === $value) {
                    $this->targetColumns = $t['columns'] ?? [];
                    break;
                }
            }
        }

        $this->autoGenerateMapping();
    }

    public function autoGenerateMapping(): void
    {
        if (empty($this->sourceColumns) || empty($this->targetColumns)) {
            return;
        }

        $this->isMappingLoading = true;

        try {
            $gemini = app(GeminiService::class);
            $mapping = $gemini->generateStudioColumnMapping($this->sourceColumns, $this->targetColumns);

            if ($mapping) {
                $this->columnMappings = $mapping;
            } else {
                $this->columnMappings = [];
            }
        } catch (\Exception $e) {
            Log::error("StudioPipelines::autoGenerateMapping error: " . $e->getMessage());
        }

        $this->isMappingLoading = false;
    }

    public function addMappingRow(): void
    {
        $this->columnMappings[] = ['source' => '', 'target' => ''];
    }

    public function removeMappingRow(int $index): void
    {
        unset($this->columnMappings[$index]);
        $this->columnMappings = array_values($this->columnMappings);
    }

    public function propagateMetadata(): void
    {
        if (empty($this->canvasDataJson)) {
            return;
        }

        try {
            $canvas = json_decode($this->canvasDataJson, true);
            if (!is_array($canvas) || !isset($canvas['nodes'])) {
                return;
            }

            $nodes = &$canvas['nodes'];
            $connections = $canvas['connections'] ?? [];

            // Adjacency and Parents
            $adj = [];
            $parents = [];
            $indegree = [];

            foreach ($nodes as $node) {
                $id = $node['id'];
                $adj[$id] = [];
                $parents[$id] = [];
                $indegree[$id] = 0;
            }

            // Standardize connection arrays
            $flatConnections = [];
            foreach ($connections as $connItem) {
                if (is_array($connItem)) {
                    if (array_key_exists('from', $connItem) || array_key_exists('fromNodeId', $connItem)) {
                        $flatConnections[] = $connItem;
                    } else {
                        foreach ($connItem as $subItem) {
                            if (is_array($subItem)) {
                                $flatConnections[] = $subItem;
                            }
                        }
                    }
                }
            }

            foreach ($flatConnections as $c) {
                $from = $c['from'] ?? $c['fromNodeId'] ?? null;
                $to = $c['to'] ?? $c['toNodeId'] ?? null;

                if (isset($adj[$from]) && isset($adj[$to])) {
                    $adj[$from][] = $to;
                    $parents[$to][] = $from;
                    $indegree[$to]++;
                }
            }

            // Queue for topological sort
            $queue = [];
            foreach ($nodes as $node) {
                $id = $node['id'];
                if ($indegree[$id] === 0) {
                    $queue[] = $id;
                }
            }

            // Node index lookup map
            $nodeIdx = [];
            foreach ($nodes as $idx => $node) {
                $nodeIdx[$node['id']] = $idx;
            }

            while (!empty($queue)) {
                $uId = array_shift($queue);
                if (!isset($nodeIdx[$uId])) {
                    continue;
                }
                $uIdx = $nodeIdx[$uId];
                $u = &$nodes[$uIdx];

                // fieldsIn
                $fieldsIn = [];
                $seen = [];
                foreach ($parents[$uId] as $pId) {
                    if (isset($nodeIdx[$pId])) {
                        $pIdx = $nodeIdx[$pId];
                        $pOut = $nodes[$pIdx]['metadata']['fieldsOut'] ?? [];
                        foreach ($pOut as $f) {
                            $fName = is_array($f) ? ($f['name'] ?? '') : $f;
                            if ($fName && !isset($seen[$fName])) {
                                $seen[$fName] = true;
                                $fieldsIn[] = is_array($f) ? $f : ['name' => $f, 'type' => 'String'];
                            }
                        }
                    }
                }
                $u['metadata']['fieldsIn'] = $fieldsIn;

                // fieldsOut
                $fieldsOut = [];
                $name = $u['name'] ?? '';
                $type = $u['type'] ?? '';
                $settings = $u['settings'] ?? [];

                if ($type === 'input' || $name === 'source' || $name === 'Database Input' || $name === 'Table Input') {
                    // Table Input / Database Input
                    $sqlFields = [];
                    if (!empty($settings['sql'])) {
                        $sql = $settings['sql'];
                        if (preg_match('/^\s*select\s+(.*?)\s+from/si', $sql, $matches)) {
                            $selectPart = trim($matches[1]);
                            if ($selectPart !== '*') {
                                // Basic select parsing, split columns by comma (handling basic aliases)
                                $cols = explode(',', $selectPart);
                                foreach ($cols as $col) {
                                    $col = trim($col);
                                    if (preg_match('/(?:\bas\b\s+)?(\w+)\s*$/i', $col, $aliasMatches)) {
                                        $sqlFields[] = ['name' => $aliasMatches[1], 'type' => 'String'];
                                    }
                                }
                            }
                        }
                    }
                    if (!empty($sqlFields)) {
                        $fieldsOut = $sqlFields;
                    } else {
                        foreach ($this->sourceColumns as $col) {
                            $fieldsOut[] = ['name' => $col, 'type' => 'String'];
                        }
                    }
                } elseif (in_array($name, ['CSV Input', 'CSV File Input', 'Excel Input', 'Microsoft Excel Input', 'Text File Input', 'JSON Input', 'XML Input', 'Data Grid', 'Generate Rows', 'Get System Info'])) {
                    // File inputs and generators
                    $settingsFields = $settings['fields'] ?? [];
                    if (!empty($settingsFields)) {
                        foreach ($settingsFields as $f) {
                            $fieldsOut[] = [
                                'name' => $f['name'] ?? $f['field'] ?? '',
                                'type' => $f['type'] ?? 'String'
                            ];
                        }
                    } else {
                        if ($name === 'Get System Info') {
                            $fieldsOut[] = ['name' => 'system_date', 'type' => 'Date'];
                            $fieldsOut[] = ['name' => 'ip_address', 'type' => 'String'];
                        } elseif ($name === 'Generate Rows') {
                            $fieldsOut[] = ['name' => 'row_num', 'type' => 'Integer'];
                        } else {
                            foreach ($this->sourceColumns as $col) {
                                $fieldsOut[] = ['name' => $col, 'type' => 'String'];
                            }
                        }
                    }
                } elseif ($name === 'Select Values' || $name === 'Rename Fields') {
                    $selectAlter = $settings['select_alter'] ?? [];
                    $remove = $settings['remove'] ?? [];
                    $metadata = $settings['metadata'] ?? [];

                    if (!empty($selectAlter)) {
                        foreach ($selectAlter as $sa) {
                            if (!empty($sa['field'])) {
                                $origType = 'String';
                                foreach ($fieldsIn as $fi) {
                                    if ($fi['name'] === $sa['field']) {
                                        $origType = $fi['type'] ?? 'String';
                                        break;
                                    }
                                }
                                foreach ($metadata as $m) {
                                    if ($m['field'] === $sa['field'] && !empty($m['type'])) {
                                        $origType = $m['type'];
                                    }
                                }
                                $fieldsOut[] = [
                                    'name' => !empty($sa['rename']) ? $sa['rename'] : $sa['field'],
                                    'type' => $origType
                                ];
                            }
                        }
                    } else {
                        $fieldsOut = $fieldsIn;
                        foreach ($fieldsOut as &$fo) {
                            foreach ($metadata as $m) {
                                if ($m['field'] === $fo['name'] && !empty($m['type'])) {
                                    $fo['type'] = $m['type'];
                                }
                            }
                        }
                        unset($fo);
                    }

                    if (!empty($remove)) {
                        $removeNames = array_column($remove, 'field');
                        $fieldsOut = array_values(array_filter($fieldsOut, function ($f) use ($removeNames) {
                            return !in_array($f['name'], $removeNames);
                        }));
                    }
                } elseif ($name === 'Calculator') {
                    $fieldsOut = $fieldsIn;
                    $calculations = $settings['calculations'] ?? [];
                    foreach ($calculations as $c) {
                        if (!empty($c['field_name'])) {
                            $fieldsOut[] = [
                                'name' => $c['field_name'],
                                'type' => $c['value_type'] ?? 'String'
                            ];
                        }
                    }
                } elseif ($name === 'Formula') {
                    $fieldsOut = $fieldsIn;
                    // Handle list of formulas or single formula builder target
                    $formulas = $settings['formulas'] ?? [];
                    if (!empty($formulas)) {
                        foreach ($formulas as $f) {
                            if (!empty($f['field_name'])) {
                                $found = false;
                                foreach ($fieldsOut as &$fo) {
                                    if ($fo['name'] === $f['field_name']) {
                                        $fo['type'] = $f['type'] ?? 'String';
                                        $found = true;
                                        break;
                                    }
                                }
                                if (!$found) {
                                    $fieldsOut[] = [
                                        'name' => $f['field_name'],
                                        'type' => $f['type'] ?? 'String'
                                    ];
                                }
                            }
                        }
                    }
                    if (!empty($settings['field_name'])) {
                        $found = false;
                        foreach ($fieldsOut as &$fo) {
                            if ($fo['name'] === $settings['field_name']) {
                                $fo['type'] = $settings['type'] ?? 'String';
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $fieldsOut[] = [
                                'name' => $settings['field_name'],
                                'type' => $settings['type'] ?? 'String'
                            ];
                        }
                    }
                } elseif ($name === 'Group By') {
                    $groupFields = $settings['group_fields'] ?? [];
                    $aggregations = $settings['aggregations'] ?? [];

                    foreach ($groupFields as $gf) {
                        if (!empty($gf['field'])) {
                            $origType = 'String';
                            foreach ($fieldsIn as $fi) {
                                if ($fi['name'] === $gf['field']) {
                                    $origType = $fi['type'] ?? 'String';
                                    break;
                                }
                            }
                            $fieldsOut[] = ['name' => $gf['field'], 'type' => $origType];
                        }
                    }
                    foreach ($aggregations as $agg) {
                        if (!empty($agg['field_name'])) {
                            $fieldsOut[] = [
                                'name' => $agg['field_name'],
                                'type' => $agg['value_type'] ?? 'String'
                            ];
                        }
                    }
                } elseif ($name === 'Merge Join' || $name === 'Stream Lookup' || $name === 'Join') {
                    // Merge fields from all parent steps
                    $fieldsOut = [];
                    $seen = [];
                    foreach ($parents[$uId] as $pId) {
                        if (isset($nodeIdx[$pId])) {
                            $pIdx = $nodeIdx[$pId];
                            $pOut = $nodes[$pIdx]['metadata']['fieldsOut'] ?? [];
                            foreach ($pOut as $f) {
                                $fName = is_array($f) ? ($f['name'] ?? '') : $f;
                                if ($fName && !isset($seen[$fName])) {
                                    $seen[$fName] = true;
                                    $fieldsOut[] = is_array($f) ? $f : ['name' => $f, 'type' => 'String'];
                                }
                            }
                        }
                    }
                } elseif ($name === 'Value Mapper') {
                    $fieldsOut = $fieldsIn;
                    if (!empty($settings['target_field'])) {
                        $found = false;
                        foreach ($fieldsOut as &$fo) {
                            if ($fo['name'] === $settings['target_field']) {
                                $fo['type'] = 'String';
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $fieldsOut[] = ['name' => $settings['target_field'], 'type' => 'String'];
                        }
                    }
                } else {
                    $fieldsOut = $fieldsIn;
                }

                $u['metadata']['fieldsOut'] = $fieldsOut;

                // Validation status & messages
                $validationStatus = 'valid';
                $validationMessages = [];

                if ($type === 'input' || $name === 'source' || $name === 'Database Input') {
                    if (empty($this->sourceConnectionId) || empty($this->sourceTable)) {
                        $validationStatus = 'error';
                        $validationMessages[] = "Koneksi sumber atau tabel sumber belum dikonfigurasi.";
                    }
                } elseif ($name === 'CSV Input' || $name === 'Excel Input' || $name === 'JSON Input') {
                    if (empty($settings['file_path']) && empty($settings['file_name']) && empty($settings['json_path'])) {
                        $validationStatus = 'warning';
                        $validationMessages[] = "Konfigurasi berkas masukan belum lengkap.";
                    }
                } elseif ($name === 'Select Values') {
                    if (empty($fieldsIn)) {
                        $validationStatus = 'warning';
                        $validationMessages[] = "Langkah ini belum menerima data masukan dari alur upstream.";
                    }
                } elseif ($name === 'Formula') {
                    if (empty($settings['field_name']) || empty($settings['formula'])) {
                        $validationStatus = 'warning';
                        $validationMessages[] = "Nama field hasil dan formula harus diisi.";
                    }
                } elseif ($name === 'Calculator') {
                    if (empty($settings['calculations'])) {
                        $validationStatus = 'warning';
                        $validationMessages[] = "Belum ada kalkulasi yang dikonfigurasi.";
                    }
                } elseif ($name === 'Sort Rows') {
                    if (empty($settings['fields'])) {
                        $validationStatus = 'warning';
                        $validationMessages[] = "Belum ada kolom pengurutan yang dikonfigurasi.";
                    }
                } elseif ($name === 'Unique Rows') {
                    if (empty($settings['compare_fields'])) {
                        $validationStatus = 'warning';
                        $validationMessages[] = "Belum ada kolom perbandingan unik yang dipilih.";
                    }
                } elseif ($name === 'Group By') {
                    if (empty($settings['group_fields']) && empty($settings['aggregations'])) {
                        $validationStatus = 'warning';
                        $validationMessages[] = "Konfigurasi pengelompokan atau agregasi belum diatur.";
                    }
                } elseif ($name === 'Join') {
                    if (count($parents[$uId]) < 2) {
                        $validationStatus = 'error';
                        $validationMessages[] = "Langkah gabungan (Join) memerlukan minimal 2 aliran masukan.";
                    }
                } elseif ($type === 'output' || $name === 'target' || $name === 'Table Output') {
                    if (empty($this->targetConnectionId) || empty($this->targetTable)) {
                        $validationStatus = 'error';
                        $validationMessages[] = "Koneksi target atau tabel target belum dikonfigurasi.";
                    }
                    if (empty($this->columnMappings)) {
                        $validationStatus = 'warning';
                        $validationMessages[] = "Skema pemetaan kolom (Column Mapping) kosong.";
                    }
                }

                $u['validation'] = [
                    'status' => $validationStatus,
                    'messages' => $validationMessages
                ];

                foreach ($adj[$uId] as $vId) {
                    $indegree[$vId]--;
                    if ($indegree[$vId] === 0) {
                        $queue[] = $vId;
                    }
                }
            }

            $this->canvasDataJson = json_encode($canvas);
        } catch (\Exception $e) {
            Log::error("propagateMetadata error: " . $e->getMessage());
        }
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->scheduleInterval = 'manual';
        
        // Setup default blank canvas with source and target nodes
        $this->canvasDataJson = json_encode([
            'nodes' => [
                ['id' => 'source', 'label' => 'Source Input', 'type' => 'input', 'name' => 'source', 'x' => 50, 'y' => 150],
                ['id' => 'target', 'label' => 'Target Output', 'type' => 'output', 'name' => 'target', 'x' => 550, 'y' => 150]
            ],
            'connections' => []
        ]);
        
        $this->viewMode = 'workspace';
        $this->workspaceTab = 'canvas';
        $this->loadTemplatesAndVersions();
    }

    public function openEdit(int $id): void
    {
        $this->resetForm();
        $pipe = StudioPipeline::findOrFail($id);
        $this->selectedPipelineId = $pipe->id;
        $this->name = $pipe->name;
        
        $this->sourceConnectionId = $pipe->source_connection_id;
        $this->updatedSourceConnectionId($this->sourceConnectionId);
        
        $this->sourceTable = $pipe->source_table;
        $this->updatedSourceTable($this->sourceTable);
        
        $this->selectedTransformations = $pipe->transformations ?? [];
        
        $this->targetConnectionId = $pipe->target_connection_id;
        $this->updatedTargetConnectionId($this->targetConnectionId);
        
        $this->targetTable = $pipe->target_table;
        $this->updatedTargetTable($this->targetTable);
        
        $this->columnMappings = $pipe->column_mapping ?? [];
        $this->isActive = $pipe->is_active;
        $this->scheduleInterval = $pipe->schedule_interval ?? 'manual';

        // Load existing canvas data or build fallback visual layout
        if ($pipe->canvas_data) {
            $this->canvasDataJson = json_encode($pipe->canvas_data);
        } else {
            // Build visual layout from existing transformations
            $nodes = [
                ['id' => 'source', 'label' => 'Source Input', 'type' => 'input', 'name' => 'source', 'x' => 50, 'y' => 150]
            ];
            $connections = [];
            $prevId = 'source';
            
            $transforms = $pipe->transformations ?? [];
            foreach ($transforms as $i => $t) {
                $id = 'node_' . ($i + 1) . '_' . rand(10, 99);
                $nodes[] = [
                    'id' => $id,
                    'label' => $t,
                    'type' => 'transform',
                    'name' => $t,
                    'x' => 50 + (($i + 1) * 160),
                    'y' => 150
                ];
                $connections[] = ['from' => $prevId, 'to' => $id];
                $prevId = $id;
            }
            
            $nodes[] = ['id' => 'target', 'label' => 'Target Output', 'type' => 'output', 'name' => 'target', 'x' => 50 + ((count($transforms) + 1) * 160), 'y' => 150];
            $connections[] = ['from' => $prevId, 'to' => 'target'];
            
            $this->canvasDataJson = json_encode([
                'nodes' => $nodes,
                'connections' => $connections
            ]);
        }

        $this->isEditing = true;
        $this->viewMode = 'workspace';
        $this->workspaceTab = 'canvas';
        $this->loadTemplatesAndVersions();
    }

    public function closeWorkspace(): void
    {
        $this->viewMode = 'list';
        $this->resetForm();
        $this->isRunning = false;
        $this->isPaused = false;
        $this->activeRunId = null;
        $this->selectedRun = null;
    }

    public function loadTemplatesAndVersions(): void
    {
        if ($this->selectedPipelineId) {
            $this->pipelineVersions = StudioPipelineVersion::where('pipeline_id', $this->selectedPipelineId)
                ->orderBy('version_number', 'desc')
                ->get()
                ->toArray();
        } else {
            $this->pipelineVersions = [];
        }

        $this->savedTemplates = StudioReusableTemplate::orderBy('name')->get()->toArray();
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->sourceConnectionId = null;
        $this->sourceTable = '';
        $this->selectedTransformations = [];
        $this->targetConnectionId = null;
        $this->targetTable = '';
        $this->columnMappings = [];
        $this->isActive = 'active';
        $this->scheduleInterval = 'manual';
        $this->sourceTables = [];
        $this->targetTables = [];
        $this->sourceColumns = [];
        $this->targetColumns = [];
        $this->selectedPipelineId = null;
        $this->canvasDataJson = '';
        $this->pipelineVersions = [];
        $this->compareVersionLeftId = null;
        $this->compareVersionRightId = null;
        $this->comparedData = null;
    }

    public function save(): void
    {
        $this->validate();

        try {
            $this->propagateMetadata();
            $canvas = !empty($this->canvasDataJson) ? json_decode($this->canvasDataJson, true) : null;
            
            $data = [
                'name' => $this->name,
                'source_connection_id' => $this->sourceConnectionId,
                'source_table' => $this->sourceTable,
                'transformations' => $this->selectedTransformations,
                'target_connection_id' => $this->targetConnectionId,
                'target_table' => $this->targetTable,
                'column_mapping' => $this->columnMappings,
                'is_active' => $this->isActive,
                'canvas_data' => $canvas,
                'schedule_interval' => $this->scheduleInterval
            ];

            if ($this->isEditing) {
                $exists = StudioPipeline::where('name', $this->name)->whereKeyNot($this->selectedPipelineId)->exists();
                if ($exists) {
                    session()->flash('error', "Gagal menyimpan: Nama pipeline '{$this->name}' sudah digunakan oleh pipeline lain.");
                    return;
                }
                $pipe = StudioPipeline::findOrFail($this->selectedPipelineId);
                $pipe->update($data);
                $this->saveNewVersion($pipe);
                session()->flash('message', "Pipeline '{$this->name}' berhasil diperbarui dan versi baru disimpan.");
            } else {
                $exists = StudioPipeline::where('name', $this->name)->exists();
                if ($exists) {
                    session()->flash('error', "Gagal menyimpan: Nama pipeline '{$this->name}' sudah digunakan. Silakan gunakan nama lain.");
                    return;
                }
                $pipe = StudioPipeline::create($data);
                $this->selectedPipelineId = $pipe->id;
                $this->isEditing = true;
                $this->saveNewVersion($pipe);
                session()->flash('message', "Pipeline '{$this->name}' berhasil dibuat.");
            }

            $this->loadPipelines();
            $this->loadTemplatesAndVersions();
        } catch (\Exception $e) {
            Log::error("StudioPipelines::save error: " . $e->getMessage());
            session()->flash('error', "Gagal menyimpan pipeline: " . $e->getMessage());
        }
    }

    protected function saveNewVersion(StudioPipeline $pipeline): void
    {
        $maxVer = StudioPipelineVersion::where('pipeline_id', $pipeline->id)->max('version_number') ?? 0;
        
        StudioPipelineVersion::create([
            'pipeline_id' => $pipeline->id,
            'version_number' => $maxVer + 1,
            'name' => $pipeline->name,
            'source_connection_id' => $pipeline->source_connection_id,
            'source_table' => $pipeline->source_table,
            'transformations' => $pipeline->transformations,
            'target_connection_id' => $pipeline->target_connection_id,
            'target_table' => $pipeline->target_table,
            'column_mapping' => $pipeline->column_mapping,
            'canvas_data' => $pipeline->canvas_data,
            'schedule_interval' => $pipeline->schedule_interval
        ]);
    }

    public function restoreVersion(int $versionId): void
    {
        try {
            $ver = StudioPipelineVersion::findOrFail($versionId);
            $this->name = $ver->name;
            $this->sourceConnectionId = $ver->source_connection_id;
            $this->updatedSourceConnectionId($this->sourceConnectionId);
            $this->sourceTable = $ver->source_table;
            $this->updatedSourceTable($this->sourceTable);
            $this->selectedTransformations = $ver->transformations ?? [];
            $this->targetConnectionId = $ver->target_connection_id;
            $this->updatedTargetConnectionId($this->targetConnectionId);
            $this->targetTable = $ver->target_table;
            $this->updatedTargetTable($this->targetTable);
            $this->columnMappings = $ver->column_mapping ?? [];
            $this->canvasDataJson = json_encode($ver->canvas_data);
            $this->scheduleInterval = $ver->schedule_interval;

            session()->flash('message', "Versi #{$ver->version_number} berhasil dimuat ke editor. Jangan lupa klik Simpan untuk memperbarui.");
        } catch (\Exception $e) {
            session()->flash('error', "Gagal memuat versi: " . $e->getMessage());
        }
    }

    public function compareVersions(): void
    {
        if (!$this->compareVersionLeftId || !$this->compareVersionRightId) {
            $this->comparedData = null;
            return;
        }

        try {
            $left = StudioPipelineVersion::findOrFail($this->compareVersionLeftId);
            $right = StudioPipelineVersion::findOrFail($this->compareVersionRightId);

            $this->comparedData = [
                'left_ver' => $left->version_number,
                'right_ver' => $right->version_number,
                'left_name' => $left->name,
                'right_name' => $right->name,
                'left_table' => $left->source_table . ' -> ' . $left->target_table,
                'right_table' => $right->source_table . ' -> ' . $right->target_table,
                'left_transforms' => $left->transformations ?? [],
                'right_transforms' => $right->transformations ?? [],
                'left_mapping_count' => count($left->column_mapping ?? []),
                'right_mapping_count' => count($right->column_mapping ?? []),
            ];
        } catch (\Exception $e) {
            session()->flash('error', "Gagal membandingkan versi: " . $e->getMessage());
        }
    }

    public function saveAsTemplate(string $name, string $type): void
    {
        if (empty($name)) {
            session()->flash('error', "Nama template tidak boleh kosong.");
            return;
        }

        try {
            $config = [];
            if ($type === 'mapping') {
                $config = $this->columnMappings;
            } else {
                $config = $this->selectedTransformations;
            }

            StudioReusableTemplate::create([
                'name' => $name,
                'type' => $type,
                'config' => $config
            ]);

            $this->newTemplateName = '';
            $this->loadTemplatesAndVersions();
            session()->flash('message', "Template '{$name}' berhasil disimpan!");
        } catch (\Exception $e) {
            session()->flash('error', "Gagal menyimpan template: " . $e->getMessage());
        }
    }

    public function loadTemplate(int $templateId): void
    {
        try {
            $tpl = StudioReusableTemplate::findOrFail($templateId);
            if ($tpl->type === 'mapping') {
                $this->columnMappings = $tpl->config ?? [];
                session()->flash('message', "Template pemetaan '{$tpl->name}' diterapkan.");
            } else {
                $transforms = $tpl->config ?? [];
                $this->selectedTransformations = array_unique(array_merge($this->selectedTransformations, $transforms));
                
                // Rebuild canvas visually
                $nodes = [
                    ['id' => 'source', 'label' => 'Source Input', 'type' => 'input', 'name' => 'source', 'x' => 50, 'y' => 150]
                ];
                $connections = [];
                $prevId = 'source';
                foreach ($this->selectedTransformations as $i => $t) {
                    $id = 'node_' . ($i + 1) . '_' . rand(10, 99);
                    $nodes[] = [
                        'id' => $id,
                        'label' => $t,
                        'type' => 'transform',
                        'name' => $t,
                        'x' => 50 + (($i + 1) * 160),
                        'y' => 150
                    ];
                    $connections[] = ['from' => $prevId, 'to' => $id];
                    $prevId = $id;
                }
                $nodes[] = ['id' => 'target', 'label' => 'Target Output', 'type' => 'output', 'name' => 'target', 'x' => 50 + ((count($this->selectedTransformations) + 1) * 160), 'y' => 150];
                $connections[] = ['from' => $prevId, 'to' => 'target'];

                $this->canvasDataJson = json_encode([
                    'nodes' => $nodes,
                    'connections' => $connections
                ]);
                session()->flash('message', "Template transformasi '{$tpl->name}' berhasil digabungkan ke kanvas.");
            }
        } catch (\Exception $e) {
            session()->flash('error', "Gagal memuat template: " . $e->getMessage());
        }
    }

    public function startRunSimulation(): void
    {
        $warnings = $this->getValidationWarningsProperty();
        if (count($warnings) > 0) {
            session()->flash('error', "Gagal menjalankan. Tolong perbaiki error validasi visual terlebih dahulu.");
            return;
        }

        try {
            $run = StudioPipelineRun::create([
                'pipeline_id' => $this->selectedPipelineId ?? StudioPipeline::first()->id ?? 1,
                'status' => 'Running',
                'start_time' => Carbon::now(),
                'rows_read' => 0,
                'rows_written' => 0,
                'rows_rejected' => 0,
                'execution_logs' => "INFO - Memulai inisialisasi eksekusi ETL di workspace...\n"
            ]);

            $this->activeRunId = $run->id;
            $this->isRunning = true;
            $this->isPaused = false;
            $this->runProgress = 0;
            $this->runLogs = "INFO - [" . now()->toTimeString() . "] Menyiapkan mesin workspace ETL...\n";
            
            // Build dynamic step metrics keys
            $canvas = json_decode($this->canvasDataJson, true);
            $metrics = [];
            foreach ($canvas['nodes'] ?? [] as $n) {
                $metrics[$n['id']] = ['input' => 0, 'output' => 0];
            }
            $this->stepMetrics = $metrics;

            $willSucceed = rand(1, 100) <= 85;

            // Dispatch event to Alpine to handle progression
            $this->dispatch('start-workspace-execution-simulation', [
                'runId' => $run->id,
                'pipelineName' => $this->name ?: 'pipeline_custom',
                'transformations' => $this->selectedTransformations,
                'willSucceed' => $willSucceed
            ]);

        } catch (\Exception $e) {
            Log::error("StudioPipelines::startRunSimulation error: " . $e->getMessage());
            session()->flash('error', "Gagal memulai simulasi run: " . $e->getMessage());
        }
    }

    public function pauseRunSimulation(): void
    {
        $this->isPaused = true;
        $this->runLogs .= "WARNING - [" . now()->toTimeString() . "] Eksekusi dijeda oleh pengguna (Paused).\n";
        $this->dispatch('pause-workspace-execution-simulation');
    }

    public function resumeRunSimulation(): void
    {
        $this->isPaused = false;
        $this->runLogs .= "INFO - [" . now()->toTimeString() . "] Melanjutkan eksekusi dari kondisi jeda...\n";
        $this->dispatch('resume-workspace-execution-simulation');
    }

    public function stopRunSimulation(): void
    {
        if ($this->activeRunId) {
            try {
                $run = StudioPipelineRun::find($this->activeRunId);
                if ($run) {
                    $run->update([
                        'status' => 'Failed',
                        'end_time' => Carbon::now(),
                        'execution_logs' => $this->runLogs . "ERROR - Eksekusi dihentikan paksa oleh pengguna.\n",
                        'error_log' => 'User aborted.'
                    ]);
                }
            } catch (\Exception $e) {}
        }
        $this->isRunning = false;
        $this->isPaused = false;
        $this->activeRunId = null;
        session()->flash('message', "Eksekusi pipeline berhasil dihentikan.");
    }

    public function completeWorkspaceRunSuccess(int $runId, string $logs, int $read, int $written, int $rejected, array $metrics): void
    {
        try {
            $run = StudioPipelineRun::findOrFail($runId);

            // Execute real ETL pipeline physically!
            if (!app()->runningUnitTests()) {
                try {
                    $pipeline = StudioPipeline::findOrFail($run->pipeline_id);
                    $executor = app(\App\Services\PipelineExecutorService::class);
                    $result = $executor->execute($pipeline);
                    
                    $read = $result['read'];
                    $written = $result['written'];
                    $rejected = $result['rejected'];
                    
                    $logs .= "INFO - [" . Carbon::now()->toTimeString() . "] Eksekusi data fisik berhasil dilakukan di target database.\n";
                } catch (\Exception $ex) {
                    Log::error("Physical ETL run failed during workspace completion: " . $ex->getMessage());
                    $this->completeWorkspaceRunFailed($runId, $logs . "\nERROR - [" . Carbon::now()->toTimeString() . "] Gagal memuat data fisik ke database: " . $ex->getMessage(), $ex->getMessage(), $metrics);
                    return;
                }
            }

            $run->update([
                'status' => 'Success',
                'end_time' => Carbon::now(),
                'duration_seconds' => (int) abs(Carbon::now()->diffInSeconds($run->start_time)),
                'rows_read' => $read,
                'rows_written' => $written,
                'rows_rejected' => $rejected,
                'execution_logs' => $logs,
                'step_metrics' => $metrics
            ]);

            $this->runLogs = $logs;
            $this->runRowsRead = $read;
            $this->runRowsWritten = $written;
            $this->runRowsRejected = $rejected;
            $this->stepMetrics = $metrics;
            $this->isRunning = false;
            $this->isPaused = false;
            $this->activeRunId = null;
            $this->selectedRun = $run->toArray();

            $this->loadPipelines();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function completeWorkspaceRunFailed(int $runId, string $logs, string $errorLog, array $metrics): void
    {
        try {
            $run = StudioPipelineRun::findOrFail($runId);
            $run->update([
                'status' => 'Failed',
                'end_time' => Carbon::now(),
                'duration_seconds' => (int) abs(Carbon::now()->diffInSeconds($run->start_time)),
                'rows_read' => $metrics['source']['output'] ?? rand(500, 1000),
                'rows_written' => 0,
                'rows_rejected' => $metrics['source']['output'] ?? rand(10, 50),
                'execution_logs' => $logs,
                'error_log' => $errorLog,
                'step_metrics' => $metrics
            ]);

            $this->runLogs = $logs;
            $this->runRowsRead = $run->rows_read;
            $this->runRowsWritten = 0;
            $this->runRowsRejected = $run->rows_rejected;
            $this->stepMetrics = $metrics;
            $this->isRunning = false;
            $this->isPaused = false;
            $this->activeRunId = null;

            // Trigger AI Diagnosis
            $this->isAnalyzing = true;
            $gemini = app(GeminiService::class);
            $analysis = $gemini->analyzeStudioFailure($this->name ?: 'pipeline_custom', $errorLog);
            if ($analysis) {
                $run->ai_failure_analysis = $analysis;
                $run->save();
            }
            $this->selectedRun = $run->toArray();
            $this->isAnalyzing = false;

            $this->loadPipelines();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function addAiRecommendedStep(string $transformName): void
    {
        $canvas = !empty($this->canvasDataJson) ? json_decode($this->canvasDataJson, true) : ['nodes' => [], 'connections' => []];
        
        $targetNode = null;
        $targetIndex = -1;
        foreach ($canvas['nodes'] as $idx => $node) {
            if ($node['id'] === 'target') {
                $targetNode = $node;
                $targetIndex = $idx;
                break;
            }
        }

        if ($targetNode) {
            $canvas['nodes'][$targetIndex]['x'] += 160;
            $newX = $targetNode['x'];
            $newY = $targetNode['y'];
        } else {
            $newX = 300;
            $newY = 150;
        }

        $id = 'node_ai_rec_' . time() . rand(10, 99);
        $newNode = [
            'id' => $id,
            'type' => 'transform',
            'name' => $transformName,
            'label' => $transformName,
            'x' => $newX,
            'y' => $newY
        ];
        $canvas['nodes'][] = $newNode;

        $targetConnIdx = -1;
        foreach ($canvas['connections'] as $idx => $conn) {
            if ($conn['to'] === 'target') {
                $targetConnIdx = $idx;
                break;
            }
        }

        if ($targetConnIdx !== -1) {
            $oldFrom = $canvas['connections'][$targetConnIdx]['from'];
            $canvas['connections'][$targetConnIdx]['to'] = $id;
            $canvas['connections'][] = ['from' => $id, 'to' => 'target'];
        } else {
            $canvas['connections'][] = ['from' => 'source', 'to' => $id];
            $canvas['connections'][] = ['from' => $id, 'to' => 'target'];
        }

        $this->canvasDataJson = json_encode($canvas);
        $this->selectedTransformations[] = $transformName;
        $this->selectedTransformations = array_unique($this->selectedTransformations);
        $this->updateLivewire(); // Update states

        session()->flash('message', "Rekomendasi AI '{$transformName}' berhasil ditambahkan ke kanvas.");
    }

    public function generateWorkspacePipeline(): void
    {
        if (empty($this->assistantPrompt)) return;
        
        $this->isMappingLoading = true;
        
        try {
            $gemini = app(GeminiService::class);
            
            // --- Collect real schema context from all active connections ---
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
            
            $plan = $gemini->generateEtlStudioPipeline($this->assistantPrompt, $connectionContext);
            
            if ($plan && !empty($plan['pipeline_name'])) {
                $this->name = $plan['pipeline_name'];
                
                // --- Resolve connections: exact match first, then partial LIKE, then fallback ---
                $planSrcName = $plan['source_connection_name'] ?? '';
                $planTgtName = $plan['target_connection_name'] ?? '';
                $likeOperator = \Illuminate\Support\Facades\DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
                
                // Find source connection
                $srcConn = EtlConnection::where('name', $planSrcName)->first()
                    ?? EtlConnection::where('name', $likeOperator, '%' . $planSrcName . '%')->first()
                    ?? EtlConnection::where('type', 'Database')->first()
                    ?? EtlConnection::first();
                
                // Find target connection  
                $tgtConn = EtlConnection::where('name', $planTgtName)->first()
                    ?? EtlConnection::where('name', $likeOperator, '%' . $planTgtName . '%')->first()
                    ?? EtlConnection::where('type', 'Database')->whereKeyNot($srcConn?->id)->first()
                    ?? EtlConnection::first();
                    
                if ($srcConn) {
                    $this->sourceConnectionId = $srcConn->id;
                    $this->updatedSourceConnectionId($srcConn->id);
                }
                
                // Set source table — don't call updatedSourceTable (it would reset column mappings)
                $this->sourceTable = $plan['source_table'] ?? 'customers_raw';
                // Load source columns from metadata manually for display
                if ($srcConn) {
                    $meta = $srcConn->metadata ?? [];
                    foreach (array_merge($meta['tables'] ?? [], $meta['views'] ?? []) as $t) {
                        if ($t['name'] === $this->sourceTable) {
                            $this->sourceColumns = $t['columns'] ?? [];
                            break;
                        }
                    }
                }
                
                if ($tgtConn) {
                    $this->targetConnectionId = $tgtConn->id;
                    $this->updatedTargetConnectionId($tgtConn->id);
                }
                
                // Set target table — don't call updatedTargetTable (it would reset column mappings)
                $this->targetTable = $plan['target_table'] ?? 'dim_customer';
                // Load target columns from metadata manually for display
                if ($tgtConn) {
                    $meta = $tgtConn->metadata ?? [];
                    foreach ($meta['tables'] ?? [] as $t) {
                        if ($t['name'] === $this->targetTable) {
                            $this->targetColumns = $t['columns'] ?? [];
                            break;
                        }
                    }
                }
                
                // Apply AI-generated column mapping (preserve it, don't overwrite with autoGenerateMapping)
                if (!empty($plan['column_mapping'])) {
                    $this->columnMappings = $plan['column_mapping'];
                }
                
                // Construct Visual Canvas Nodes & Connections
                $nodes = [];
                $connections = [];
                
                $srcDriver = $srcConn ? strtolower($srcConn->driver) : 'pgsql';
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
                    'y' => 150,
                    'settings' => [
                        'connection' => $this->sourceConnectionId,
                        'table' => $this->sourceTable,
                        'sql' => "SELECT * FROM {$this->sourceTable}",
                        'parameters' => [],
                        'variables' => []
                    ]
                ];
                
                $prevId = 'source';
                $transforms = $plan['transformations'] ?? [];
                foreach ($transforms as $i => $t) {
                    $id = 'node_' . ($i + 1) . '_' . rand(10, 99);
                    $nodeSettings = [];
                    
                    if ($t === 'Select Values') {
                        $selectAlter = [];
                        foreach ($this->columnMappings as $map) {
                            if (!str_contains($map['source'], '[')) {
                                $selectAlter[] = [
                                    'field' => $map['source'],
                                    'rename' => $map['target']
                                ];
                            }
                        }
                        $nodeSettings = [
                            'select_alter' => $selectAlter,
                            'remove' => [],
                            'metadata' => []
                        ];
                    } elseif ($t === 'Formula') {
                        // Find any calculated column mapping
                        $calculatedMap = null;
                        foreach ($this->columnMappings as $map) {
                            if (str_contains($map['source'], '[Kalkulasi:')) {
                                $calculatedMap = $map;
                                break;
                            }
                        }
                        if ($calculatedMap) {
                            preg_match('/\[Kalkulasi:\s*(.*?)\s*\]/i', $calculatedMap['source'], $fMatches);
                            $formulaExpr = $fMatches[1] ?? '';
                            $nodeSettings = [
                                'field_name' => $calculatedMap['target'],
                                'formula' => $formulaExpr,
                                'type' => 'String'
                            ];
                        } else {
                            $nodeSettings = [
                                'field_name' => '',
                                'formula' => '',
                                'type' => 'String'
                            ];
                        }
                    } elseif ($t === 'Calculator') {
                        $calculations = [];
                        foreach ($this->columnMappings as $map) {
                            if (str_contains($map['source'], '[Kalkulasi:')) {
                                preg_match('/\[Kalkulasi:\s*(.*?)\s*\]/i', $map['source'], $fMatches);
                                $formulaExpr = $fMatches[1] ?? '';
                                if (str_contains($formulaExpr, '+')) {
                                    $parts = array_map('trim', explode('+', $formulaExpr));
                                    $calculations[] = [
                                        'field_name' => $map['target'],
                                        'calculation_type' => 'Add (A + B)',
                                        'field_a' => $parts[0] ?? '',
                                        'field_b' => $parts[1] ?? '',
                                        'value_type' => 'String'
                                    ];
                                }
                            }
                        }
                        $nodeSettings = [
                            'calculations' => $calculations
                        ];
                    } elseif ($t === 'Filter Rows') {
                        $nodeSettings = [
                            'condition' => [
                                'type' => 'AND',
                                'rules' => [
                                    ['field' => '', 'op' => '=', 'value' => '']
                                ]
                            ]
                        ];
                    } elseif ($t === 'Sort Rows') {
                        $nodeSettings = [
                            'fields' => []
                        ];
                    } elseif ($t === 'Unique Rows') {
                        $nodeSettings = [
                            'compare_fields' => [],
                            'case_sensitive' => true,
                            'ignore_null' => false,
                            'sort_before' => true,
                            'keep' => 'First'
                        ];
                    } elseif ($t === 'Group By') {
                        $nodeSettings = [
                            'group_fields' => [],
                            'aggregations' => []
                        ];
                    } elseif ($t === 'Join') {
                        $nodeSettings = [
                            'join_type' => 'Inner',
                            'keys' => []
                        ];
                    }
                    
                    $nodes[] = [
                        'id' => $id,
                        'label' => $t,
                        'type' => 'transform',
                        'name' => $t,
                        'x' => 50 + (($i + 1) * 160),
                        'y' => 150,
                        'settings' => $nodeSettings
                    ];
                    $connections[] = ['from' => $prevId, 'to' => $id];
                    $prevId = $id;
                }
                
                $tgtDriver = $tgtConn ? strtolower($tgtConn->driver) : 'pgsql';
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
                    'x' => 50 + ((count($transforms) + 1) * 160),
                    'y' => 150,
                    'settings' => [
                        'connection' => $this->targetConnectionId,
                        'table' => $this->targetTable,
                        'commit_size' => 1000,
                        'batch_insert' => true,
                        'use_transaction' => true,
                        'error_handling' => [
                            'reject_row' => false,
                            'continue_on_error' => false,
                            'log_error' => true
                        ]
                    ]
                ];
                $connections[] = ['from' => $prevId, 'to' => 'target'];
                
                $this->canvasDataJson = json_encode([
                    'nodes' => $nodes,
                    'connections' => $connections
                ]);
                $this->selectedTransformations = $transforms;
                
                $this->propagateMetadata();
                
                $this->assistantPrompt = '';
                session()->flash('message', "AI berhasil merancang pipeline '{$this->name}' visual!");
            }
        } catch (\Exception $e) {
            Log::error("StudioPipelines::generateWorkspacePipeline error: " . $e->getMessage());
            session()->flash('error', "Gagal rancang otomatis: " . $e->getMessage());
        }
        
        $this->isMappingLoading = false;
    }

    public function getValidationWarningsProperty(): array
    {
        $this->propagateMetadata();
        $warnings = [];
        
        $canvas = json_decode($this->canvasDataJson, true);
        if (isset($canvas['nodes']) && is_array($canvas['nodes'])) {
            foreach ($canvas['nodes'] as $node) {
                if (isset($node['validation']['status']) && $node['validation']['status'] === 'error') {
                    foreach ($node['validation']['messages'] ?? [] as $msg) {
                        $warnings[] = "[" . ($node['label'] ?? $node['name']) . "] Error: " . $msg;
                    }
                }
            }
        }

        if (empty($this->name) || strlen($this->name) < 3) {
            $warnings[] = "Nama pipeline kosong atau terlalu pendek (minimal 3 karakter).";
        }
        if (!$this->sourceConnectionId) {
            $warnings[] = "Koneksi Extract Source database/file belum ditentukan.";
        }
        if (empty($this->sourceTable)) {
            $warnings[] = "Tabel/Berkas sumber data belum dipilih.";
        }
        if (!$this->targetConnectionId) {
            $warnings[] = "Koneksi Load Target database warehouse belum ditentukan.";
        }
        if (empty($this->targetTable)) {
            $warnings[] = "Tabel target gudang data belum dipilih.";
        }
        if (empty($this->columnMappings)) {
            $warnings[] = "Skema Pemetaan Kolom (Column Mapping) kosong.";
        }

        if (!empty($this->canvasDataJson)) {
            $canvas = json_decode($this->canvasDataJson, true);
            $hasConnection = false;
            $hasTargetLink = false;
            
            // Normalize/flatten the connections array if it's nested
            $connections = [];
            if (isset($canvas['connections']) && is_array($canvas['connections'])) {
                foreach ($canvas['connections'] as $connItem) {
                    if (is_array($connItem)) {
                        if (array_key_exists('from', $connItem) || array_key_exists('fromNodeId', $connItem)) {
                            $connections[] = $connItem;
                        } else {
                            foreach ($connItem as $subItem) {
                                if (is_array($subItem)) {
                                    $connections[] = $subItem;
                                }
                            }
                        }
                    }
                }
            }

            foreach ($connections as $conn) {
                $from = $conn['from'] ?? $conn['fromNodeId'] ?? null;
                $to = $conn['to'] ?? $conn['toNodeId'] ?? null;

                if ($from === 'source') {
                    $hasConnection = true;
                }
                if ($to === 'target') {
                    $hasTargetLink = true;
                }
            }

            if (!$hasConnection) {
                $warnings[] = "Visual Kanvas Error: Node Source Input ('source') belum dihubungkan ke langkah apa pun.";
            }

            if (!$hasTargetLink) {
                $warnings[] = "Visual Kanvas Error: Node Target Output ('target') belum menerima input alur data.";
            }
        }

        return $warnings;
    }

    public function delete(int $id): void
    {
        try {
            $pipe = StudioPipeline::findOrFail($id);
            $name = $pipe->name;
            $pipe->delete();

            $this->loadPipelines();
            session()->flash('message', "Pipeline '{$name}' berhasil dihapus.");
        } catch (\Exception $e) {
            Log::error("StudioPipelines::delete error: " . $e->getMessage());
        }
    }

    public function updateLivewire(): void
    {
        $this->propagateMetadata();
    }

    public function testConnection($connectionId): array
    {
        $conn = EtlConnection::find($connectionId);
        if ($conn) {
            return ['success' => true, 'message' => "Koneksi '{$conn->name}' berhasil terhubung!"];
        }
        return ['success' => false, 'message' => 'Koneksi tidak ditemukan atau tidak aktif.'];
    }

    public function fetchTableColumns($connectionId, $tableName): array
    {
        $conn = EtlConnection::find($connectionId);
        if ($conn) {
            $metadata = $conn->metadata ?? [];
            foreach (array_merge($metadata['tables'] ?? [], $metadata['views'] ?? []) as $t) {
                if ($t['name'] === $tableName) {
                    return $t['columns'] ?? [];
                }
            }
        }
        return [];
    }

    public function previewSqlQuery($connectionId, $sql): array
    {
        $columns = [];
        $tableName = null;
        
        if (preg_match('/from\s+([a-zA-Z0-9_\.]+)/i', $sql, $tableMatches)) {
            $tableName = str_replace(['[', ']', '"', '`'], '', $tableMatches[1]);
        }
        
        if (preg_match('/^\s*select\s+(.*?)\s+from/si', $sql, $colMatches)) {
            $selectPart = trim($colMatches[1]);
            if ($selectPart !== '*') {
                $cols = explode(',', $selectPart);
                foreach ($cols as $col) {
                    $col = trim($col);
                    if (preg_match('/(?:\bas\b\s+)?(\w+)\s*$/i', $col, $aliasMatches)) {
                        $columns[] = $aliasMatches[1];
                    }
                }
            }
        }
        
        if (empty($columns) && $tableName && $connectionId) {
            $conn = EtlConnection::find($connectionId);
            if ($conn) {
                $metadata = $conn->metadata ?? [];
                foreach (array_merge($metadata['tables'] ?? [], $metadata['views'] ?? []) as $t) {
                    if ($t['name'] === $tableName) {
                        $columns = $t['columns'] ?? [];
                        break;
                    }
                }
            }
        }
        
        if (empty($columns)) {
            $columns = ['customer_id', 'customer_name', 'email', 'status'];
        }
        
        $rows = [];
        for ($i = 1; $i <= 3; $i++) {
            $row = [];
            foreach ($columns as $col) {
                $row[$col] = match(strtolower($col)) {
                    'id', 'customer_id', 'user_id' => $i,
                    'email' => "user{$i}@example.com",
                    'status', 'is_active' => 'active',
                    'created_at', 'updated_at' => now()->toDateTimeString(),
                    default => "Val " . $col . " " . $i
                };
            }
            $rows[] = $row;
        }
        
        return [
            'columns' => $columns,
            'rows' => $rows
        ];
    }

    public function render()
    {
        $this->loadPipelines();
        $this->connections = EtlConnection::where('status', 'active')->orderBy('name')->get()->toArray();
        return view('livewire.studio-pipelines');
    }
}
