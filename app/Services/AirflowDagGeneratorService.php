<?php

namespace App\Services;

use App\Models\StudioPipeline;
use App\Models\EtlConnection;
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
        // 1. Normalize attributes
        if ($pipeline instanceof StudioPipeline) {
            $name = $pipeline->name;
            $sourceTable = $pipeline->source_table;
            $targetTable = $pipeline->target_table;
            $scheduleInterval = $pipeline->schedule_interval ?? 'manual';
            $sourceConn = $pipeline->sourceConnection;
            $targetConn = $pipeline->targetConnection;
        } else {
            $name = $pipeline['name'] ?? $pipeline['pipeline_name'] ?? 'etl_studio_pipeline';
            $sourceTable = $pipeline['source_table'] ?? 'source_table';
            $targetTable = $pipeline['target_table'] ?? 'target_table';
            $scheduleInterval = $pipeline['schedule_interval'] ?? 'manual';

            $srcName = $pipeline['source_connection_name'] ?? '';
            $tgtName = $pipeline['target_connection_name'] ?? '';
            $sourceConn = EtlConnection::where('name', $srcName)->first() ?? EtlConnection::find($pipeline['source_connection_id'] ?? null);
            $targetConn = EtlConnection::where('name', $tgtName)->first() ?? EtlConnection::find($pipeline['target_connection_id'] ?? null);
        }

        // Clean pipeline name for DAG ID (snake_case, valid python identifier)
        $dagId = Str::snake(preg_replace('/[^a-zA-Z0-9_]/', '', $name)) . '_dag';

        // Map Airflow schedule interval
        $airflowSchedule = $this->mapScheduleToAirflow($scheduleInterval);
        
        $generatedAt = date('Y-m-d H:i:s');
        $currentDateYear = date('Y');
        $currentDateMonth = date('n');
        $currentDateDay = date('j');

        // Resolve connection names
        $sourceConnId = 'postgres_source';
        $targetConnId = 'postgres_target';
        
        if ($sourceConn) {
            $sourceConnId = Str::snake(preg_replace('/[^a-zA-Z0-9_]/', '', $sourceConn->name));
        }
        if ($targetConn) {
            $targetConnId = Str::snake(preg_replace('/[^a-zA-Z0-9_]/', '', $targetConn->name));
        }

        // Clean python names for tables
        $cleanSrcTable = $this->cleanPythonName($sourceTable);
        $cleanTgtTable = $this->cleanPythonName($targetTable);

        // Task 1: Source Validation
        $validateSourceTaskId = "validate_source_{$cleanSrcTable}";
        $validateSourceSql = "SELECT 1 FROM {$sourceTable} LIMIT 1;";
        $indentedValidateSourceSql = $this->indentSql($validateSourceSql);

        // Task 2: Load Target
        $loadTargetTaskId = "load_{$cleanTgtTable}";
        $loadSql = $this->buildLoadSql($pipeline);
        $indentedLoadSql = $this->indentSql($loadSql);

        // Task 3: Target Validation
        $validateTargetTaskId = "validate_target_{$cleanTgtTable}";
        $validateTargetSql = "SELECT 1 FROM {$targetTable} LIMIT 1;";
        $indentedValidateTargetSql = $this->indentSql($validateTargetSql);

        // Build the task definitions code
        $tasksListCode = [];
        $tasksListCode[] = "    # Task 1: Validate Source Table Existence and Data Availability\n" .
            "    {$validateSourceTaskId} = SQLExecuteQueryOperator(\n" .
            "        task_id='{$validateSourceTaskId}',\n" .
            "        sql=\"\"\"{$indentedValidateSourceSql}\"\"\",\n" .
            "        conn_id='{$sourceConnId}',\n" .
            "    )";

        $tasksListCode[] = "    # Task 2: Executable SQL Load query combining Select, Formulas, and Deduplication\n" .
            "    {$loadTargetTaskId} = SQLExecuteQueryOperator(\n" .
            "        task_id='{$loadTargetTaskId}',\n" .
            "        sql=\"\"\"{$indentedLoadSql}\"\"\",\n" .
            "        conn_id='{$targetConnId}',\n" .
            "    )";

        $tasksListCode[] = "    # Task 3: Validate Target Table Population\n" .
            "    {$validateTargetTaskId} = SQLExecuteQueryOperator(\n" .
            "        task_id='{$validateTargetTaskId}',\n" .
            "        sql=\"\"\"{$indentedValidateTargetSql}\"\"\",\n" .
            "        conn_id='{$targetConnId}',\n" .
            "    )";

        $tasksCodeStr = implode("\n\n", $tasksListCode);
        $dependencyFlowCode = "    {$validateSourceTaskId} >> {$loadTargetTaskId} >> {$validateTargetTaskId}";

        // Compile clean Python output string (Zero Blade HTML annotations)
        $pythonCode = <<<PYTHON
"""
Apache Airflow DAG generated automatically by AI DataGov ETL Studio.
Pipeline Name: {$name}
Generated At: {$generatedAt}
"""

from datetime import datetime, timedelta
from airflow import DAG
from airflow.providers.common.sql.operators.sql import SQLExecuteQueryOperator
import logging

# Default arguments for the DAG
default_args = {
    'owner': 'ai_datagov',
    'depends_on_past': False,
    'start_date': datetime({$currentDateYear}, {$currentDateMonth}, {$currentDateDay}),
    'email_on_failure': False,
    'email_on_retry': False,
    'retries': 1,
    'retry_delay': timedelta(minutes=5),
}

# ------------------------------------------------------------------------
# DAG DEFINITION
# ------------------------------------------------------------------------

with DAG(
    dag_id='{$dagId}',
    default_args=default_args,
    description='ETL pipeline {$name} generated by AI DataGov',
    schedule_interval={$airflowSchedule},
    catchup=False,
    tags=['ai_datagov', 'etl_studio'],
) as dag:

{$tasksCodeStr}

    # Define task dependencies flow
{$dependencyFlowCode}

PYTHON;

        return $pythonCode;
    }

    /**
     * Unifies and generates select and load queries from a pipeline draft/definition.
     *
     * @param array|StudioPipeline $pipeline
     * @return array
     */
    public function generateSqlQuery(array|StudioPipeline $pipeline): array
    {
        return [
            'select_query' => $this->buildSelectSql($pipeline),
            'load_query' => $this->buildLoadSql($pipeline),
        ];
    }

    /**
     * Build SELECT SQL query based on transformations and mappings.
     *
     * @param array|StudioPipeline $pipeline
     * @param int|null $upToStepIndex
     * @return string
     */
    public function buildSelectSql(array|StudioPipeline $pipeline, ?int $upToStepIndex = null): string
    {
        if ($pipeline instanceof StudioPipeline) {
            $sourceTable = $pipeline->source_table;
            $transformations = $pipeline->transformations ?? [];
            $columnMapping = $pipeline->column_mapping ?? [];
            $canvasData = $pipeline->canvas_data;
        } else {
            $sourceTable = $pipeline['source_table'] ?? 'source_table';
            $transformations = $pipeline['transformations'] ?? [];
            $columnMapping = $pipeline['column_mapping'] ?? [];
            $canvasData = $pipeline['canvas_data'] ?? null;
        }

        if (is_string($canvasData)) {
            $canvasData = json_decode($canvasData, true);
        }

        if ($upToStepIndex === 0) {
            return "SELECT *\nFROM {$sourceTable}";
        }

        $activeTransforms = $transformations;
        if ($upToStepIndex !== null) {
            $activeTransforms = array_slice($transformations, 0, $upToStepIndex);
        }

        // Detect DISTINCT
        $hasUnique = false;
        foreach ($activeTransforms as $t) {
            $lowerT = strtolower($t);
            if (str_contains($lowerT, 'unique') || str_contains($lowerT, 'duplicate')) {
                $hasUnique = true;
            }
        }
        $distinct = $hasUnique ? " DISTINCT" : "";

        // Extract Formulas
        $formulasMap = [];
        $hasFormulaActive = false;
        foreach ($activeTransforms as $at) {
            if (str_contains(strtolower($at), 'formula')) {
                $hasFormulaActive = true;
                break;
            }
        }

        if ($hasFormulaActive && $canvasData && isset($canvasData['nodes'])) {
            foreach ($canvasData['nodes'] as $node) {
                if (($node['name'] ?? '') === 'Formula' || ($node['label'] ?? '') === 'Formula') {
                    foreach ($node['settings']['formulas'] ?? [] as $f) {
                        if (!empty($f['field_name'])) {
                            $formulasMap[$f['field_name']] = self::translateFormulaToSql($f['formula']);
                        }
                    }
                }
            }
        }

        // Parse calculations from draft mappings if canvas data isn't present
        foreach ($columnMapping as $map) {
            $src = $map['source'] ?? '';
            $tgt = $map['target'] ?? '';
            if (str_starts_with($src, '[Kalkulasi]')) {
                $expr = trim(substr($src, strlen('[Kalkulasi]')));
                $formulasMap[$tgt] = self::translateFormulaToSql($expr);
            } elseif (str_contains($src, '[Kalkulasi')) {
                $formulasMap[$tgt] = "CONCAT(first_name, ' ', last_name)";
            }
        }

        // Identify raw source columns needed in subquery selection
        $rawSourceCols = [];
        foreach ($columnMapping as $map) {
            $src = $map['source'] ?? '';
            if (!empty($src) && !str_contains($src, '[Kalkulasi') && !str_contains($src, '[Serial')) {
                $cleaned = preg_replace('/\[([^\]]+)\]/', '$1', $src);
                if (preg_match('/^[a-zA-Z0-9_]+$/', $cleaned)) {
                    $rawSourceCols[$cleaned] = true;
                }
            }
        }

        foreach ($formulasMap as $field => $expr) {
            preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', $expr, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $word) {
                    $lowerWord = strtolower($word);
                    if (!in_array($lowerWord, ['concat', 'if', 'else', 'then', 'end', 'select', 'distinct', 'case', 'when', 'or', 'and', 'not', 'null', 'nextval'])) {
                        $rawSourceCols[$word] = true;
                    }
                }
            }
            unset($rawSourceCols[$field]);
        }

        $rawSourceColsList = array_keys($rawSourceCols);
        if (empty($rawSourceColsList)) {
            $rawSourceColsList = ['*'];
        }

        // Build outer select columns mapping
        $outerSelectCols = [];
        foreach ($columnMapping as $map) {
            $src = $map['source'] ?? '';
            $tgt = $map['target'] ?? '';

            if (isset($formulasMap[$tgt]) || isset($formulasMap[$src])) {
                $expr = $formulasMap[$tgt] ?? $formulasMap[$src];
                $outerSelectCols[] = "    {$expr} AS {$tgt}";
            } elseif (str_starts_with($src, '[Serial]') || str_contains($src, '[Serial')) {
                $seq = 'seq_' . $tgt;
                if (str_starts_with($src, '[Serial]')) {
                    $customSeq = trim(substr($src, strlen('[Serial]')));
                    if (!empty($customSeq)) $seq = $customSeq;
                }
                $outerSelectCols[] = "    NEXTVAL('{$seq}') AS {$tgt}";
            } else {
                $cleanedSrc = preg_replace('/\[([^\]]+)\]/', '$1', $src);
                if ($cleanedSrc === $tgt) {
                    $outerSelectCols[] = "    {$tgt}";
                } else {
                    $outerSelectCols[] = "    {$cleanedSrc} AS {$tgt}";
                }
            }
        }

        // Build subquery if transformations exist
        if (!empty($activeTransforms)) {
            $innerColsStr = implode(",\n    ", $rawSourceColsList);
            $outerColsStr = implode(",\n", $outerSelectCols);
            $innerQuery = "SELECT{$distinct}\n    {$innerColsStr}\n    FROM {$sourceTable}";
            return "SELECT\n{$outerColsStr}\nFROM (\n    " . str_replace("\n", "\n    ", $innerQuery) . "\n) src";
        } else {
            // Simple flat select
            $flatSelectCols = [];
            foreach ($columnMapping as $map) {
                $src = $map['source'] ?? '';
                $tgt = $map['target'] ?? '';
                if (str_starts_with($src, '[Serial]') || str_contains($src, '[Serial')) {
                    $flatSelectCols[] = "    NEXTVAL('seq_{$tgt}') AS {$tgt}";
                } else {
                    $cleanedSrc = preg_replace('/\[([^\]]+)\]/', '$1', $src);
                    if ($cleanedSrc === $tgt) {
                        $flatSelectCols[] = "    {$tgt}";
                    } else {
                        $flatSelectCols[] = "    {$cleanedSrc} AS {$tgt}";
                    }
                }
            }
            $flatColsStr = empty($flatSelectCols) ? "    *" : implode(",\n", $flatSelectCols);
            return "SELECT\n{$flatColsStr}\nFROM {$sourceTable}";
        }
    }

    /**
     * Build load (INSERT/UPSERT) SQL query based on transformations and mappings.
     *
     * @param array|StudioPipeline $pipeline
     * @return string
     */
    public function buildLoadSql(array|StudioPipeline $pipeline): string
    {
        if ($pipeline instanceof StudioPipeline) {
            $sourceTable = $pipeline->source_table;
            $targetTable = $pipeline->target_table;
            $transformations = $pipeline->transformations ?? [];
            $columnMapping = $pipeline->column_mapping ?? [];
            $canvasData = $pipeline->canvas_data;
        } else {
            $sourceTable = $pipeline['source_table'] ?? 'source_table';
            $targetTable = $pipeline['target_table'] ?? 'target_table';
            $transformations = $pipeline['transformations'] ?? [];
            $columnMapping = $pipeline['column_mapping'] ?? [];
            $canvasData = $pipeline['canvas_data'] ?? null;
        }

        if (is_string($canvasData)) {
            $canvasData = json_decode($canvasData, true);
        }

        // Detect DISTINCT
        $hasUnique = false;
        foreach ($transformations as $t) {
            $lowerT = strtolower($t);
            if (str_contains($lowerT, 'unique') || str_contains($lowerT, 'duplicate')) {
                $hasUnique = true;
            }
        }
        $distinct = $hasUnique ? " DISTINCT" : "";

        // Extract Formulas
        $formulasMap = [];
        $hasFormulaActive = false;
        foreach ($transformations as $at) {
            if (str_contains(strtolower($at), 'formula')) {
                $hasFormulaActive = true;
                break;
            }
        }

        if ($hasFormulaActive && $canvasData && isset($canvasData['nodes'])) {
            foreach ($canvasData['nodes'] as $node) {
                if (($node['name'] ?? '') === 'Formula' || ($node['label'] ?? '') === 'Formula') {
                    foreach ($node['settings']['formulas'] ?? [] as $f) {
                        if (!empty($f['field_name'])) {
                            $formulasMap[$f['field_name']] = self::translateFormulaToSql($f['formula']);
                        }
                    }
                }
            }
        }

        // Parse calculations from draft mappings if canvas data isn't present
        foreach ($columnMapping as $map) {
            $src = $map['source'] ?? '';
            $tgt = $map['target'] ?? '';
            if (str_starts_with($src, '[Kalkulasi]')) {
                $expr = trim(substr($src, strlen('[Kalkulasi]')));
                $formulasMap[$tgt] = self::translateFormulaToSql($expr);
            } elseif (str_contains($src, '[Kalkulasi')) {
                $formulasMap[$tgt] = "CONCAT(first_name, ' ', last_name)";
            }
        }

        // Identify raw source columns needed in subquery selection
        $rawSourceCols = [];
        foreach ($columnMapping as $map) {
            $src = $map['source'] ?? '';
            if (!empty($src) && !str_contains($src, '[Kalkulasi') && !str_contains($src, '[Serial')) {
                $cleaned = preg_replace('/\[([^\]]+)\]/', '$1', $src);
                if (preg_match('/^[a-zA-Z0-9_]+$/', $cleaned)) {
                    $rawSourceCols[$cleaned] = true;
                }
            }
        }

        foreach ($formulasMap as $field => $expr) {
            preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', $expr, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $word) {
                    $lowerWord = strtolower($word);
                    if (!in_array($lowerWord, ['concat', 'if', 'else', 'then', 'end', 'select', 'distinct', 'case', 'when', 'or', 'and', 'not', 'null', 'nextval'])) {
                        $rawSourceCols[$word] = true;
                    }
                }
            }
            unset($rawSourceCols[$field]);
        }

        $rawSourceColsList = array_keys($rawSourceCols);
        if (empty($rawSourceColsList)) {
            $rawSourceColsList = ['*'];
        }

        // Build load targets and outer SELECT expressions
        $tgtColumnsList = [];
        $outerSelectExprs = [];
        foreach ($columnMapping as $map) {
            $src = $map['source'] ?? '';
            $tgt = $map['target'] ?? '';

            $tgtColumnsList[] = $tgt;

            if (isset($formulasMap[$tgt]) || isset($formulasMap[$src])) {
                $expr = $formulasMap[$tgt] ?? $formulasMap[$src];
                $outerSelectExprs[] = "    {$expr}";
            } elseif (str_starts_with($src, '[Serial]') || str_contains($src, '[Serial')) {
                $seq = 'seq_' . $tgt;
                if (str_starts_with($src, '[Serial]')) {
                    $customSeq = trim(substr($src, strlen('[Serial]')));
                    if (!empty($customSeq)) $seq = $customSeq;
                }
                $outerSelectExprs[] = "    NEXTVAL('{$seq}')";
            } else {
                $cleanedSrc = preg_replace('/\[([^\]]+)\]/', '$1', $src);
                $outerSelectExprs[] = "    {$cleanedSrc}";
            }
        }

        $tgtColsStr = implode(",\n    ", $tgtColumnsList);
        $outerSelectStr = implode(",\n", $outerSelectExprs);

        // Detect PK/conflict key for Postgres ON CONFLICT UPSERT
        $pkCol = 'customer_id';
        foreach ($columnMapping as $map) {
            $tgt = $map['target'] ?? '';
            if (str_contains(strtolower($tgt), 'id') && !str_contains(strtolower($tgt), 'key')) {
                $pkCol = $tgt;
                break;
            }
        }

        $isUpsert = false;
        if ($canvasData && isset($canvasData['nodes'])) {
            foreach ($canvasData['nodes'] as $node) {
                if ($node['type'] === 'output' && (($node['name'] ?? '') === 'Insert Update' || ($node['label'] ?? '') === 'Insert Update')) {
                    $isUpsert = true;
                    break;
                }
            }
        }

        $onConflict = "";
        if ($isUpsert) {
            $updateAssignments = [];
            foreach ($tgtColumnsList as $col) {
                if ($col !== $pkCol) {
                    $updateAssignments[] = "{$col} = EXCLUDED.{$col}";
                }
            }
            $onConflict = "\nON CONFLICT ({$pkCol})\nDO UPDATE SET\n    " . implode(",\n    ", $updateAssignments);
        }

        // Build load query with subselect if transformations exist
        if (!empty($transformations)) {
            $innerColsStr = implode(",\n    ", $rawSourceColsList);
            $innerQuery = "SELECT{$distinct}\n    {$innerColsStr}\n    FROM {$sourceTable}";
            return "INSERT INTO {$targetTable}\n(\n    {$tgtColsStr}\n)\nSELECT\n{$outerSelectStr}\nFROM (\n    " . str_replace("\n", "\n    ", $innerQuery) . "\n) src{$onConflict};";
        } else {
            return "INSERT INTO {$targetTable}\n(\n    {$tgtColsStr}\n)\nSELECT\n{$outerSelectStr}\nFROM {$sourceTable}{$onConflict};";
        }
    }

    /**
     * Converts a visual/AI formula expression text to executable SQL.
     *
     * @param string $formula
     * @return string
     */
    public static function translateFormulaToSql(string $formula): string
    {
        // 1. Remove brackets [field] -> field
        $sql = preg_replace('/\[([^\]]+)\]/', '$1', $formula);

        // 2. Convert standard + operator concatenations (ignoring quotes)
        if (str_contains($sql, '+')) {
            $parts = preg_split('/\s*\+\s*(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql);
            if (count($parts) > 1) {
                $trimmedParts = array_map('trim', $parts);
                return "CONCAT(" . implode(', ', $trimmedParts) . ")";
            }
        }

        return $sql;
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

    /**
     * Helper to indent SQL block strings for clean Python indentation.
     *
     * @param string $sql
     * @param string $indent
     * @return string
     */
    protected function indentSql(string $sql, string $indent = '        '): string
    {
        $lines = explode("\n", $sql);
        $indentedLines = array_map(function($line) use ($indent) {
            return $indent . $line;
        }, $lines);
        return "\n" . implode("\n", $indentedLines) . "\n" . $indent;
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
}
