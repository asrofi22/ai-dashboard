<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Models\EtlConnection;
use App\Models\StudioPipeline;
use App\Models\StudioPipelineRun;
use App\Jobs\RunEtlPipelineJob;
use Illuminate\Support\Facades\Queue;

class StudioSchedulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup mock connection context
        $this->conn1 = EtlConnection::create([
            'name' => 'Src DB', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);
        $this->conn2 = EtlConnection::create([
            'name' => 'Tgt DB', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);
    }

    public function test_schedules_page_loads_pipelines()
    {
        $pipe = StudioPipeline::create([
            'name' => 'Daily Sync Customer',
            'source_connection_id' => $this->conn1->id,
            'source_table' => 's',
            'target_connection_id' => $this->conn2->id,
            'target_table' => 'd',
            'transformations' => [],
            'column_mapping' => [],
            'schedule_interval' => 'daily',
            'is_active' => 'active'
        ]);

        Livewire::test(\App\Livewire\StudioSchedules::class)
            ->assertSee('Daily Sync Customer')
            ->assertSee('Daily')
            ->assertSet('pipelines.0.name', 'Daily Sync Customer');
    }

    public function test_schedules_can_toggle_pipeline_active_state()
    {
        $pipe = StudioPipeline::create([
            'name' => 'Toggle Sync Pipe',
            'source_connection_id' => $this->conn1->id,
            'source_table' => 's',
            'target_connection_id' => $this->conn2->id,
            'target_table' => 'd',
            'transformations' => [],
            'column_mapping' => [],
            'schedule_interval' => 'manual',
            'is_active' => 'active'
        ]);

        Livewire::test(\App\Livewire\StudioSchedules::class)
            ->call('togglePipelineActive', $pipe->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('studio_pipelines', [
            'id' => $pipe->id,
            'is_active' => 'inactive'
        ]);
    }

    public function test_scheduler_can_run_pipeline_immediately()
    {
        Queue::fake();

        $pipe = StudioPipeline::create([
            'name' => 'Immediate Run Pipe',
            'source_connection_id' => $this->conn1->id,
            'source_table' => 's',
            'target_connection_id' => $this->conn2->id,
            'target_table' => 'd',
            'transformations' => [],
            'column_mapping' => [],
            'schedule_interval' => 'manual',
            'is_active' => 'active'
        ]);

        Livewire::test(\App\Livewire\StudioSchedules::class)
            ->call('runNow', $pipe->id)
            ->assertHasNoErrors();

        Queue::assertPushed(RunEtlPipelineJob::class, function ($job) use ($pipe) {
            return $job->pipelineId === $pipe->id;
        });
    }

    public function test_scheduler_can_edit_and_save_schedule_config()
    {
        $pipe = StudioPipeline::create([
            'name' => 'Editable Schedule Pipe',
            'source_connection_id' => $this->conn1->id,
            'source_table' => 's',
            'target_connection_id' => $this->conn2->id,
            'target_table' => 'd',
            'transformations' => [],
            'column_mapping' => [],
            'schedule_interval' => 'manual',
            'is_active' => 'active'
        ]);

        Livewire::test(\App\Livewire\StudioSchedules::class)
            ->call('editSchedule', $pipe->id)
            ->assertSet('selectedPipelineId', $pipe->id)
            ->assertSet('isEditModalOpen', true)
            ->set('scheduleMode', 'daily')
            ->set('dailyTime', '04:30')
            ->set('isActive', true)
            ->call('saveSchedule')
            ->assertSet('isEditModalOpen', false)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('studio_pipelines', [
            'id' => $pipe->id,
            'schedule_interval' => '30 4 * * *',
            'is_active' => 'active'
        ]);
    }

    public function test_application_timezone_is_wib()
    {
        $this->assertEquals('Asia/Jakarta', config('app.timezone'));
    }
}
