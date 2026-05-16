<?php

namespace App\Services;

use App\Models\ImportLog;
use App\Models\ImportedProject;
use App\Models\SourceConnection;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportService
{
    protected CleaningService $cleaningService;

    public function __construct(CleaningService $cleaningService)
    {
        $this->cleaningService = $cleaningService;
    }

    /**
     * Process imported data from an array format (e.g. from Excel/CSV upload).
     * Uses chunked bulk inserts for performance on large datasets.
     */
    public function processImport(int $sourceConnectionId, array $data): ImportLog
    {
        $source = SourceConnection::findOrFail($sourceConnectionId);

        $log = ImportLog::create([
            'source_connection_id' => $source->id,
            'status'               => 'processing',
            'total_records'        => count($data),
        ]);

        $successCount = 0;
        $failedCount  = 0;
        $errors       = [];
        $batch        = [];
        $now          = now()->toDateTimeString();
        $chunkSize    = 200;

        try {
            foreach ($data as $index => $row) {
                $originalName = $row['name']
                    ?? $row['project_name']
                    ?? $row[1]
                    ?? null;

                $externalId = $row['id']
                    ?? $row['project_code']
                    ?? $row['external_id']
                    ?? $row[0]
                    ?? null;

                if (empty($originalName)) {
                    $failedCount++;
                    $errors[] = "Row {$index}: nama proyek kosong.";
                    continue;
                }

                $normalizedName = $this->cleaningService->normalize($originalName);

                $batch[] = [
                    'source_connection_id' => $source->id,
                    'import_log_id'        => $log->id,
                    'external_id'          => (string) $externalId,
                    'original_name'        => $originalName,
                    'normalized_name'      => $normalizedName,
                    'metadata'             => json_encode($row),
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];

                $successCount++;

                // Flush every $chunkSize rows to avoid memory issues
                if (count($batch) >= $chunkSize) {
                    DB::table('imported_projects')->insert($batch);
                    $batch = [];
                }
            }

            // Flush remaining rows
            if (!empty($batch)) {
                DB::table('imported_projects')->insert($batch);
            }

            $log->update([
                'status'         => 'completed',
                'success_records' => $successCount,
                'failed_records'  => $failedCount,
                'error_details'   => count($errors) > 0
                    ? implode("\n", array_slice($errors, 0, 10))
                    : null,
            ]);

            $source->update(['last_sync_at' => now()]);

        } catch (Exception $e) {
            $log->update([
                'status'        => 'failed',
                'error_details' => $e->getMessage(),
            ]);
            Log::error("Import failed: " . $e->getMessage());
        }

        return $log->fresh();
    }
}
