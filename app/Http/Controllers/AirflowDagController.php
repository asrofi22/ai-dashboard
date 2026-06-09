<?php

namespace App\Http\Controllers;

use App\Models\StudioPipeline;
use App\Services\AirflowDagGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AirflowDagController extends Controller
{
    protected AirflowDagGeneratorService $generator;

    public function __construct(AirflowDagGeneratorService $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Download the Airflow DAG for a saved pipeline.
     *
     * @param StudioPipeline $pipeline
     * @return StreamedResponse
     */
    public function downloadSaved(StudioPipeline $pipeline): StreamedResponse
    {
        $dagCode = $this->generator->generate($pipeline);
        $fileName = Str::snake(preg_replace('/[^a-zA-Z0-9_]/', '', $pipeline->name)) . '_dag.py';

        return response()->streamDownload(function () use ($dagCode) {
            echo $dagCode;
        }, $fileName, [
            'Content-Type' => 'text/x-python',
        ]);
    }

    /**
     * Download the Airflow DAG for an unsaved dynamic blueprint draft.
     *
     * @param Request $request
     * @return StreamedResponse
     */
    public function downloadDraft(Request $request): StreamedResponse
    {
        $request->validate([
            'pipeline_name' => 'required|string|min:3',
            'source_table' => 'required|string',
            'target_table' => 'required|string',
            'transformations' => 'nullable|array',
            'column_mapping' => 'nullable|array',
            'schedule_interval' => 'nullable|string'
        ]);

        $pipelineData = [
            'name' => $request->input('pipeline_name'),
            'source_table' => $request->input('source_table'),
            'target_table' => $request->input('target_table'),
            'transformations' => $request->input('transformations', []),
            'column_mapping' => $request->input('column_mapping', []),
            'schedule_interval' => $request->input('schedule_interval', 'manual')
        ];

        $dagCode = $this->generator->generate($pipelineData);
        $fileName = Str::snake(preg_replace('/[^a-zA-Z0-9_]/', '', $pipelineData['name'])) . '_dag.py';

        return response()->streamDownload(function () use ($dagCode) {
            echo $dagCode;
        }, $fileName, [
            'Content-Type' => 'text/x-python',
        ]);
    }
}
