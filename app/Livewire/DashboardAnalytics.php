<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\DuplicateCandidate;
use App\Models\ImportedProject;
use App\Models\ImportLog;
use App\Models\WarehouseTable;
use App\Models\WarehouseColumn;
use App\Models\EtlPipeline;
use App\Models\EtlJobRun;
use App\Models\DataQualityRecommendation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardAnalytics extends Component
{
    public $batchId = ''; // Empty string means "All Batches"
    
    public int $totalProjects       = 0;
    public int $totalCandidates     = 0;
    public int $highConfidenceCount = 0;
    public float $duplicatePercentage = 0;
    public string $distributionLabels = '[]';
    public string $distributionData   = '[]';

    // New Data Platform KPIs
    public int $totalTables = 0;
    public int $totalRecords = 0;
    public float $dqScore = 95.0;
    public int $activePipelines = 0;
    public int $failedPipelines = 0;
    public int $missingRecords = 0;
    public int $duplicateRecords = 0;
    public int $aiInsightsGenerated = 0;

    // Charts Data
    public string $dqTrendLabels = '[]';
    public string $dqTrendData = '[]';
    public string $etlSuccessData = '[]';
    public array $topIssues = [];
    public array $recentInsights = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    #[On('import-completed')]
    public function refresh(): void
    {
        $this->loadStats();
    }

    public function updatedBatchId(): void
    {
        $this->loadStats();
        $this->dispatch('batch-filter-updated', batchId: $this->batchId);
    }

    private function loadStats(): void
    {
        // 1. Existing Project stats
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

        // Existing Distribution buckets
        $distribution = [
            '0.5 - 0.6' => (clone $candidateQuery)->whereBetween('similarity_score', [0.50, 0.599])->count(),
            '0.6 - 0.7' => (clone $candidateQuery)->whereBetween('similarity_score', [0.60, 0.699])->count(),
            '0.7 - 0.8' => (clone $candidateQuery)->whereBetween('similarity_score', [0.70, 0.799])->count(),
            '0.8 - 0.9' => (clone $candidateQuery)->whereBetween('similarity_score', [0.80, 0.899])->count(),
            '0.9 - 1.0' => (clone $candidateQuery)->whereBetween('similarity_score', [0.90, 1.00])->count(),
        ];

        $this->distributionLabels = json_encode(array_keys($distribution));
        $this->distributionData   = json_encode(array_values($distribution));

        // 2. New Data Platform indicators
        $this->totalTables = WarehouseTable::count();
        
        $dwh_records = WarehouseTable::sum('row_count') ?? 0;
        $this->totalRecords = $dwh_records + ImportedProject::count();

        $avg_score = WarehouseTable::avg('quality_score');
        $this->dqScore = $avg_score ? round(floatval($avg_score), 1) : 95.0;

        $this->activePipelines = EtlPipeline::where('is_active', 'active')->count();
        $this->failedPipelines = EtlJobRun::where('status', 'Failed')->count();

        // Calculate missing values
        $this->missingRecords = 0;
        $columns = WarehouseColumn::with('table')->get();
        foreach ($columns as $col) {
            if ($col->missing_percentage > 0 && $col->table) {
                $this->missingRecords += intval((floatval($col->missing_percentage) / 100.0) * $col->table->row_count);
            }
        }

        $this->duplicateRecords = DuplicateCandidate::where('status', 'confirmed')->count();

        $ai_recs = DataQualityRecommendation::count();
        $ai_dups = DuplicateCandidate::where('ai_validation_status', 'validated')->count();
        $ai_failures = EtlJobRun::whereNotNull('ai_failure_analysis')->count();
        $this->aiInsightsGenerated = $ai_recs + $ai_dups + $ai_failures;

        // Data Quality Trend (Simulation data for last 7 days)
        $trendDates = [];
        $trendValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('d M');
            $trendDates[] = $date;
            // Introduce slight variation around the current score
            $trendValues[] = round($this->dqScore - ($i * 0.15) + ($i % 2 == 0 ? 0.8 : -0.4), 1);
        }
        $this->dqTrendLabels = json_encode($trendDates);
        $this->dqTrendData = json_encode($trendValues);

        // ETL success rates
        $success = EtlJobRun::where('status', 'Success')->count();
        $failed = EtlJobRun::where('status', 'Failed')->count();
        $warning = EtlJobRun::where('status', 'Warning')->count();
        $this->etlSuccessData = json_encode([$success, $failed, $warning]);

        // Top Data Quality Issues list
        $this->topIssues = DataQualityRecommendation::orderBy('priority_level', 'desc')
            ->limit(4)
            ->get()
            ->toArray();

        // Recent AI Insights Feed
        $feed = [];
        $recs = DataQualityRecommendation::orderBy('created_at', 'desc')->limit(2)->get();
        foreach ($recs as $r) {
            $feed[] = [
                'type' => 'Quality recommendation',
                'title' => 'Rekomendasi DQ: ' . $r->table_name,
                'message' => $r->finding_summary,
                'time' => $r->created_at->diffForHumans()
            ];
        }
        $failures = EtlJobRun::where('status', 'Failed')->orderBy('start_time', 'desc')->limit(2)->get();
        foreach ($failures as $f) {
            if ($f->ai_failure_analysis) {
                $feed[] = [
                    'type' => 'Pipeline failure diagnostics',
                    'title' => 'Kegagalan Job: ' . $f->pipeline->name,
                    'message' => $f->ai_failure_analysis['root_cause'],
                    'time' => $f->start_time->diffForHumans()
                ];
            }
        }
        $this->recentInsights = $feed;
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
