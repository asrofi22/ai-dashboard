<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\DuplicateCandidate;
use App\Models\ImportedProject;
use App\Models\ImportLog;

class DashboardAnalytics extends Component
{
    public $batchId = ''; // Empty string means "All Batches"
    
    public int $totalProjects       = 0;
    public int $totalCandidates     = 0;
    public int $highConfidenceCount = 0;
    public float $duplicatePercentage = 0;
    public string $distributionLabels = '[]';
    public string $distributionData   = '[]';

    public function mount(): void
    {
        $this->loadStats();
    }

    /**
     * Listen for the 'import-completed' event dispatched by UploadManager
     * and reload all stats so the dashboard stays in sync.
     */
    #[On('import-completed')]
    public function refresh(): void
    {
        $this->loadStats();
    }

    public function updatedBatchId(): void
    {
        $this->loadStats();
        // Notify other components if needed, but for now we just refresh this view
        $this->dispatch('batch-filter-updated', batchId: $this->batchId);
    }

    private function loadStats(): void
    {
        $projectQuery = ImportedProject::query();
        $candidateQuery = DuplicateCandidate::query();

        if ($this->batchId) {
            $projectQuery->where('import_log_id', $this->batchId);
            $candidateQuery->where('import_log_id', $this->batchId);
        }

        $this->totalProjects       = $projectQuery->count();
        $this->totalCandidates     = $candidateQuery->count();
        $this->highConfidenceCount = (clone $candidateQuery)->where('confidence_level', 'high')->count();

        $this->duplicatePercentage = $this->totalProjects > 0
            ? round(($this->totalCandidates / $this->totalProjects) * 100, 1)
            : 0;

        // Distribution buckets
        $distribution = [
            '0.5 - 0.6' => (clone $candidateQuery)->whereBetween('similarity_score', [0.50, 0.599])->count(),
            '0.6 - 0.7' => (clone $candidateQuery)->whereBetween('similarity_score', [0.60, 0.699])->count(),
            '0.7 - 0.8' => (clone $candidateQuery)->whereBetween('similarity_score', [0.70, 0.799])->count(),
            '0.8 - 0.9' => (clone $candidateQuery)->whereBetween('similarity_score', [0.80, 0.899])->count(),
            '0.9 - 1.0' => (clone $candidateQuery)->whereBetween('similarity_score', [0.90, 1.00])->count(),
        ];

        $this->distributionLabels = json_encode(array_keys($distribution));
        $this->distributionData   = json_encode(array_values($distribution));
    }

    public function render()
    {
        $batches = ImportLog::with('sourceConnection')->orderBy('created_at', 'desc')->get();

        return view('livewire.dashboard-analytics', [
            'batches'             => $batches,
            'totalProjects'       => $this->totalProjects,
            'totalCandidates'     => $this->totalCandidates,
            'highConfidenceCount' => $this->highConfidenceCount,
            'duplicatePercentage' => $this->duplicatePercentage,
            'distributionLabels'  => $this->distributionLabels,
            'distributionData'    => $this->distributionData,
        ]);
    }
}
