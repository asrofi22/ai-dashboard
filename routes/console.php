<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Jobs\RunEtlPipelineJob;
use App\Models\StudioPipeline;
use Cron\CronExpression;
use Illuminate\Support\Facades\Log;

Schedule::call(function () {
    $pipelines = StudioPipeline::where('is_active', 'active')
        ->where('schedule_interval', '!=', 'manual')
        ->get();

    foreach ($pipelines as $pipeline) {
        $cronExpr = match ($pipeline->schedule_interval) {
            'hourly'  => '0 * * * *',
            'daily'   => '0 0 * * *',
            'weekly'  => '0 0 * * 0',
            'monthly' => '0 0 1 * *',
            default   => $pipeline->schedule_interval,
        };

        try {
            if (CronExpression::isValidExpression($cronExpr)) {
                $cron = new CronExpression($cronExpr);
                if ($cron->isDue()) {
                    dispatch(new RunEtlPipelineJob($pipeline->id));
                    Log::info("Scheduled job dispatched for ETL Pipeline ID {$pipeline->id} ({$pipeline->name}) using cron: {$cronExpr}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to run scheduler for pipeline '{$pipeline->name}': " . $e->getMessage());
        }
    }
})->everyMinute();

