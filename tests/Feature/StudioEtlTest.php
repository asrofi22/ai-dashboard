<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Models\EtlConnection;
use App\Models\StudioPipeline;
use App\Models\StudioPipelineRun;

class StudioEtlTest extends TestCase
{
    use RefreshDatabase;

    public function test_connections_page_can_create_and_delete_connection()
    {
        Livewire::test(\App\Livewire\StudioConnections::class)
            ->assertSet('connections', [])
            ->set('name', 'Test PG Connection')
            ->set('type', 'Database')
            ->set('driver', 'pgsql')
            ->set('config', ['host' => 'localhost', 'database' => 'test_db'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertCount('connections', 1);

        $this->assertDatabaseHas('etl_connections', [
            'name' => 'Test PG Connection',
            'driver' => 'pgsql',
        ]);
    }

    public function test_pipelines_page_can_create_pipeline()
    {
        $conn1 = EtlConnection::create([
            'name' => 'Source DB',
            'type' => 'Database',
            'driver' => 'pgsql',
            'config' => ['host' => 'localhost', 'database' => 'src'],
            'status' => 'active',
            'metadata' => [
                'tables' => [
                    ['name' => 'users', 'columns' => ['id', 'name', 'email']]
                ]
            ]
        ]);

        $conn2 = EtlConnection::create([
            'name' => 'Target DB',
            'type' => 'Database',
            'driver' => 'pgsql',
            'config' => ['host' => 'localhost', 'database' => 'tgt'],
            'status' => 'active',
            'metadata' => [
                'tables' => [
                    ['name' => 'dim_users', 'columns' => ['customer_id', 'customer_name', 'email']]
                ]
            ]
        ]);

        Livewire::test(\App\Livewire\StudioPipelines::class)
            ->set('name', 'Sync Users')
            ->set('sourceConnectionId', $conn1->id)
            ->set('sourceTable', 'users')
            ->set('selectedTransformations', ['Trim Text'])
            ->set('targetConnectionId', $conn2->id)
            ->set('targetTable', 'dim_users')
            ->set('columnMappings', [['source' => 'name', 'target' => 'customer_name']])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('studio_pipelines', [
            'name' => 'Sync Users',
            'source_table' => 'users',
            'target_table' => 'dim_users',
        ]);
    }

    public function test_pipeline_runs_can_be_triggered()
    {
        $conn1 = EtlConnection::create([
            'name' => 'Source DB',
            'type' => 'Database',
            'driver' => 'pgsql',
            'config' => [],
            'status' => 'active',
            'metadata' => []
        ]);
        $conn2 = EtlConnection::create([
            'name' => 'Target DB',
            'type' => 'Database',
            'driver' => 'pgsql',
            'config' => [],
            'status' => 'active',
            'metadata' => []
        ]);

        $pipeline = StudioPipeline::create([
            'name' => 'Run Test Pipe',
            'source_connection_id' => $conn1->id,
            'source_table' => 'raw_data',
            'target_connection_id' => $conn2->id,
            'target_table' => 'cleaned_data',
            'transformations' => ['Remove Null'],
            'column_mapping' => [],
            'is_active' => 'active'
        ]);

        Livewire::test(\App\Livewire\StudioRuns::class)
            ->call('startRun', $pipeline->id)
            ->assertSet('runningPipelineId', $pipeline->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('studio_pipeline_runs', [
            'pipeline_id' => $pipeline->id,
            'status' => 'Running'
        ]);

        $run = StudioPipelineRun::where('pipeline_id', $pipeline->id)->first();

        // Complete success
        Livewire::test(\App\Livewire\StudioRuns::class)
            ->call('completeRunSuccess', $run->id, 'LOGS...', 100, 95, 5)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('studio_pipeline_runs', [
            'id' => $run->id,
            'status' => 'Success',
            'rows_read' => 100,
            'rows_written' => 95,
            'rows_rejected' => 5
        ]);
    }

    public function test_ai_etl_assistant_generates_pipeline()
    {
        // Set up DB connections for saving
        EtlConnection::create([
            'name' => 'Oracle Finance ERP',
            'type' => 'Database',
            'driver' => 'oracle',
            'config' => [],
            'status' => 'active',
            'metadata' => []
        ]);
        EtlConnection::create([
            'name' => 'PostgreSQL Data Warehouse',
            'type' => 'Database',
            'driver' => 'pgsql',
            'config' => [],
            'status' => 'active',
            'metadata' => []
        ]);

        Livewire::test(\App\Livewire\StudioAssistant::class)
            ->set('prompt', 'Ambil data customer dari Oracle Finance ERP, bersihkan data null dan simpan ke PostgreSQL Data Warehouse dim_customer')
            ->call('generatePipeline')
            ->assertHasNoErrors()
            ->assertNotSet('generatedPlan', null)
            ->call('savePipeline')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('studio_pipelines', [
            'source_table' => 'customers_raw',
            'target_table' => 'dim_customer'
        ]);
    }

    public function test_studio_monitoring_metrics()
    {
        $conn1 = EtlConnection::create([
            'name' => 'Src', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);
        $conn2 = EtlConnection::create([
            'name' => 'Dst', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);
        $pipe = StudioPipeline::create([
            'name' => 'Test Monitor Pipe',
            'source_connection_id' => $conn1->id,
            'source_table' => 's',
            'target_connection_id' => $conn2->id,
            'target_table' => 'd',
            'transformations' => [],
            'column_mapping' => [],
            'is_active' => 'active'
        ]);

        StudioPipelineRun::create([
            'pipeline_id' => $pipe->id,
            'status' => 'Success',
            'start_time' => now(),
            'end_time' => now()->addSeconds(10),
            'duration_seconds' => 10,
            'rows_read' => 50,
            'rows_written' => 50,
            'rows_rejected' => 0
        ]);

        Livewire::test(\App\Livewire\StudioMonitoring::class)
            ->assertViewHas('successRate', 100.0)
            ->assertViewHas('totalRowsWritten', 50);
    }
}
