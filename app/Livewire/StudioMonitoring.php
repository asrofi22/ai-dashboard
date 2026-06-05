<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\StudioPipelineRun;

class StudioMonitoring extends Component
{
    public $selectedRunId = null;

    public function selectRun(int|null $id): void
    {
        $this->selectedRunId = $id;
    }

    public function render()
    {
        $runs = StudioPipelineRun::with('pipeline')
            ->orderBy('start_time', 'desc')
            ->get();

        $selectedRun = $this->selectedRunId ? StudioPipelineRun::with('pipeline')->find($this->selectedRunId) : null;

        // Statistics
        $totalRuns = count($runs);
        $successCount = $runs->where('status', 'Success')->count();
        $failedCount = $runs->where('status', 'Failed')->count();
        
        $successRate = $totalRuns > 0 ? round(($successCount / $totalRuns) * 100, 1) : 100.0;
        $avgDuration = $totalRuns > 0 ? round($runs->avg('duration_seconds')) : 0;
        
        $totalRowsRead = $runs->sum('rows_read');
        $totalRowsWritten = $runs->sum('rows_written');
        $totalRowsRejected = $runs->sum('rows_rejected');

        // Preparing chart datasets
        // We'll take the last 7 runs or aggregate by date
        $latestRuns = $runs->take(10)->reverse();
        $labels = [];
        $rowsRead = [];
        $rowsWritten = [];
        $rowsRejected = [];

        foreach ($latestRuns as $run) {
            $labels[] = substr($run->pipeline->name, 0, 12) . ' (#' . $run->id . ')';
            $rowsRead[] = $run->rows_read;
            $rowsWritten[] = $run->rows_written;
            $rowsRejected[] = $run->rows_rejected;
        }

        return view('livewire.studio-monitoring', [
            'runs' => $runs,
            'selectedRun' => $selectedRun,
            'successRate' => $successRate,
            'failedCount' => $failedCount,
            'avgDuration' => $avgDuration,
            'totalRowsRead' => $totalRowsRead,
            'totalRowsWritten' => $totalRowsWritten,
            'totalRowsRejected' => $totalRowsRejected,
            'labels' => json_encode($labels),
            'rowsRead' => json_encode($rowsRead),
            'rowsWritten' => json_encode($rowsWritten),
            'rowsRejected' => json_encode($rowsRejected)
        ]);
    }
}
