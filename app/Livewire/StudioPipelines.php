<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\StudioPipeline;
use App\Models\EtlConnection;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

class StudioPipelines extends Component
{
    public $pipelines = [];
    public $connections = [];
    public bool $showModal = false;
    public bool $isEditing = false;
    public $selectedPipelineId = null;

    // Form fields
    public string $name = '';
    public $sourceConnectionId = null;
    public string $sourceTable = '';
    public array $selectedTransformations = [];
    public $targetConnectionId = null;
    public string $targetTable = '';
    public array $columnMappings = []; // Array of ['source' => '', 'target' => '']
    public string $isActive = 'active';

    // Dynamic Lists based on dropdown selections
    public array $sourceTables = [];
    public array $targetTables = [];
    public array $sourceColumns = [];
    public array $targetColumns = [];
    public bool $isMappingLoading = false;

    public array $availableTransformations = [
        'Remove Duplicate',
        'Remove Null',
        'Trim Text',
        'Uppercase',
        'Lowercase',
        'Rename Column',
        'Data Type Conversion',
        'Filter Data',
        'Custom SQL'
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

    public function openCreate(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
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

        $this->isEditing = true;
        $this->showModal = true;
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
        $this->sourceTables = [];
        $this->targetTables = [];
        $this->sourceColumns = [];
        $this->targetColumns = [];
        $this->selectedPipelineId = null;
    }

    public function save(): void
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'source_connection_id' => $this->sourceConnectionId,
                'source_table' => $this->sourceTable,
                'transformations' => $this->selectedTransformations,
                'target_connection_id' => $this->targetConnectionId,
                'target_table' => $this->targetTable,
                'column_mapping' => $this->columnMappings,
                'is_active' => $this->isActive
            ];

            if ($this->isEditing) {
                $pipe = StudioPipeline::findOrFail($this->selectedPipelineId);
                $pipe->update($data);
                session()->flash('message', "Pipeline '{$this->name}' berhasil diperbarui.");
            } else {
                StudioPipeline::create($data);
                session()->flash('message', "Pipeline '{$this->name}' berhasil dibuat.");
            }

            $this->showModal = false;
            $this->loadPipelines();
        } catch (\Exception $e) {
            Log::error("StudioPipelines::save error: " . $e->getMessage());
            session()->flash('error', "Gagal menyimpan pipeline: " . $e->getMessage());
        }
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

    public function render()
    {
        $this->loadPipelines();
        $this->connections = EtlConnection::where('status', 'active')->orderBy('name')->get()->toArray();
        return view('livewire.studio-pipelines');
    }
}
