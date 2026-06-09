<?php

namespace App\Services;

use App\Models\StudioPipeline;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class AirflowDagGeneratorService
{
    /**
     * Generate Airflow DAG source code from pipeline model or raw definition array.
     *
     * @param array|StudioPipeline $pipeline
     * @return string
     */
    public function generate(array|StudioPipeline $pipeline): string
    {
        // Normalize pipeline attributes
        if ($pipeline instanceof StudioPipeline) {
            $name = $pipeline->name;
            $sourceTable = $pipeline->source_table;
            $targetTable = $pipeline->target_table;
            $transformations = $pipeline->transformations ?? [];
            $columnMapping = $pipeline->column_mapping ?? [];
            $scheduleInterval = $pipeline->schedule_interval ?? 'manual';
        } else {
            $name = $pipeline['name'] ?? $pipeline['pipeline_name'] ?? 'etl_studio_pipeline';
            $sourceTable = $pipeline['source_table'] ?? 'source_table';
            $targetTable = $pipeline['target_table'] ?? 'target_table';
            $transformations = $pipeline['transformations'] ?? [];
            $columnMapping = $pipeline['column_mapping'] ?? [];
            $scheduleInterval = $pipeline['schedule_interval'] ?? 'manual';
        }

        // Clean pipeline name for DAG ID (snake_case, valid python identifier)
        $dagId = Str::snake(preg_replace('/[^a-zA-Z0-9_]/', '', $name)) . '_dag';

        // Map Airflow schedule interval
        $airflowSchedule = $this->mapScheduleToAirflow($scheduleInterval);

        // Process steps and tasks mapping
        $tasks = [];
        $dependencies = [];

        // 1. Extract Task (Table Input)
        $extractTaskId = 'extract_' . $this->cleanPythonName($sourceTable);
        $tasks[] = [
            'id' => $extractTaskId,
            'type' => 'Extract',
            'name' => 'Table Input: ' . $sourceTable,
            'function' => 'extract_from_' . $this->cleanPythonName($sourceTable),
            'desc' => "Ekstraksi data dari tabel sumber '{$sourceTable}'.",
            'meta' => [
                'table' => $sourceTable,
            ]
        ];
        $prevTaskId = $extractTaskId;

        // 2. Transform Tasks (Select Values, Filter Rows, Formula, etc.)
        $transformIndex = 1;
        foreach ($transformations as $index => $t) {
            $cleanedTransformName = $this->cleanPythonName($t);
            $transformTaskId = 'transform_' . $cleanedTransformName . '_' . $transformIndex;
            
            $meta = [];
            if ($t === 'Select Values' || $t === 'Rename Column' || $t === 'Rename Fields') {
                $meta['mappings'] = $columnMapping;
            }

            $tasks[] = [
                'id' => $transformTaskId,
                'type' => 'Transform',
                'name' => $t,
                'function' => 'apply_' . $cleanedTransformName . '_' . $transformIndex,
                'desc' => "Task transformasi: Menerapkan logika '{$t}'.",
                'parent_id' => $prevTaskId,
                'meta' => $meta
            ];

            $prevTaskId = $transformTaskId;
            $transformIndex++;
        }

        // 3. Load Task (Table Output)
        $loadTaskId = 'load_' . $this->cleanPythonName($targetTable);
        $tasks[] = [
            'id' => $loadTaskId,
            'type' => 'Load',
            'name' => 'Table Output: ' . $targetTable,
            'function' => 'load_into_' . $this->cleanPythonName($targetTable),
            'desc' => "Memuat hasil akhir data ETL ke tabel tujuan '{$targetTable}'.",
            'parent_id' => $prevTaskId,
            'meta' => [
                'table' => $targetTable
            ]
        ];

        // Construct a single dependency chain
        $dependencies = [implode(' >> ', array_column($tasks, 'id'))];

        // Render Python DAG using Blade Template
        return View::make('templates.airflow_dag', [
            'dag_id' => $dagId,
            'pipeline_name' => $name,
            'schedule_interval' => $airflowSchedule,
            'tasks' => $tasks,
            'dependencies' => $dependencies,
            'generated_at' => date('Y-m-d H:i:s'),
            'source_table' => $sourceTable,
            'target_table' => $targetTable,
            'column_mapping' => $columnMapping
        ])->render();
    }

    /**
     * Map studio schedule interval format to Airflow equivalent.
     *
     * @param string $interval
     * @return string
     */
    protected function mapScheduleToAirflow(string $interval): string
    {
        return match ($interval) {
            'hourly' => "'@hourly'",
            'daily' => "'@daily'",
            'weekly' => "'@weekly'",
            'monthly' => "'@monthly'",
            'manual' => 'None',
            default => str_contains($interval, '*') || preg_match('/^[0-9\/,\s]+$/', $interval) ? "'{$interval}'" : 'None',
        };
    }

    /**
     * Clean strings to make valid python identifier words.
     *
     * @param string $name
     * @return string
     */
    protected function cleanPythonName(string $name): string
    {
        $name = strtolower(str_replace([' ', '.', '-', '/'], '_', $name));
        $name = preg_replace('/[^a-z0-9_]/', '', $name);
        return $name ?: 'step';
    }
}
