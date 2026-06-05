<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\EtlPipeline;
use App\Models\EtlJobRun;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EtlMonitoring extends Component
{
    public int|null $selectedRunId = null;
    public int|null $selectedPipelineId = null;
    public bool $isAnalyzing = false;
    public bool $isRunningJob = false;

    // AI Pipeline creation state
    public string $newPipelinePrompt = '';
    public bool $isGeneratingPipeline = false;
    public bool $showCreateModal = false;

    public function selectRun(int $id): void
    {
        $this->selectedRunId = $id;
    }

    public function selectPipeline(int|null $id): void
    {
        $this->selectedPipelineId = $id;
        // If we select a pipeline, close any run selection to avoid layout clutter
        if ($id) {
            $this->selectedRunId = null;
        }
    }

    public function toggleCreateModal(): void
    {
        $this->showCreateModal = !$this->showCreateModal;
        $this->newPipelinePrompt = '';
    }

    public function generatePipelineWithAi(): void
    {
        $this->validate([
            'newPipelinePrompt' => 'required|min:10'
        ], [
            'newPipelinePrompt.required' => 'Instruksi pipeline wajib diisi.',
            'newPipelinePrompt.min' => 'Instruksi minimal harus 10 karakter agar AI bisa merancang dengan baik.'
        ]);

        $this->isGeneratingPipeline = true;

        try {
            $gemini = app(GeminiService::class);
            $result = $gemini->generateEtlPipeline($this->newPipelinePrompt);

            if ($result && !empty($result['pipeline_name'])) {
                $pipeline = EtlPipeline::create([
                    'name' => $result['pipeline_name'],
                    'source_layer' => $result['source_layer'] ?? 'Unknown Source',
                    'target_layer' => $result['target_layer'] ?? 'Unknown Target',
                    'frequency' => $result['frequency'] ?? 'Daily',
                    'is_active' => 'active',
                    'definition_prompt' => $this->newPipelinePrompt,
                    'generated_script' => $result['generated_script'] ?? '# No script generated'
                ]);

                $this->selectedPipelineId = $pipeline->id;
                $this->showCreateModal = false;
                $this->newPipelinePrompt = '';

                $this->dispatch('pipeline-created', message: "Pipeline '{$pipeline->name}' berhasil dirancang dan dibuat oleh AI!");
            } else {
                session()->flash('generation_error', 'Gagal merancang pipeline. Coba ubah atau perjelas instruksi Anda.');
            }
        } catch (\Exception $e) {
            Log::error("EtlMonitoring::generatePipelineWithAi error: " . $e->getMessage());
            session()->flash('generation_error', 'Terjadi kesalahan sistem saat menghubungi AI: ' . $e->getMessage());
        }

        $this->isGeneratingPipeline = false;
    }

    public function deletePipeline(int $id): void
    {
        try {
            $pipeline = EtlPipeline::findOrFail($id);
            $name = $pipeline->name;
            $pipeline->delete();

            if ($this->selectedPipelineId === $id) {
                $this->selectedPipelineId = null;
            }

            $this->dispatch('pipeline-deleted', message: "Pipeline {$name} berhasil dihapus.");
        } catch (\Exception $e) {
            Log::error("EtlMonitoring::deletePipeline error: " . $e->getMessage());
        }
    }

    public function triggerMockJob(int $pipelineId): void
    {
        $this->isRunningJob = true;
        
        try {
            $pipeline = EtlPipeline::findOrFail($pipelineId);
            
            // Randomly succeed or fail
            // 70% chance of success, 30% chance of failure
            $succeeds = rand(1, 100) <= 70;
            
            $startTime = Carbon::now();
            $duration = rand(30, 300); // 30s to 5m
            
            if ($succeeds) {
                EtlJobRun::create([
                    'pipeline_id' => $pipeline->id,
                    'status' => 'Success',
                    'start_time' => $startTime,
                    'end_time' => $startTime->copy()->addSeconds($duration),
                    'duration_seconds' => $duration,
                    'rows_processed' => rand(100, 5000),
                    'error_message' => null,
                    'ai_failure_analysis' => null
                ]);
            } else {
                $errors = [
                    "Deadlock detected on resource update: Transaction (Process ID 54) was deadlocked on lock resources with another process.",
                    "Unique key constraint violation on table 'fact_sales': Duplicate key value violates unique constraint 'idx_sales_reference_id'.",
                    "Database Connection Timeout on Target Host 'ClickHouse DW' at 10.22.41.98:8123. Connection reset by peer.",
                    "Value too long for character type: Column 'customer_name' has length 255 but input length was 512."
                ];
                
                $errorMsg = $errors[array_rand($errors)];
                $run = EtlJobRun::create([
                    'pipeline_id' => $pipeline->id,
                    'status' => 'Failed',
                    'start_time' => $startTime,
                    'end_time' => $startTime->copy()->addSeconds($duration),
                    'duration_seconds' => $duration,
                    'rows_processed' => 0,
                    'error_message' => $errorMsg,
                    'ai_failure_analysis' => null
                ]);

                // Auto run Gemini analysis
                $this->analyzeFailure($run->id);
            }
        } catch (\Exception $e) {
            Log::error("EtlMonitoring::triggerMockJob error: " . $e->getMessage());
        }

        $this->isRunningJob = false;
    }

    public function analyzeFailure(int $runId): void
    {
        $this->isAnalyzing = true;
        
        try {
            $run = EtlJobRun::findOrFail($runId);
            if ($run->status === 'Failed' && !$run->ai_failure_analysis) {
                $gemini = app(GeminiService::class);
                $analysis = $gemini->analyzeEtlFailure($run->pipeline->name, $run->error_message);
                
                if ($analysis) {
                    $run->ai_failure_analysis = $analysis;
                    $run->save();
                }
            }
            $this->selectedRunId = $runId;
        } catch (\Exception $e) {
            Log::error("EtlMonitoring::analyzeFailure error: " . $e->getMessage());
        }

        $this->isAnalyzing = false;
    }

    public function fixJob(int $runId): void
    {
        try {
            $run = EtlJobRun::findOrFail($runId);
            if ($run->status === 'Failed') {
                $run->status = 'Success';
                $run->error_message = null;
                $run->rows_processed = rand(1000, 5000);
                $run->duration_seconds = rand(30, 120);
                
                $analysis = $run->ai_failure_analysis;
                if (is_array($analysis)) {
                    $analysis['root_cause'] = "TERSELESAIKAN: " . $analysis['root_cause'];
                    $analysis['recommendations'] = ["Patch Berhasil Diterapkan: Strategi UPSERT / Penanganan Konflik Kunci Unik diaktifkan."];
                }
                $run->ai_failure_analysis = $analysis;
                $run->save();

                $this->selectedRunId = null;
                $this->dispatch('job-fixed', message: "Pipeline {$run->pipeline->name} berhasil diperbaiki menggunakan patch strategi UPSERT.");
            }
        } catch (\Exception $e) {
            Log::error("EtlMonitoring::fixJob error: " . $e->getMessage());
        }
    }

    public function render()
    {
        $pipelines = EtlPipeline::orderBy('name')->get();
        $jobRuns = EtlJobRun::with('pipeline')->orderBy('start_time', 'desc')->get();
        $selectedRun = $this->selectedRunId ? EtlJobRun::with('pipeline')->find($this->selectedRunId) : null;
        $selectedPipeline = $this->selectedPipelineId ? EtlPipeline::find($this->selectedPipelineId) : null;

        // Calculate statistics
        $totalRuns = count($jobRuns);
        $successCount = $jobRuns->where('status', 'Success')->count();
        $failedCount = $jobRuns->where('status', 'Failed')->count();
        
        $successRate = $totalRuns > 0 ? round(($successCount / $totalRuns) * 100, 1) : 100.0;
        $avgDuration = $totalRuns > 0 ? round($jobRuns->avg('duration_seconds')) : 0;

        return view('livewire.etl-monitoring', [
            'pipelines' => $pipelines,
            'jobRuns' => $jobRuns,
            'selectedRun' => $selectedRun,
            'selectedPipeline' => $selectedPipeline,
            'successRate' => $successRate,
            'failedCount' => $failedCount,
            'avgDuration' => $avgDuration
        ]);
    }
}
