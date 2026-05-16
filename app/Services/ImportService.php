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
     */
    public function processImport(int $sourceConnectionId, array $data): ImportLog
    {
        $source = SourceConnection::findOrFail($sourceConnectionId);
        
        $log = ImportLog::create([
            'source_connection_id' => $source->id,
            'status' => 'processing',
            'total_records' => count($data),
        ]);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($data as $index => $row) {
                // Determine 'name' and 'external_id' depending on the row structure.
                // Assuming standard keys: 'name', 'project_name', 'id', 'project_code'
                $originalName = $row['name'] ?? $row['project_name'] ?? $row[1] ?? null;
                $externalId = $row['id'] ?? $row['project_code'] ?? $row['external_id'] ?? $row[0] ?? null;

                if (empty($originalName)) {
                    $failedCount++;
                    $errors[] = "Row {$index}: Missing project name.";
                    continue;
                }

                $normalizedName = $this->cleaningService->normalize($originalName);

                ImportedProject::create([
                    'source_connection_id' => $source->id,
                    'import_log_id' => $log->id,
                    'external_id' => $externalId,
                    'original_name' => $originalName,
                    'normalized_name' => $normalizedName,
                    'metadata' => $row, // store full row as metadata
                ]);

                $successCount++;
            }

            $log->update([
                'status' => 'completed',
                'success_records' => $successCount,
                'failed_records' => $failedCount,
                'error_details' => count($errors) > 0 ? implode("\n", array_slice($errors, 0, 10)) : null,
            ]);

            $source->update(['last_sync_at' => now()]);

            DB::commit();

            // Fire off Duplicate Detection Job here if needed
            // DuplicateDetectionService::detectForLog($log->id);

        } catch (Exception $e) {
            DB::rollBack();
            $log->update([
                'status' => 'failed',
                'error_details' => $e->getMessage(),
            ]);
            Log::error("Import failed: " . $e->getMessage());
        }

        return $log;
    }
}
