<?php

namespace App\Services;

use App\Models\DuplicateCandidate;
use App\Models\ImportLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuplicateDetectionService
{
    /**
     * Detect duplicates ONLY within a single import log batch.
     * Uses chunked processing to avoid PHP/DB timeouts on large datasets.
     *
     * @param  int    $importLogId
     * @param  float  $threshold   pg_trgm similarity threshold
     * @return int    Number of duplicate candidates created
     */
    public function detectForLog(int $importLogId, float $threshold = 0.5): int
    {
        $threshold = max(0.1, min(1.0, (float) $threshold));

        // Ensure pg_trgm extension is active
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        } catch (\Throwable $e) {
            // Already exists or no permission — continue
        }

        DB::statement('SET pg_trgm.similarity_threshold = ' . $threshold);

        // Set generous timeouts for this heavy query
        DB::statement('SET statement_timeout = 0');
        DB::statement('SET lock_timeout = 0');

        $log = ImportLog::findOrFail($importLogId);

        Log::info("DuplicateDetectionService: starting for log #{$importLogId}, threshold={$threshold}");

        $count = 0;

        /**
         * Chunked approach: get all project IDs in this log, then
         * for each chunk of IDs (as p1), find similar ones within the full log.
         * This breaks the NxN cross-join into smaller N/chunk × N pieces.
         */
        $projectIds = DB::table('imported_projects')
            ->where('import_log_id', $importLogId)
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        if (empty($projectIds)) {
            Log::warning("DuplicateDetectionService: no projects found for log #{$importLogId}");
            return 0;
        }

        $chunkSize   = 100;
        $totalIds    = count($projectIds);
        $insertBatch = [];

        // We process p1 in chunks; p2 spans the full log (filtered by p1.id < p2.id)
        foreach (array_chunk($projectIds, $chunkSize) as $chunkIndex => $idChunk) {
            $placeholders = implode(',', $idChunk);

            $sql = "
                SELECT
                    p1.id  AS project_a_id,
                    p2.id  AS project_b_id,
                    similarity(p1.normalized_name, p2.normalized_name) AS score
                FROM imported_projects p1
                JOIN imported_projects p2
                  ON p1.id < p2.id
                 AND p2.import_log_id = {$importLogId}
                 AND p1.normalized_name % p2.normalized_name
                WHERE p1.id IN ({$placeholders})
                  AND p1.import_log_id = {$importLogId}
            ";

            $results = DB::select($sql);

            foreach ($results as $row) {
                $confidence = match(true) {
                    $row->score >= 0.85 => 'high',
                    $row->score >= 0.70 => 'medium',
                    default             => 'low',
                };

                $insertBatch[] = [
                    'import_log_id'        => $importLogId,
                    'project_a_id'         => $row->project_a_id,
                    'project_b_id'         => $row->project_b_id,
                    'similarity_score'     => $row->score,
                    'confidence_level'     => $confidence,
                    'status'               => 'pending',
                    'ai_validation_status' => 'pending',
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ];

                $count++;

                // Flush candidate inserts every 200 rows
                if (count($insertBatch) >= 200) {
                    $this->upsertCandidates($insertBatch);
                    $insertBatch = [];
                }
            }

            Log::info("DuplicateDetectionService: chunk " . ($chunkIndex + 1) . " of " . ceil($totalIds / $chunkSize) . " done. Candidates so far: {$count}");
        }

        // Flush remaining
        if (!empty($insertBatch)) {
            $this->upsertCandidates($insertBatch);
        }

        Log::info("DuplicateDetectionService: log #{$importLogId} complete — {$count} candidates found.");

        return $count;
    }

    /**
     * Cross-log comparison — use this intentionally to compare two separate uploads.
     */
    public function detectBetweenLogs(int $logIdA, int $logIdB, float $threshold = 0.5): int
    {
        $threshold = max(0.1, min(1.0, (float) $threshold));
        DB::statement('SET pg_trgm.similarity_threshold = ' . $threshold);
        DB::statement('SET statement_timeout = 0');

        $sql = "
            SELECT
                p1.id  AS project_a_id,
                p2.id  AS project_b_id,
                similarity(p1.normalized_name, p2.normalized_name) AS score
            FROM imported_projects p1
            JOIN imported_projects p2
              ON p1.normalized_name % p2.normalized_name
            WHERE p1.import_log_id = :log_a
              AND p2.import_log_id = :log_b
        ";

        $results = DB::select($sql, ['log_a' => $logIdA, 'log_b' => $logIdB]);
        $batch   = [];
        $count   = 0;

        foreach ($results as $row) {
            $confidence = match(true) {
                $row->score >= 0.85 => 'high',
                $row->score >= 0.70 => 'medium',
                default             => 'low',
            };
            $batch[] = [
                'project_a_id'         => $row->project_a_id,
                'project_b_id'         => $row->project_b_id,
                'similarity_score'     => $row->score,
                'confidence_level'     => $confidence,
                'status'               => 'pending',
                'ai_validation_status' => 'pending',
                'created_at'           => now(),
                'updated_at'           => now(),
            ];
            $count++;
        }

        if (!empty($batch)) {
            $this->upsertCandidates($batch);
        }

        Log::info("DuplicateDetectionService: cross-log #{$logIdA}<->{$logIdB} — {$count} candidates.");
        return $count;
    }

    private function upsertCandidates(array $batch): void
    {
        // Use insertOrIgnore so duplicate pairs (if re-run) are safely skipped
        // This works even without a unique constraint, but adding one is recommended
        // via migration: 2026_05_16_120000_add_unique_constraint_to_duplicate_candidates.php
        DB::table('duplicate_candidates')->insertOrIgnore($batch);
    }
}
