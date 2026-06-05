<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\StudioPipeline;
use App\Models\StudioPipelineRun;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StudioRuns extends Component
{
    public $pipelines = [];
    public $runs = [];
    public $selectedRunId = null;

    // Running states (controlled via Livewire + Alpine)
    public $runningPipelineId = null;
    public $activeRunId = null;
    public bool $isAnalyzing = false;

    public function mount(): void
    {
        // Loaded dynamically in render()
    }

    public function loadData(): void
    {
        $this->pipelines = StudioPipeline::with(['sourceConnection', 'targetConnection'])
            ->where('is_active', 'active')
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->runs = StudioPipelineRun::with('pipeline')
            ->orderBy('start_time', 'desc')
            ->get()
            ->toArray();
    }

    public function startRun(int $pipelineId): void
    {
        $pipeline = StudioPipeline::findOrFail($pipelineId);

        // Create a running record in the database
        $run = StudioPipelineRun::create([
            'pipeline_id' => $pipeline->id,
            'status' => 'Running',
            'start_time' => Carbon::now(),
            'rows_read' => 0,
            'rows_written' => 0,
            'rows_rejected' => 0,
            'execution_logs' => "INFO - Memulai inisialisasi eksekusi ETL untuk pipeline '{$pipeline->name}'...\n"
        ]);

        $this->activeRunId = $run->id;
        $this->runningPipelineId = $pipelineId;

        // Determine if this run will succeed or fail (80% success, 20% fail)
        $willSucceed = rand(1, 100) <= 80;

        // Dispatch browser event to kick off Alpine progress and log simulator
        $this->dispatch('start-execution-simulation', [
            'runId' => $run->id,
            'pipelineName' => $pipeline->name,
            'sourceDriver' => $pipeline->sourceConnection->driver,
            'targetDriver' => $pipeline->targetConnection->driver,
            'sourceTable' => $pipeline->source_table,
            'targetTable' => $pipeline->target_table,
            'transformations' => $pipeline->transformations ?? [],
            'willSucceed' => $willSucceed
        ]);
    }

    public function completeRunSuccess(int $runId, string $logs, int $read, int $written, int $rejected): void
    {
        try {
            $run = StudioPipelineRun::findOrFail($runId);
            $run->update([
                'status' => 'Success',
                'end_time' => Carbon::now(),
                'duration_seconds' => Carbon::now()->diffInSeconds($run->start_time),
                'rows_read' => $read,
                'rows_written' => $written,
                'rows_rejected' => $rejected,
                'execution_logs' => $logs
            ]);

            $this->runningPipelineId = null;
            $this->activeRunId = null;
            $this->loadData();
            
            $this->dispatch('execution-completed', message: "Pipeline '{$run->pipeline->name}' sukses dijalankan! {$written} baris dimuat.");
        } catch (\Exception $e) {
            Log::error("StudioRuns::completeRunSuccess error: " . $e->getMessage());
        }
    }

    public function completeRunFailed(int $runId, string $logs, string $errorLog): void
    {
        try {
            $run = StudioPipelineRun::findOrFail($runId);
            $run->update([
                'status' => 'Failed',
                'end_time' => Carbon::now(),
                'duration_seconds' => Carbon::now()->diffInSeconds($run->start_time),
                'rows_read' => rand(100, 500),
                'rows_written' => 0,
                'rows_rejected' => rand(10, 50),
                'execution_logs' => $logs,
                'error_log' => $errorLog
            ]);

            $this->runningPipelineId = null;
            $this->activeRunId = null;
            $this->selectedRunId = $run->id; // Open detail panel immediately for diagnostics
            $this->loadData();

            // Auto-trigger failure analysis
            $this->analyzeFailure($run->id);

            $this->dispatch('execution-completed', message: "❌ Pipeline '{$run->pipeline->name}' gagal dieksekusi. Memulai analisis diagnosis AI...");
        } catch (\Exception $e) {
            Log::error("StudioRuns::completeRunFailed error: " . $e->getMessage());
        }
    }

    public function selectRun(int|null $id): void
    {
        $this->selectedRunId = $id;
    }

    public function analyzeFailure(int $runId): void
    {
        $this->isAnalyzing = true;

        try {
            $run = StudioPipelineRun::findOrFail($runId);
            if ($run->status === 'Failed' && !$run->ai_failure_analysis) {
                $gemini = app(GeminiService::class);
                $analysis = $gemini->analyzeStudioFailure($run->pipeline->name, $run->error_log ?? 'Unknown connection timeout.');

                if ($analysis) {
                    $run->ai_failure_analysis = $analysis;
                    $run->save();
                }
            }
            $this->selectedRunId = $runId;
            $this->loadData();
        } catch (\Exception $e) {
            Log::error("StudioRuns::analyzeFailure error: " . $e->getMessage());
        }

        $this->isAnalyzing = false;
    }

    public function render()
    {
        $this->loadData();
        return view('livewire.studio-runs');
    }
}
