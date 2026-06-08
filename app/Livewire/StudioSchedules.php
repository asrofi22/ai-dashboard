<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\StudioPipeline;
use App\Models\StudioPipelineRun;
use App\Jobs\RunEtlPipelineJob;
use Cron\CronExpression;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StudioSchedules extends Component
{
    public $pipelines = [];
    public $selectedPipelineId = null;
    public $isEditModalOpen = false;

    // Form inputs
    public $scheduleMode = 'manual'; // manual, hourly, daily, weekly, monthly, custom
    public $hourlyMinutes = 0; // 0 (hourly), or minutes
    public $dailyTime = '00:00';
    public $weeklyDays = []; // array of day indexes (0-6, 0=Sunday)
    public $weeklyTime = '00:00';
    public $monthlyDay = 1;
    public $monthlyTime = '00:00';
    public $customCron = '*/15 * * * *';
    public $isActive = true;

    // Event listener mapping
    protected $listeners = ['refreshSchedules' => 'loadData'];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        // Don't reload if edit modal is open - preserve form state & avoid re-render disruption
        if ($this->isEditModalOpen) {
            return;
        }

        $this->pipelines = StudioPipeline::with(['sourceConnection', 'targetConnection', 'runs'])
            ->orderBy('name')
            ->get()
            ->map(function ($pipe) {
                $pipeArray = $pipe->toArray();
                $pipeArray['next_run'] = $this->calculateNextRun($pipe->schedule_interval, $pipe->is_active === 'active');
                $pipeArray['last_run'] = $pipe->runs()->orderBy('start_time', 'desc')->first()?->start_time?->toDateTimeString();
                return $pipeArray;
            })
            ->toArray();
    }

    public function togglePipelineActive(int $id): void
    {
        try {
            $pipe = StudioPipeline::findOrFail($id);
            $pipe->is_active = $pipe->is_active === 'active' ? 'inactive' : 'active';
            $pipe->save();

            $this->loadData();
            $this->dispatch('schedule-updated', message: "Status pipeline '{$pipe->name}' berhasil diubah.");
        } catch (\Exception $e) {
            Log::error("StudioSchedules::togglePipelineActive error: " . $e->getMessage());
        }
    }

    public function runNow(int $id): void
    {
        try {
            $pipe = StudioPipeline::findOrFail($id);
            dispatch(new RunEtlPipelineJob($pipe->id));

            $this->loadData();
            $this->dispatch('schedule-updated', message: "Pipeline '{$pipe->name}' berhasil dipicu untuk berjalan sekarang di background queue.");
        } catch (\Exception $e) {
            Log::error("StudioSchedules::runNow error: " . $e->getMessage());
        }
    }

    public function editSchedule(int $id): void
    {
        try {
            $pipe = StudioPipeline::findOrFail($id);
            $this->selectedPipelineId = $pipe->id;
            $interval = $pipe->schedule_interval;
            $this->isActive = $pipe->is_active === 'active';

            // Parse schedule_interval to form inputs
            $this->resetFormInputs();

            if ($interval === 'manual') {
                $this->scheduleMode = 'manual';
            } elseif ($interval === 'hourly' || $interval === '0 * * * *') {
                $this->scheduleMode = 'hourly';
            } elseif (str_starts_with($interval, '*/') && str_ends_with($interval, ' * * * *')) {
                $this->scheduleMode = 'hourly';
                // Extract minutes e.g. */15
                $this->hourlyMinutes = (int) str_replace(['*/', ' * * * *'], '', $interval);
            } elseif ($this->isDailyCron($interval)) {
                $this->scheduleMode = 'daily';
                $this->dailyTime = $this->parseTimeFromCron($interval);
            } elseif ($this->isWeeklyCron($interval)) {
                $this->scheduleMode = 'weekly';
                $parts = explode(' ', $interval);
                $this->weeklyTime = sprintf('%02d:%02d', $parts[1], $parts[0]);
                $this->weeklyDays = explode(',', $parts[4]);
            } elseif ($this->isMonthlyCron($interval)) {
                $this->scheduleMode = 'monthly';
                $parts = explode(' ', $interval);
                $this->monthlyTime = sprintf('%02d:%02d', $parts[1], $parts[0]);
                $this->monthlyDay = (int) $parts[2];
            } else {
                $this->scheduleMode = 'custom';
                $this->customCron = $interval;
            }

            $this->isEditModalOpen = true;
        } catch (\Exception $e) {
            Log::error("StudioSchedules::editSchedule error: " . $e->getMessage());
        }
    }

    public function saveSchedule(): void
    {
        if (!$this->selectedPipelineId) return;

        try {
            $pipe = StudioPipeline::findOrFail($this->selectedPipelineId);
            $interval = 'manual';

            if ($this->scheduleMode === 'hourly') {
                $interval = $this->hourlyMinutes > 0 ? "*/{$this->hourlyMinutes} * * * *" : '0 * * * *';
            } elseif ($this->scheduleMode === 'daily') {
                $parts = explode(':', $this->dailyTime);
                $min = (int)($parts[1] ?? 0);
                $hour = (int)($parts[0] ?? 0);
                $interval = "{$min} {$hour} * * *";
            } elseif ($this->scheduleMode === 'weekly') {
                $parts = explode(':', $this->weeklyTime);
                $min = (int)($parts[1] ?? 0);
                $hour = (int)($parts[0] ?? 0);
                $days = !empty($this->weeklyDays) ? implode(',', $this->weeklyDays) : '0';
                $interval = "{$min} {$hour} * * {$days}";
            } elseif ($this->scheduleMode === 'monthly') {
                $parts = explode(':', $this->monthlyTime);
                $min = (int)($parts[1] ?? 0);
                $hour = (int)($parts[0] ?? 0);
                $interval = "{$min} {$hour} {$this->monthlyDay} * *";
            } elseif ($this->scheduleMode === 'custom') {
                if (CronExpression::isValidExpression($this->customCron)) {
                    $interval = $this->customCron;
                } else {
                    $this->addError('customCron', 'Ekspresi CRON tidak valid.');
                    return;
                }
            }

            $pipe->schedule_interval = $interval;
            $pipe->is_active = $this->isActive ? 'active' : 'inactive';
            $pipe->save();

            $this->isEditModalOpen = false;
            $this->loadData();
            $this->dispatch('schedule-updated', message: "Penjadwalan untuk pipeline '{$pipe->name}' berhasil disimpan.");
        } catch (\Exception $e) {
            Log::error("StudioSchedules::saveSchedule error: " . $e->getMessage());
        }
    }

    protected function resetFormInputs(): void
    {
        $this->scheduleMode = 'manual';
        $this->hourlyMinutes = 0;
        $this->dailyTime = '00:00';
        $this->weeklyDays = [];
        $this->weeklyTime = '00:00';
        $this->monthlyDay = 1;
        $this->monthlyTime = '00:00';
        $this->customCron = '*/15 * * * *';
        $this->resetErrorBag();
    }

    protected function calculateNextRun(string $interval, bool $active): string
    {
        if (!$active || $interval === 'manual') {
            return 'Tidak Aktif / Manual';
        }

        $cronExpr = match ($interval) {
            'hourly'  => '0 * * * *',
            'daily'   => '0 0 * * *',
            'weekly'  => '0 0 * * 0',
            'monthly' => '0 0 1 * *',
            default   => $interval,
        };

        try {
            if (CronExpression::isValidExpression($cronExpr)) {
                $cron = new CronExpression($cronExpr);
                // Use WIB timezone so next-run time reflects local Jakarta time
                $now = Carbon::now('Asia/Jakarta');
                return Carbon::instance($cron->getNextRunDate($now->toDateTime()))
                    ->setTimezone('Asia/Jakarta')
                    ->format('Y-m-d H:i:s');
            }
        } catch (\Exception $e) {
            return 'Ekspresi Cron Invalid';
        }

        return 'Manual';
    }

    public function getUpcomingTimelineProperty(): array
    {
        $timeline = [];
        $now   = Carbon::now('Asia/Jakarta');
        $limit = Carbon::now('Asia/Jakarta')->addHours(24);

        foreach ($this->pipelines as $pipe) {
            if ($pipe['is_active'] !== 'active' || $pipe['schedule_interval'] === 'manual') {
                continue;
            }

            $cronExpr = match ($pipe['schedule_interval']) {
                'hourly'  => '0 * * * *',
                'daily'   => '0 0 * * *',
                'weekly'  => '0 0 * * 0',
                'monthly' => '0 0 1 * *',
                default   => $pipe['schedule_interval'],
            };

            try {
                if (CronExpression::isValidExpression($cronExpr)) {
                    $cron    = new CronExpression($cronExpr);
                    $runDate = Carbon::now('Asia/Jakarta');
                    // Get up to 3 upcoming runs within the next 24 hours (WIB)
                    for ($i = 0; $i < 3; $i++) {
                        $runDate = Carbon::instance(
                            $cron->getNextRunDate($runDate->toDateTime())
                        )->setTimezone('Asia/Jakarta');

                        if ($runDate->greaterThan($limit)) {
                            break;
                        }
                        $timeline[] = [
                            'timestamp'     => $runDate->timestamp,
                            'time'          => $runDate->format('H:i'),
                            'date'          => $runDate->format('d M'),
                            'relative'      => $runDate->diffForHumans(),   // initial SSR value
                            'pipeline_name' => $pipe['name'],
                            'pipeline_id'   => $pipe['id'],
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Skip invalid cron expressions silently
            }
        }

        // Sort chronologically
        usort($timeline, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        // Limit to 8 items
        return array_slice($timeline, 0, 8);
    }

    public function getSummaryMetricsProperty(): array
    {
        $totalScheduled = 0;
        $activeSchedules = 0;
        $nextPipeline = 'N/A';
        $nextTime = null;

        foreach ($this->pipelines as $pipe) {
            if ($pipe['schedule_interval'] !== 'manual') {
                $totalScheduled++;
                if ($pipe['is_active'] === 'active') {
                    $activeSchedules++;

                    $nextRunStr = $pipe['next_run'];
                    if ($nextRunStr && $nextRunStr !== 'Ekspresi Cron Invalid' && $nextRunStr !== 'Tidak Aktif / Manual' && $nextRunStr !== 'Manual') {
                        $dt = Carbon::parse($nextRunStr, 'Asia/Jakarta');
                        if ($nextTime === null || $dt->lessThan($nextTime)) {
                            $nextTime     = $dt;
                            $nextPipeline = $pipe['name'];
                        }
                    }
                }
            }
        }

        return [
            'total_scheduled'     => $totalScheduled,
            'active_schedules'    => $activeSchedules,
            'next_pipeline'       => $nextPipeline,
            'next_time'           => $nextTime ? $nextTime->format('H:i') . ' WIB' : 'N/A',
            'next_time_ts'        => $nextTime ? $nextTime->timestamp : null,
            'next_time_formatted' => $nextTime ? $nextTime->format('H:i') . ' WIB' : '',
            'next_time_relative'  => $nextTime ? $nextTime->diffForHumans() : '',
        ];
    }

    // Cron parsing helpers
    protected function isDailyCron(string $cron): bool
    {
        // format: min hour * * *
        $parts = explode(' ', $cron);
        return count($parts) === 5 && $parts[2] === '*' && $parts[3] === '*' && $parts[4] === '*';
    }

    protected function parseTimeFromCron(string $cron): string
    {
        $parts = explode(' ', $cron);
        return sprintf('%02d:%02d', (int)($parts[1] ?? 0), (int)($parts[0] ?? 0));
    }

    protected function isWeeklyCron(string $cron): bool
    {
        // format: min hour * * days
        $parts = explode(' ', $cron);
        return count($parts) === 5 && $parts[2] === '*' && $parts[3] === '*' && $parts[4] !== '*';
    }

    protected function isMonthlyCron(string $cron): bool
    {
        // format: min hour day * *
        $parts = explode(' ', $cron);
        return count($parts) === 5 && $parts[2] !== '*' && $parts[3] === '*' && $parts[4] === '*';
    }

    public function render()
    {
        return view('livewire.studio-schedules');
    }
}
