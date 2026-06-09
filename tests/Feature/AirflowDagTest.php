<?php

namespace Tests\Feature;

use App\Models\EtlConnection;
use App\Models\StudioPipeline;
use App\Services\AirflowDagGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AirflowDagTest extends TestCase
{
    use RefreshDatabase;

    public function test_dag_generator_service_maps_pipeline_data_to_python_code()
    {
        $service = new AirflowDagGeneratorService();

        $pipelineData = [
            'name' => 'Sync Sales Invoices',
            'source_table' => 'invoices_raw',
            'target_table' => 'fact_sales',
            'transformations' => ['Select Values', 'Formula', 'Filter Rows'],
            'column_mapping' => [
                ['source' => 'id', 'target' => 'sales_id'],
                ['source' => 'amount', 'target' => 'total_amount']
            ],
            'schedule_interval' => 'daily'
        ];

        $pythonCode = $service->generate($pipelineData);

        // Assert basic python DAG structure
        $this->assertStringContainsString('Sync Sales Invoices', $pythonCode);
        $this->assertStringContainsString('sync_sales_invoices_dag', $pythonCode);
        $this->assertStringContainsString("'@daily'", $pythonCode);

        // Assert mapped operators are defined
        $this->assertStringContainsString('extract_invoices_raw', $pythonCode);
        $this->assertStringContainsString('transform_select_values_1', $pythonCode);
        $this->assertStringContainsString('transform_formula_2', $pythonCode);
        $this->assertStringContainsString('transform_filter_rows_3', $pythonCode);
        $this->assertStringContainsString('load_fact_sales', $pythonCode);

        // Assert sequential dependencies flow is defined
        $this->assertStringContainsString(
            'extract_invoices_raw >> transform_select_values_1 >> transform_formula_2 >> transform_filter_rows_3 >> load_fact_sales',
            $pythonCode
        );
    }

    public function test_download_saved_pipeline_dag_route()
    {
        $conn1 = EtlConnection::create([
            'name' => 'Source Conn', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active'
        ]);
        $conn2 = EtlConnection::create([
            'name' => 'Target Conn', 'type' => 'Database', 'driver' => 'pgsql', 'config' => [], 'status' => 'active'
        ]);

        $pipeline = StudioPipeline::create([
            'name' => 'Download Test Pipeline',
            'source_connection_id' => $conn1->id,
            'source_table' => 'source_table_abc',
            'target_connection_id' => $conn2->id,
            'target_table' => 'target_table_xyz',
            'transformations' => ['Select Values', 'Formula'],
            'column_mapping' => [],
            'is_active' => 'active',
            'schedule_interval' => 'hourly'
        ]);

        $response = $this->get(route('studio.pipelines.download-dag', $pipeline->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=download_test_pipeline_dag.py');
        $response->assertHeader('Content-Type', 'text/x-python; charset=UTF-8');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('download_test_pipeline_dag', $content);
        $this->assertStringContainsString("'@hourly'", $content);
        $this->assertStringContainsString('extract_source_table_abc', $content);
        $this->assertStringContainsString('load_target_table_xyz', $content);
    }

    public function test_download_draft_pipeline_dag_route()
    {
        $response = $this->postJson(route('studio.pipelines.download-dag-draft'), [
            'pipeline_name' => 'Assistant Draft Pipeline',
            'source_table' => 'draft_src',
            'target_table' => 'draft_tgt',
            'transformations' => ['Select Values', 'Filter Rows'],
            'column_mapping' => [['source' => 'a', 'target' => 'b']],
            'schedule_interval' => 'daily'
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=assistant_draft_pipeline_dag.py');

        $content = $response->streamedContent();
        $this->assertStringContainsString('assistant_draft_pipeline_dag', $content);
        $this->assertStringContainsString('extract_draft_src', $content);
        $this->assertStringContainsString('transform_select_values_1', $content);
        $this->assertStringContainsString('transform_filter_rows_2', $content);
        $this->assertStringContainsString('load_draft_tgt', $content);
    }
}
