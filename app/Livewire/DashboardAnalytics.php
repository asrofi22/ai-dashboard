<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\DuplicateCandidate;
use App\Models\ImportedProject;
use App\Models\ImportLog;
use Illuminate\Support\Facades\DB;

class DashboardAnalytics extends Component
{
    public function render()
    {
        $totalProjects = ImportedProject::count();
        $totalCandidates = DuplicateCandidate::count();
        $highConfidenceCount = DuplicateCandidate::where('confidence_level', 'high')->count();
        
        $duplicatePercentage = $totalProjects > 0 ? round(($totalCandidates / $totalProjects) * 100, 1) : 0;

        // Similarity distribution for Chart.js
        // Groups: 0.6-0.7, 0.7-0.8, 0.8-0.9, 0.9-1.0
        $distribution = [
            '0.6 - 0.7' => DuplicateCandidate::whereBetween('similarity_score', [0.6, 0.699])->count(),
            '0.7 - 0.8' => DuplicateCandidate::whereBetween('similarity_score', [0.7, 0.799])->count(),
            '0.8 - 0.9' => DuplicateCandidate::whereBetween('similarity_score', [0.8, 0.899])->count(),
            '0.9 - 1.0' => DuplicateCandidate::whereBetween('similarity_score', [0.9, 1.0])->count(),
        ];

        return view('livewire.dashboard-analytics', [
            'totalProjects' => $totalProjects,
            'totalCandidates' => $totalCandidates,
            'highConfidenceCount' => $highConfidenceCount,
            'duplicatePercentage' => $duplicatePercentage,
            'distributionLabels' => json_encode(array_keys($distribution)),
            'distributionData' => json_encode(array_values($distribution)),
        ]);
    }
}
