<?php

namespace App\Jobs;

use App\Models\ImportLog;
use App\Services\DuplicateDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DetectDuplicatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of seconds the job can run before timing out.
     */
    public int $timeout = 600;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 1;

    public function __construct(public int $importLogId)
    {
    }

    public function handle(DuplicateDetectionService $service): void
    {
        // Override PHP time limit — needed for large datasets in sync mode
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        Log::info("DetectDuplicatesJob started for importLogId: {$this->importLogId}");
        $count = $service->detectForLog($this->importLogId);
        Log::info("DetectDuplicatesJob finished. Found {$count} candidates.");

        // Update import log status to reflect completion
        $log = ImportLog::find($this->importLogId);
        if ($log) {
            $log->update(['status' => 'completed']);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("DetectDuplicatesJob failed for importLogId: {$this->importLogId}", [
            'error' => $exception->getMessage(),
        ]);

        $log = ImportLog::find($this->importLogId);
        if ($log) {
            $log->update(['status' => 'failed', 'error_details' => $exception->getMessage()]);
        }
    }
}
