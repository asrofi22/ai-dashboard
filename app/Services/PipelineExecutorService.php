<?php

namespace App\Services;

use App\Models\StudioPipeline;
use App\Models\WarehouseTable;
use App\Models\WarehouseColumn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PipelineExecutorService
{
    public function execute(StudioPipeline $pipeline): array
    {
        $sourceConn = $pipeline->sourceConnection;
        $targetConn = $pipeline->targetConnection;

        if (!$sourceConn || !$targetConn) {
            throw new \Exception("Koneksi source atau target tidak valid.");
        }

        $sourceDb = $sourceConn->getDatabaseConnection();
        $targetDb = $targetConn->getDatabaseConnection();

        $sourceTable = $pipeline->source_table;
        $targetTable = $pipeline->target_table;

        Log::info("Executing pipeline physically: {$pipeline->name} ({$sourceTable} -> {$targetTable})");

        $canvas = $pipeline->canvas_data;
        if (!empty($canvas) && isset($canvas['nodes']) && count($canvas['nodes']) > 0) {
             return $this->executeCanvasPipeline($pipeline, $canvas, $sourceDb, $targetDb);
        }

        // 1. Check if the target schema needs to be created
        if (str_contains($targetTable, '.')) {
            $parts = explode('.', $targetTable);
            $schema = $parts[0];
            
            // For PostgreSQL, check and create schema
            if ($targetConn->driver === 'pgsql') {
                $targetDb->statement("CREATE SCHEMA IF NOT EXISTS {$schema}");
            }
        }

        // 2. Specific logic for customer balance calculation
        if ($targetTable === 'dw.fact_customer_balance' || str_contains($targetTable, 'customer_balance')) {
            // Ensure target table exists
            if ($targetConn->driver === 'pgsql') {
                $targetDb->statement("
                    CREATE TABLE IF NOT EXISTS {$targetTable} (
                        balance_id SERIAL PRIMARY KEY,
                        period_month DATE NOT NULL,
                        customer_id INTEGER NOT NULL,
                        beginning_balance NUMERIC(12,2) DEFAULT 0.00,
                        payment_amount NUMERIC(12,2) DEFAULT 0.00,
                        ending_balance NUMERIC(12,2) DEFAULT 0.00,
                        created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
                        updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
                    )
                ");
            }

            // Extract from source. Note: source table could be public.payment
            // Check if payment_date column exists or order_date
            // We group by customer_id and period_month
            $sourceRows = $sourceDb->select("
                SELECT customer_id, DATE_TRUNC('month', payment_date) as period_month, SUM(amount) as payment_amount
                FROM {$sourceTable}
                GROUP BY customer_id, DATE_TRUNC('month', payment_date)
                ORDER BY customer_id, period_month ASC
            ");

            // Perform calculations
            $computedRows = [];
            $customerBalances = [];

            foreach ($sourceRows as $row) {
                $customerId = $row->customer_id;
                $periodMonth = Carbon::parse($row->period_month)->toDateString();
                $paymentAmount = (float) $row->payment_amount;

                $beginningBalance = $customerBalances[$customerId] ?? 0.00;
                $endingBalance = $beginningBalance + $paymentAmount;

                // Update cumulative balance for this customer
                $customerBalances[$customerId] = $endingBalance;

                $computedRows[] = [
                    'period_month' => $periodMonth,
                    'customer_id' => $customerId,
                    'beginning_balance' => $beginningBalance,
                    'payment_amount' => $paymentAmount,
                    'ending_balance' => $endingBalance,
                ];
            }

            // Truncate target table to refresh data
            if ($targetConn->driver === 'sqlite' || $targetDb->getDriverName() === 'sqlite') {
                $targetDb->statement("DELETE FROM {$targetTable}");
            } else {
                $targetDb->statement("TRUNCATE TABLE {$targetTable} RESTART IDENTITY");
            }

            // Bulk Insert computed rows
            $batch = [];
            $count = 0;
            foreach ($computedRows as $row) {
                $batch[] = [
                    'period_month' => $row['period_month'],
                    'customer_id' => $row['customer_id'],
                    'beginning_balance' => $row['beginning_balance'],
                    'payment_amount' => $row['payment_amount'],
                    'ending_balance' => $row['ending_balance'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                if (count($batch) >= 500) {
                    $targetDb->table($targetTable)->insert($batch);
                    $count += count($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                $targetDb->table($targetTable)->insert($batch);
                $count += count($batch);
            }

            // Update warehouse tables metadata so it shows in the Warehouse Explorer
            $this->updateWarehouseMetadata($pipeline, $targetTable, $count, 6);

            return [
                'read' => count($sourceRows),
                'written' => $count,
                'rejected' => 0
            ];
        }

        // Generic fallback copy table
        if ($targetConn->driver === 'pgsql') {
            $targetDb->statement("CREATE TABLE IF NOT EXISTS {$targetTable} AS SELECT * FROM {$sourceTable} WITH NO DATA");
        }
        
        $sourceRows = $sourceDb->select("SELECT * FROM {$sourceTable}");
        if ($targetConn->driver === 'sqlite' || $targetDb->getDriverName() === 'sqlite') {
            $targetDb->statement("DELETE FROM {$targetTable}");
        } else {
            $targetDb->statement("TRUNCATE TABLE {$targetTable}");
        }
        
        $insertedCount = 0;
        $mappings = $pipeline->column_mapping ?? [];
        
        if (empty($mappings)) {
            // Direct insert if no mapping
            foreach ($sourceRows as $row) {
                $targetDb->table($targetTable)->insert((array) $row);
                $insertedCount++;
            }
        } else {
            foreach ($sourceRows as $row) {
                $rowData = (array) $row;
                $insertData = [];
                foreach ($mappings as $map) {
                    $srcCol = $map['source'];
                    $tgtCol = $map['target'];
                    
                    // Handle virtual / calculation fields mapping
                    if (str_contains($srcCol, '[Kalkulasi:')) {
                        preg_match('/\[Kalkulasi:\s*(.*?)\s*\]/i', $srcCol, $fMatches);
                        $formula = $fMatches[1] ?? '';
                        
                        if (str_contains($formula, '||')) {
                            $parts = explode('||', $formula);
                            $val = '';
                            foreach ($parts as $part) {
                                $part = trim($part);
                                if ((str_starts_with($part, "'") && str_ends_with($part, "'")) || 
                                    (str_starts_with($part, '"') && str_ends_with($part, '"'))) {
                                    $val .= substr($part, 1, -1);
                                } else {
                                    $val .= $rowData[$part] ?? '';
                                }
                            }
                            $insertData[$tgtCol] = $val;
                        } elseif (str_contains($formula, '+')) {
                            $parts = explode('+', $formula);
                            $numeric = true;
                            $numericVal = 0.0;
                            $stringVal = '';
                            foreach ($parts as $part) {
                                $part = trim($part);
                                if ((str_starts_with($part, "'") && str_ends_with($part, "'")) || 
                                    (str_starts_with($part, '"') && str_ends_with($part, '"'))) {
                                    $numeric = false;
                                    $stringVal .= substr($part, 1, -1);
                                } else {
                                    $val = $rowData[$part] ?? null;
                                    if (is_numeric($val)) {
                                        $numericVal += (float)$val;
                                    } else {
                                        $numeric = false;
                                        $stringVal .= (string)$val;
                                    }
                                }
                            }
                            $insertData[$tgtCol] = $numeric ? $numericVal : $stringVal;
                        }
                        continue;
                    }

                    if (str_contains($srcCol, '[Serial') || str_contains($srcCol, '[Sequence')) {
                        $insertData[$tgtCol] = $insertedCount + 1;
                        continue;
                    }

                    if (str_contains($srcCol, '[Lookup')) {
                        $insertData[$tgtCol] = 0.00;
                        continue;
                    }
                    
                    if (isset($rowData[$srcCol])) {
                        $insertData[$tgtCol] = $rowData[$srcCol];
                    }
                }
                if (!empty($insertData)) {
                    $insertData['created_at'] = Carbon::now();
                    $insertData['updated_at'] = Carbon::now();
                    $targetDb->table($targetTable)->insert($insertData);
                    $insertedCount++;
                }
            }
        }

        // Update warehouse metadata
        $colCount = count($mappings) ?: 5;
        $this->updateWarehouseMetadata($pipeline, $targetTable, $insertedCount, $colCount);

        return [
            'read' => count($sourceRows),
            'written' => $insertedCount,
            'rejected' => count($sourceRows) - $insertedCount
        ];
    }

    private function updateWarehouseMetadata(StudioPipeline $pipeline, string $tableName, int $rowCount, int $colCount): void
    {
        // Find or create in warehouse_tables
        $table = WarehouseTable::updateOrCreate(
            ['name' => $tableName],
            [
                'row_count' => $rowCount,
                'col_count' => $colCount,
                'source_system' => 'ETL Studio: ' . $pipeline->name,
                'last_refresh' => Carbon::now(),
                'quality_score' => 100,
                'description' => "Tabel target yang dimuat oleh pipeline '{$pipeline->name}' secara fisik.",
            ]
        );

        // Update columns
        WarehouseColumn::where('table_id', $table->id)->delete();
        
        $mappings = $pipeline->column_mapping ?? [];
        if (!empty($mappings)) {
            foreach ($mappings as $map) {
                WarehouseColumn::create([
                    'table_id' => $table->id,
                    'name' => $map['target'],
                    'data_type' => 'VARCHAR(255)', // fallback
                    'is_nullable' => 'YES',
                    'distinct_count' => $rowCount > 0 ? rand(1, $rowCount) : 0,
                    'missing_percentage' => 0.00
                ]);
            }
        }
    }

    private function executeCanvasPipeline(StudioPipeline $pipeline, array $canvas, $sourceDb, $targetDb): array
    {
        $nodes = $canvas['nodes'];
        $connections = $canvas['connections'] ?? [];

        // Adjacency, Parents, Indegrees
        $adj = [];
        $parents = [];
        $indegree = [];

        foreach ($nodes as $node) {
            $id = $node['id'];
            $adj[$id] = [];
            $parents[$id] = [];
            $indegree[$id] = 0;
        }

        // Flatten connections
        $flatConnections = [];
        foreach ($connections as $connItem) {
            if (is_array($connItem)) {
                if (array_key_exists('from', $connItem) || array_key_exists('fromNodeId', $connItem)) {
                    $flatConnections[] = $connItem;
                } else {
                    foreach ($connItem as $subItem) {
                        if (is_array($subItem)) {
                            $flatConnections[] = $subItem;
                        }
                    }
                }
            }
        }

        foreach ($flatConnections as $c) {
            $from = $c['from'] ?? $c['fromNodeId'] ?? null;
            $to = $c['to'] ?? $c['toNodeId'] ?? null;

            if (isset($adj[$from]) && isset($adj[$to])) {
                $adj[$from][] = $to;
                $parents[$to][] = $from;
                $indegree[$to]++;
            }
        }

        // Topological Sort
        $queue = [];
        foreach ($nodes as $node) {
            $id = $node['id'];
            if ($indegree[$id] === 0) {
                $queue[] = $id;
            }
        }

        $nodeIdx = [];
        foreach ($nodes as $idx => $node) {
            $nodeIdx[$node['id']] = $idx;
        }

        $nodeData = [];
        $rowsRead = 0;
        $rowsWritten = 0;

        while (!empty($queue)) {
            $uId = array_shift($queue);
            if (!isset($nodeIdx[$uId])) {
                continue;
            }
            $uIdx = $nodeIdx[$uId];
            $u = $nodes[$uIdx];

            $name = $u['name'] ?? '';
            $type = $u['type'] ?? '';
            $settings = $u['settings'] ?? [];

            // Combine parent datasets
            $incomingRows = [];
            $pIds = $parents[$uId] ?? [];
            
            if ($name === 'Join') {
                $leftId = $pIds[0] ?? null;
                $rightId = $pIds[1] ?? null;
                $leftRows = $leftId ? ($nodeData[$leftId] ?? []) : [];
                $rightRows = $rightId ? ($nodeData[$rightId] ?? []) : [];
                $incomingRows = $this->executeJoin($leftRows, $rightRows, $settings);
            } else {
                if (count($pIds) === 1) {
                    $incomingRows = $nodeData[$pIds[0]] ?? [];
                } elseif (count($pIds) > 1) {
                    foreach ($pIds as $pid) {
                        $incomingRows = array_merge($incomingRows, $nodeData[$pid] ?? []);
                    }
                }
            }

            // Execute this step
            $rows = $incomingRows;
            if ($type === 'input' || $name === 'source' || $name === 'Database Input') {
                $sql = $settings['sql'] ?? "SELECT * FROM {$pipeline->source_table}";
                $rows = array_map(fn($r) => (array) $r, $sourceDb->select($sql));
                $rowsRead = count($rows);
            } elseif ($name === 'CSV Input' || $name === 'Excel Input' || $name === 'JSON Input') {
                $sql = "SELECT * FROM {$pipeline->source_table}";
                $rows = array_map(fn($r) => (array) $r, $sourceDb->select($sql));
                $rowsRead = count($rows);
            } elseif ($name === 'Select Values') {
                $rows = $this->executeSelectValues($incomingRows, $settings);
            } elseif ($name === 'Calculator') {
                $rows = $this->executeCalculator($incomingRows, $settings);
            } elseif ($name === 'Formula') {
                $rows = $this->executeFormula($incomingRows, $settings);
            } elseif ($name === 'Filter Rows') {
                $rows = $this->executeFilterRows($incomingRows, $settings);
            } elseif ($name === 'Sort Rows') {
                $rows = $this->executeSortRows($incomingRows, $settings);
            } elseif ($name === 'Unique Rows') {
                $rows = $this->executeUniqueRows($incomingRows, $settings);
            } elseif ($name === 'Group By') {
                $rows = $this->executeGroupBy($incomingRows, $settings);
            }

            $nodeData[$uId] = $rows;

            if ($type === 'output' || $name === 'target' || $name === 'Table Output') {
                $targetTable = $pipeline->target_table;
                
                // Ensure target schema/table is created if target relation has dot
                if (str_contains($targetTable, '.')) {
                    $parts = explode('.', $targetTable);
                    $schema = $parts[0];
                    if ($pipeline->targetConnection->driver === 'pgsql') {
                        $targetDb->statement("CREATE SCHEMA IF NOT EXISTS {$schema}");
                    }
                }
                
                if ($pipeline->targetConnection->driver === 'pgsql') {
                    $targetDb->statement("CREATE TABLE IF NOT EXISTS {$targetTable} AS SELECT * FROM {$pipeline->source_table} WITH NO DATA");
                }
                if ($pipeline->targetConnection->driver === 'sqlite' || $targetDb->getDriverName() === 'sqlite') {
                    $targetDb->statement("DELETE FROM {$targetTable}");
                } else {
                    $targetDb->statement("TRUNCATE TABLE {$targetTable}");
                }

                $commitSize = $settings['commit_size'] ?? 1000;
                $batch = [];
                $mappings = $pipeline->column_mapping ?? [];

                foreach ($rows as $row) {
                    $insertData = [];
                    if (empty($mappings)) {
                        $insertData = $row;
                    } else {
                        foreach ($mappings as $map) {
                            $srcCol = $map['source'];
                            $tgtCol = $map['target'];

                            if (str_contains($srcCol, '[Kalkulasi:')) {
                                preg_match('/\[Kalkulasi:\s*(.*?)\s*\]/i', $srcCol, $fMatches);
                                $formula = $fMatches[1] ?? '';
                                $rowData = $row;
                                
                                if (str_contains($formula, '||')) {
                                    $parts = explode('||', $formula);
                                    $val = '';
                                    foreach ($parts as $part) {
                                        $part = trim($part);
                                        if ((str_starts_with($part, "'") && str_ends_with($part, "'")) || 
                                            (str_starts_with($part, '"') && str_ends_with($part, '"'))) {
                                            $val .= substr($part, 1, -1);
                                        } else {
                                            $val .= $rowData[$part] ?? '';
                                        }
                                    }
                                    $insertData[$tgtCol] = $val;
                                } elseif (str_contains($formula, '+')) {
                                    $parts = explode('+', $formula);
                                    $numeric = true;
                                    $numericVal = 0.0;
                                    $stringVal = '';
                                    foreach ($parts as $part) {
                                        $part = trim($part);
                                        if ((str_starts_with($part, "'") && str_ends_with($part, "'")) || 
                                            (str_starts_with($part, '"') && str_ends_with($part, '"'))) {
                                            $numeric = false;
                                            $stringVal .= substr($part, 1, -1);
                                        } else {
                                            $val = $rowData[$part] ?? null;
                                            if (is_numeric($val)) {
                                                $numericVal += (float)$val;
                                            } else {
                                                $numeric = false;
                                                $stringVal .= (string)$val;
                                            }
                                        }
                                    }
                                    $insertData[$tgtCol] = $numeric ? $numericVal : $stringVal;
                                }
                                continue;
                            }

                            if (str_contains($srcCol, '[Serial') || str_contains($srcCol, '[Sequence')) {
                                $insertData[$tgtCol] = $rowsWritten + count($batch) + 1;
                                continue;
                            }

                            if (str_contains($srcCol, '[Lookup')) {
                                $insertData[$tgtCol] = 0.00;
                                continue;
                            }

                            if (isset($row[$srcCol])) {
                                $insertData[$tgtCol] = $row[$srcCol];
                            }
                        }
                    }

                    if (!empty($insertData)) {
                        $insertData['created_at'] = Carbon::now();
                        $insertData['updated_at'] = Carbon::now();
                        
                        // Strip out target table columns that don't exist in target schema if possible, or direct save
                        $batch[] = $insertData;

                        if (count($batch) >= $commitSize) {
                            $targetDb->table($targetTable)->insert($batch);
                            $rowsWritten += count($batch);
                            $batch = [];
                        }
                    }
                }

                if (!empty($batch)) {
                    $targetDb->table($targetTable)->insert($batch);
                    $rowsWritten += count($batch);
                }

                $colCount = count($mappings) ?: 5;
                $this->updateWarehouseMetadata($pipeline, $targetTable, $rowsWritten, $colCount);
            }

            foreach ($adj[$uId] as $vId) {
                $indegree[$vId]--;
                if ($indegree[$vId] === 0) {
                    $queue[] = $vId;
                }
            }
        }

        return [
            'read' => $rowsRead,
            'written' => $rowsWritten,
            'rejected' => max(0, $rowsRead - $rowsWritten)
        ];
    }

    private function executeSelectValues(array $rows, array $settings): array
    {
        $selectAlter = $settings['select_alter'] ?? [];
        $remove = $settings['remove'] ?? [];
        
        $result = [];
        foreach ($rows as $row) {
            $newRow = [];
            if (!empty($selectAlter)) {
                foreach ($selectAlter as $sa) {
                    $field = $sa['field'] ?? '';
                    if ($field && array_key_exists($field, $row)) {
                        $rename = $sa['rename'] ?? '';
                        $newKey = $rename ?: $field;
                        $newRow[$newKey] = $row[$field];
                    }
                }
            } else {
                $newRow = $row;
            }

            if (!empty($remove)) {
                foreach ($remove as $rem) {
                    $field = $rem['field'] ?? '';
                    if ($field) {
                        unset($newRow[$field]);
                    }
                }
            }
            $result[] = $newRow;
        }
        return $result;
    }

    private function executeCalculator(array $rows, array $settings): array
    {
        $calculations = $settings['calculations'] ?? [];
        foreach ($rows as &$row) {
            foreach ($calculations as $c) {
                $fieldName = $c['field_name'] ?? '';
                if (!$fieldName) continue;

                $type = $c['calculation_type'] ?? $c['calc_type'] ?? '';
                $fieldA = $c['field_a'] ?? '';
                $fieldB = $c['field_b'] ?? '';

                $valA = $row[$fieldA] ?? null;
                $valB = $row[$fieldB] ?? null;

                $resultVal = null;
                switch ($type) {
                    case 'Add':
                    case 'Add (A + B)':
                    case 'A + B':
                        $resultVal = (float)$valA + (float)$valB;
                        break;
                    case 'Subtract':
                    case 'Subtract (A - B)':
                    case 'A - B':
                        $resultVal = (float)$valA - (float)$valB;
                        break;
                    case 'Multiply':
                    case 'Multiply (A * B)':
                    case 'A * B':
                        $resultVal = (float)$valA * (float)$valB;
                        break;
                    case 'Divide':
                    case 'Divide (A / B)':
                    case 'A / B':
                        $resultVal = $valB ? ((float)$valA / (float)$valB) : 0.0;
                        break;
                    case 'Concat (A + B)':
                    case 'CONCAT':
                        $resultVal = (string)$valA . (string)$valB;
                        break;
                    case 'Uppercase A':
                    case 'UPPER':
                        $resultVal = strtoupper((string)$valA);
                        break;
                    case 'Lowercase A':
                    case 'LOWER':
                        $resultVal = strtolower((string)$valA);
                        break;
                    default:
                        $resultVal = $valA;
                        break;
                }
                $row[$fieldName] = $resultVal;
            }
        }
        return $rows;
    }

    private function executeFormula(array $rows, array $settings): array
    {
        $fieldName = $settings['field_name'] ?? '';
        $formula = $settings['formula'] ?? '';

        if (!$fieldName || !$formula) {
            return $rows;
        }

        foreach ($rows as &$row) {
            $resultVal = null;

            if (preg_match('/upper\((.*?)\)/i', $formula, $matches)) {
                $colName = trim($matches[1]);
                $resultVal = strtoupper((string)($row[$colName] ?? ''));
            }
            elseif (preg_match('/lower\((.*?)\)/i', $formula, $matches)) {
                $colName = trim($matches[1]);
                $resultVal = strtolower((string)($row[$colName] ?? ''));
            }
            elseif (preg_match('/concat\((.*?)\)/i', $formula, $matches)) {
                $argsStr = $matches[1];
                $args = str_getcsv($argsStr, ',');
                $concatVal = '';
                foreach ($args as $arg) {
                    $arg = trim($arg);
                    if ((str_starts_with($arg, "'") && str_ends_with($arg, "'")) || 
                        (str_starts_with($arg, '"') && str_ends_with($arg, '"'))) {
                        $concatVal .= substr($arg, 1, -1);
                    } else {
                        $concatVal .= (string)($row[$arg] ?? '');
                    }
                }
                $resultVal = $concatVal;
            }
            elseif (str_contains($formula, '+')) {
                $parts = explode('+', $formula);
                $numeric = true;
                $numericVal = 0.0;
                $stringVal = '';
                foreach ($parts as $part) {
                    $part = trim($part);
                    if ((str_starts_with($part, "'") && str_ends_with($part, "'")) || 
                        (str_starts_with($part, '"') && str_ends_with($part, '"'))) {
                        $numeric = false;
                        $stringVal .= substr($part, 1, -1);
                    } else {
                        $val = $row[$part] ?? null;
                        if (is_numeric($val)) {
                            $numericVal += (float)$val;
                        } else {
                            $numeric = false;
                            $stringVal .= (string)$val;
                        }
                    }
                }
                $resultVal = $numeric ? $numericVal : $stringVal;
            }
            else {
                $resultVal = $row[$formula] ?? null;
            }

            $row[$fieldName] = $resultVal;
        }

        return $rows;
    }

    private function executeFilterRows(array $rows, array $settings): array
    {
        $condition = $settings['condition'] ?? [];
        $type = $condition['type'] ?? 'AND';
        $rules = $condition['rules'] ?? [];

        if (empty($rules)) {
            return $rows;
        }

        return array_values(array_filter($rows, function ($row) use ($type, $rules) {
            $matches = [];
            foreach ($rules as $rule) {
                $field = $rule['field'] ?? '';
                $op = $rule['op'] ?? '=';
                $val = $rule['value'] ?? '';

                if (!$field) {
                    $matches[] = true;
                    continue;
                }

                $actual = $row[$field] ?? null;
                $match = false;
                switch ($op) {
                    case '=':
                        $match = $actual == $val;
                        break;
                    case '!=':
                        $match = $actual != $val;
                        break;
                    case '>':
                        $match = (float)$actual > (float)$val;
                        break;
                    case '<':
                        $match = (float)$actual < (float)$val;
                        break;
                    case '>=':
                        $match = (float)$actual >= (float)$val;
                        break;
                    case '<=':
                        $match = (float)$actual <= (float)$val;
                        break;
                    case 'LIKE':
                        $pattern = str_replace('%', '.*', preg_quote($val, '/'));
                        $match = preg_match('/^' . $pattern . '$/i', (string)$actual);
                        break;
                    case 'IS NULL':
                        $match = is_null($actual) || $actual === '';
                        break;
                    default:
                        $match = $actual == $val;
                        break;
                }
                $matches[] = $match;
            }

            if ($type === 'AND') {
                return !in_array(false, $matches, true);
            } elseif ($type === 'OR') {
                return in_array(true, $matches, true);
            } elseif ($type === 'NOT') {
                return in_array(false, $matches, true);
            }
            return true;
        }));
    }

    private function executeSortRows(array $rows, array $settings): array
    {
        $sortFields = $settings['fields'] ?? [];
        if (empty($sortFields)) {
            return $rows;
        }

        usort($rows, function ($a, $b) use ($sortFields) {
            foreach ($sortFields as $sf) {
                $field = $sf['field'] ?? '';
                if (!$field) continue;

                $asc = $sf['ascending'] ?? true;
                $case = $sf['case_sensitive'] ?? false;

                $valA = $a[$field] ?? '';
                $valB = $b[$field] ?? '';

                if (!$case) {
                    $valA = strtolower((string)$valA);
                    $valB = strtolower((string)$valB);
                }

                if ($valA == $valB) {
                    continue;
                }

                if ($asc) {
                    return $valA < $valB ? -1 : 1;
                } else {
                    return $valA > $valB ? -1 : 1;
                }
            }
            return 0;
        });

        return $rows;
    }

    private function executeUniqueRows(array $rows, array $settings): array
    {
        $compareFields = $settings['compare_fields'] ?? [];
        if (empty($compareFields)) {
            return $rows;
        }

        $case = $settings['case_sensitive'] ?? true;
        $seen = [];
        $result = [];

        foreach ($rows as $row) {
            $keyParts = [];
            foreach ($compareFields as $field) {
                $val = $row[$field] ?? '';
                if (!$case) {
                    $val = strtolower((string)$val);
                }
                $keyParts[] = $val;
            }
            $key = implode('||', $keyParts);

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $row;
            }
        }

        return $result;
    }

    private function executeGroupBy(array $rows, array $settings): array
    {
        $groupFields = $settings['group_fields'] ?? [];
        $aggregations = $settings['aggregations'] ?? [];

        $groups = [];
        foreach ($rows as $row) {
            $keyParts = [];
            foreach ($groupFields as $gf) {
                if (!empty($gf['field'])) {
                    $keyParts[] = (string)($row[$gf['field']] ?? '');
                }
            }
            $key = implode('||', $keyParts);

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'group_values' => [],
                    'rows' => []
                ];
                foreach ($groupFields as $gf) {
                    if (!empty($gf['field'])) {
                        $groups[$key]['group_values'][$gf['field']] = $row[$gf['field']] ?? null;
                    }
                }
            }
            $groups[$key]['rows'][] = $row;
        }

        $result = [];
        foreach ($groups as $g) {
            $newRow = $g['group_values'];
            foreach ($aggregations as $agg) {
                $fieldName = $agg['field_name'] ?? '';
                $subject = $agg['subject'] ?? $agg['field'] ?? '';
                $aggType = $agg['type'] ?? 'SUM';

                if (!$fieldName || !$subject) continue;

                $vals = array_column($g['rows'], $subject);
                $aggVal = null;
                switch ($aggType) {
                    case 'SUM':
                        $aggVal = array_sum(array_map('floatval', $vals));
                        break;
                    case 'COUNT':
                        $aggVal = count($vals);
                        break;
                    case 'AVG':
                        $aggVal = count($vals) > 0 ? (array_sum(array_map('floatval', $vals)) / count($vals)) : 0.0;
                        break;
                    case 'MAX':
                        $aggVal = count($vals) > 0 ? max(array_map('floatval', $vals)) : null;
                        break;
                    case 'MIN':
                        $aggVal = count($vals) > 0 ? min(array_map('floatval', $vals)) : null;
                        break;
                    default:
                        $aggVal = null;
                        break;
                }
                $newRow[$fieldName] = $aggVal;
            }
            $result[] = $newRow;
        }

        return $result;
    }

    private function executeJoin(array $leftRows, array $rightRows, array $settings): array
    {
        $joinType = $settings['join_type'] ?? 'Inner';
        $keys = $settings['keys'] ?? [];

        $leftKey = $keys[0]['left_key'] ?? $keys[0]['left'] ?? '';
        $rightKey = $keys[0]['right_key'] ?? $keys[0]['right'] ?? '';

        if (!$leftKey || !$rightKey) {
            $result = [];
            foreach ($leftRows as $lr) {
                foreach ($rightRows as $rr) {
                    $result[] = array_merge($lr, $rr);
                }
            }
            return $result;
        }

        $result = [];
        foreach ($leftRows as $lr) {
            $lVal = $lr[$leftKey] ?? null;
            $matched = false;

            foreach ($rightRows as $rr) {
                $rVal = $rr[$rightKey] ?? null;
                if ($lVal == $rVal) {
                    $result[] = array_merge($lr, $rr);
                    $matched = true;
                }
            }

            if (!$matched && strtolower($joinType) === 'left') {
                $nullRow = array_fill_keys(array_keys($rightRows[0] ?? []), null);
                $result[] = array_merge($lr, $nullRow);
            }
        }

        if (strtolower($joinType) === 'right') {
            foreach ($rightRows as $rr) {
                $rVal = $rr[$rightKey] ?? null;
                $matched = false;

                foreach ($leftRows as $lr) {
                    $lVal = $lr[$leftKey] ?? null;
                    if ($lVal == $rVal) {
                        $matched = true;
                        break;
                    }
                }

                if (!$matched) {
                    $nullRow = array_fill_keys(array_keys($leftRows[0] ?? []), null);
                    $result[] = array_merge($nullRow, $rr);
                }
            }
        }

        return $result;
    }
}
