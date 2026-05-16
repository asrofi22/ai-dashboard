<?php

namespace App\Services;

use App\Models\DuplicateCandidate;
use App\Models\ImportedProject;
use App\Models\ImportLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuplicateDetectionService
{
    /**
     * Detect duplicates for a specific import log against all other projects.
     */
    public function detectForLog(int $importLogId, float $threshold = 0.6)
    {
        // First, configure pg_trgm limit if needed (default is 0.3, we can set local threshold)
        DB::statement('SET pg_trgm.similarity_threshold = ' . (float)$threshold);

        $log = ImportLog::findOrFail($importLogId);
        
        // Find similarities where one of the projects is from this import log.
        // We use similarity() from pg_trgm.
        
        $sql = "
            SELECT 
                p1.id as project_a_id, 
                p2.id as project_b_id, 
                similarity(p1.normalized_name, p2.normalized_name) as score
            FROM imported_projects p1
            JOIN imported_projects p2 
              ON p1.id != p2.id 
              AND p1.normalized_name % p2.normalized_name -- the % operator uses the similarity_threshold
            WHERE p1.import_log_id = ?
              AND p1.id < p2.id -- To avoid A-B and B-A duplicates
        ";

        $results = DB::select($sql, [$importLogId]);

        $count = 0;
        foreach ($results as $row) {
            // Determine confidence level loosely based on score
            $confidence = 'medium';
            if ($row->score >= 0.85) {
                $confidence = 'high';
            } elseif ($row->score <= 0.70) {
                $confidence = 'low';
            }

            // Create or update candidate
            DuplicateCandidate::updateOrCreate([
                'project_a_id' => $row->project_a_id,
                'project_b_id' => $row->project_b_id,
            ], [
                'similarity_score' => $row->score,
                'confidence_level' => $confidence,
                'status' => 'pending',
                'ai_validation_status' => 'pending',
            ]);
            $count++;
        }

        Log::info("Duplicate detection for log {$importLogId} completed. Found {$count} candidates.");
        
        return $count;
    }
}
