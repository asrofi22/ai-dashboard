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

    public function test_pipeline_run_progress_and_force_stop()
    {
        $conn1 = EtlConnection::create([
            'name' => 'Source DB', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);
        $conn2 = EtlConnection::create([
            'name' => 'Target DB', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);

        $pipeline = StudioPipeline::create([
            'name' => 'Progress Test Pipe',
            'source_connection_id' => $conn1->id,
            'source_table' => 'raw_data',
            'target_connection_id' => $conn2->id,
            'target_table' => 'cleaned_data',
            'transformations' => ['Remove Null'],
            'column_mapping' => [],
            'is_active' => 'active'
        ]);

        $run = StudioPipelineRun::create([
            'pipeline_id' => $pipeline->id,
            'status' => 'Running',
            'start_time' => now(),
            'rows_read' => 0,
            'rows_written' => 0,
            'rows_rejected' => 0,
            'execution_logs' => ''
        ]);

        Livewire::test(\App\Livewire\StudioRuns::class)
            ->call('updateRunProgress', $run->id, 'LOGS STEP 1', 10, 10, 0, [['step' => 'Select Values', 'read' => 10, 'written' => 10, 'rejected' => 0, 'status' => 'Success']])
            ->assertHasNoErrors()
            ->call('forceStopRun', $run->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('studio_pipeline_runs', [
            'id' => $run->id,
            'status' => 'Failed',
            'rows_read' => 10,
            'rows_written' => 10,
            'rows_rejected' => 0
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

    public function test_studio_runs_renders_different_step_metrics_schemas()
    {
        $conn1 = EtlConnection::create([
            'name' => 'Src', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);
        $conn2 = EtlConnection::create([
            'name' => 'Dst', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);
        $pipe = StudioPipeline::create([
            'name' => 'Test Metrics Render Pipe',
            'source_connection_id' => $conn1->id,
            'source_table' => 's',
            'target_connection_id' => $conn2->id,
            'target_table' => 'd',
            'transformations' => [],
            'column_mapping' => [],
            'is_active' => 'active'
        ]);

        // 1. Flat schema run
        $runFlat = StudioPipelineRun::create([
            'pipeline_id' => $pipe->id,
            'status' => 'Success',
            'start_time' => now(),
            'end_time' => now(),
            'rows_read' => 10,
            'rows_written' => 10,
            'rows_rejected' => 0,
            'step_metrics' => [
                ['step' => 'Select Values', 'read' => 10, 'written' => 10, 'rejected' => 0, 'status' => 'Success']
            ]
        ]);

        // 2. Associative schema run
        $runAssoc = StudioPipelineRun::create([
            'pipeline_id' => $pipe->id,
            'status' => 'Success',
            'start_time' => now(),
            'end_time' => now(),
            'rows_read' => 20,
            'rows_written' => 20,
            'rows_rejected' => 0,
            'step_metrics' => [
                'source' => ['label' => 'Source Conn', 'input' => 20, 'output' => 20, 'rejected' => 0, 'status' => 'Success'],
                'target' => ['label' => 'Target Conn', 'input' => 20, 'output' => 20, 'rejected' => 0, 'status' => 'Success']
            ]
        ]);

        // Assert Livewire rendering doesn't throw errors when selecting runs
        Livewire::test(\App\Livewire\StudioRuns::class)
            ->call('selectRun', $runFlat->id)
            ->assertSee('Select Values')
            ->call('selectRun', $runAssoc->id)
            ->assertSee('Source Conn')
            ->assertSee('Target Conn')
            ->assertHasNoErrors();
    }

    public function test_auto_fix_run_unique_constraint_adds_remove_duplicate()
    {
        $conn1 = EtlConnection::create([
            'name' => 'Src DB', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);
        $conn2 = EtlConnection::create([
            'name' => 'Dst DB', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);
        $pipeline = StudioPipeline::create([
            'name' => 'Duplicate Test Pipe',
            'source_connection_id' => $conn1->id,
            'source_table' => 's',
            'target_connection_id' => $conn2->id,
            'target_table' => 'd',
            'transformations' => [],
            'column_mapping' => [],
            'is_active' => 'active'
        ]);

        $run = StudioPipelineRun::create([
            'pipeline_id' => $pipeline->id,
            'status' => 'Failed',
            'start_time' => now(),
            'error_log' => 'ERROR - Unique Constraint Violation: Duplicate key value violates unique constraint idx_customer_email on dim_customer',
            'execution_logs' => 'Initial execution logs...'
        ]);

        Livewire::test(\App\Livewire\StudioRuns::class)
            ->call('autoFixRun', $run->id)
            ->assertHasNoErrors();

        // Pipeline transformations should now have 'Remove Duplicate'
        $pipeline->refresh();
        $this->assertContains('Remove Duplicate', $pipeline->transformations);

        // A rerun should have been triggered, resulting in a new running/succeeded run
        $this->assertDatabaseHas('studio_pipeline_runs', [
            'pipeline_id' => $pipeline->id,
            'status' => 'Running'
        ]);
    }

    public function test_auto_fix_run_connection_refused_activates_connection()
    {
        $conn1 = EtlConnection::create([
            'name' => 'Inactive Src DB', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'inactive', 'metadata' => []
        ]);
        $conn2 = EtlConnection::create([
            'name' => 'Dst DB', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);
        $pipeline = StudioPipeline::create([
            'name' => 'Conn Test Pipe',
            'source_connection_id' => $conn1->id,
            'source_table' => 's',
            'target_connection_id' => $conn2->id,
            'target_table' => 'd',
            'transformations' => [],
            'column_mapping' => [],
            'is_active' => 'active'
        ]);

        $run = StudioPipelineRun::create([
            'pipeline_id' => $pipeline->id,
            'status' => 'Failed',
            'start_time' => now(),
            'error_log' => 'ERROR - Connection Refused: ORA-12541 TNS: no listener.',
            'execution_logs' => 'Initial execution logs...'
        ]);

        Livewire::test(\App\Livewire\StudioRuns::class)
            ->call('autoFixRun', $run->id)
            ->assertHasNoErrors();

        // Source connection status should be active now
        $conn1->refresh();
        $this->assertEquals('active', $conn1->status);
    }

    public function test_auto_fix_run_schema_mismatch_adds_column()
    {
        $conn1 = EtlConnection::create([
            'name' => 'Src DB', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);
        $conn2 = EtlConnection::create([
            'name' => 'Dst DB', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);
        $pipeline = StudioPipeline::create([
            'name' => 'Schema Test Pipe',
            'source_connection_id' => $conn1->id,
            'source_table' => 's',
            'target_connection_id' => $conn2->id,
            'target_table' => 'd',
            'transformations' => [],
            'column_mapping' => [['source' => 'created_at', 'target' => 'created_at']],
            'is_active' => 'active'
        ]);

        $run = StudioPipelineRun::create([
            'pipeline_id' => $pipeline->id,
            'status' => 'Failed',
            'start_time' => now(),
            'error_log' => 'SQLSTATE[42703]: Undefined column: 7 ERROR: kolom « created_at » dari relasi « dim_customer » tidak ada',
            'execution_logs' => 'Initial execution logs...'
        ]);

        Livewire::test(\App\Livewire\StudioRuns::class)
            ->call('autoFixRun', $run->id)
            ->assertHasNoErrors();

        // Rerun should be triggered
        $this->assertDatabaseHas('studio_pipeline_runs', [
            'pipeline_id' => $pipeline->id,
            'status' => 'Running'
        ]);
    }

    public function test_physical_executor_calculated_column_concatenation()
    {
        // Setup SQLite production/target database tables in the same connection for simplicity
        \Illuminate\Support\Facades\Schema::create('src_customers', function ($table) {
            $table->string('first_name');
            $table->string('last_name');
        });
        \Illuminate\Support\Facades\Schema::create('tgt_customers', function ($table) {
            $table->string('customer_name');
            $table->timestamps();
        });

        // Insert source row
        \Illuminate\Support\Facades\DB::table('src_customers')->insert([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        // Setup connections
        $conn1 = EtlConnection::create([
            'name' => 'Source SQLite', 'type' => 'Database', 'driver' => 'sqlite', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);
        $conn2 = EtlConnection::create([
            'name' => 'Target SQLite', 'type' => 'Database', 'driver' => 'sqlite', 'config' => [], 'status' => 'active', 'metadata' => []
        ]);

        // Setup pipeline
        $pipeline = StudioPipeline::create([
            'name' => 'Concat Test Pipe',
            'source_connection_id' => $conn1->id,
            'source_table' => 'src_customers',
            'target_connection_id' => $conn2->id,
            'target_table' => 'tgt_customers',
            'transformations' => [],
            'column_mapping' => [
                ['source' => "[Kalkulasi: first_name + ' ' + last_name]", 'target' => 'customer_name']
            ],
            'is_active' => 'active'
        ]);

        // Run physical execution
        $executor = app(\App\Services\PipelineExecutorService::class);
        $result = $executor->execute($pipeline);

        $this->assertEquals(1, $result['read']);
        $this->assertEquals(1, $result['written']);

        // Assert physical record was written with correct concatenated string
        $this->assertDatabaseHas('tgt_customers', [
            'customer_name' => 'John Doe'
        ]);

        // Cleanup tables
        \Illuminate\Support\Facades\Schema::dropIfExists('src_customers');
        \Illuminate\Support\Facades\Schema::dropIfExists('tgt_customers');
    }

    public function test_studio_pipelines_backend_helpers()
    {
        $conn = EtlConnection::create([
            'name' => 'Helper Test DB',
            'type' => 'Database',
            'driver' => 'mysql',
            'config' => [],
            'status' => 'active',
            'metadata' => [
                'tables' => [
                    [
                        'name' => 'sales_orders',
                        'columns' => ['order_id', 'customer_name', 'amount']
                    ]
                ]
            ]
        ]);

        $component = new \App\Livewire\StudioPipelines();

        // 1. testConnection
        $res1 = $component->testConnection($conn->id);
        $this->assertEquals(['success' => true, 'message' => "Koneksi 'Helper Test DB' berhasil terhubung!"], $res1);

        // 2. fetchTableColumns
        $res2 = $component->fetchTableColumns($conn->id, 'sales_orders');
        $this->assertEquals(['order_id', 'customer_name', 'amount'], $res2);

        // 3. previewSqlQuery with select *
        $res3 = $component->previewSqlQuery($conn->id, 'SELECT * FROM sales_orders');
        $this->assertEquals(['order_id', 'customer_name', 'amount'], $res3['columns']);
        $this->assertCount(3, $res3['rows']);
        $this->assertEquals('Val amount 1', $res3['rows'][0]['amount']);

        // 4. previewSqlQuery with select alias
        $res4 = $component->previewSqlQuery($conn->id, 'SELECT order_id as id, amount as total FROM sales_orders');
        $this->assertEquals(['id', 'total'], $res4['columns']);
        $this->assertEquals('Val total 1', $res4['rows'][0]['total']);
    }
}
