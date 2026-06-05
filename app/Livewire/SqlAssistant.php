<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\GeminiService;
use App\Models\QueryHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SqlAssistant extends Component
{
    public string $query = '';
    public string $generatedSql = '';
    public string $explanation = '';
    public string $errorMessage = '';

    public array $history = [];
    public array $queryResult = ['headers' => [], 'rows' => []];
    public string $chartType = 'none'; // none | bar | line | pie
    public string $chartData = '[]';

    public function mount(): void
    {
        $this->history[] = [
            'role' => 'assistant',
            'content' => 'Halo! Saya adalah AI SQL Assistant Anda. Ketik pertanyaan Anda dalam bahasa natural (contoh: *"Tampilkan 10 customer dengan transaksi terbesar"* atau *"Berapa total penjualan per kategori produk?"*), lalu saya akan membuatkan query SQL-nya, menjalankannya, serta menyajikan visualisasinya secara otomatis.'
        ];
    }

    public function submitChat(): void
    {
        $userPrompt = trim($this->query);
        if (empty($userPrompt)) return;

        $this->history[] = ['role' => 'user', 'content' => $userPrompt];
        $this->query = '';
        $this->errorMessage = '';

        try {
            $gemini = app(GeminiService::class);
            $sql = $gemini->translateNaturalQueryToSql($userPrompt);

            if ($sql) {
                $this->generatedSql = $sql;
                
                // Explain
                $this->explanation = $gemini->explainSqlQuery($sql) ?? 'Berhasil menerjemahkan prompt ke SQL.';
                
                $this->history[] = [
                    'role' => 'assistant',
                    'content' => "Berikut adalah query SQL yang telah saya buat berdasarkan permintaan Anda:\n\n```sql\n{$this->generatedSql}\n```\n\n**Penjelasan:** {$this->explanation}"
                ];

                // Auto execute
                $this->executeSql();
            } else {
                $this->history[] = [
                    'role' => 'assistant',
                    'content' => 'Maaf, saya gagal menerjemahkan permintaan Anda menjadi SQL. Silakan periksa kembali detail pertanyaan Anda.'
                ];
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            Log::error("SqlAssistant::submitChat error: " . $e->getMessage());
        }
    }

    public function explainSql(): void
    {
        if (empty($this->generatedSql)) return;
        
        $this->errorMessage = '';
        try {
            $gemini = app(GeminiService::class);
            $this->explanation = $gemini->explainSqlQuery($this->generatedSql) ?? 'Tidak dapat menghasilkan penjelasan.';
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function executeSql(): void
    {
        $sql = trim($this->generatedSql);
        if (empty($sql)) return;

        $this->errorMessage = '';
        $this->queryResult = ['headers' => [], 'rows' => []];
        $this->chartType = 'none';

        // Strict read-only validation
        $upperSql = strtoupper($sql);
        
        // Block modification commands
        $forbiddenKeywords = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE', 'TRUNCATE', 'RENAME', 'GRANT', 'REVOKE'];
        foreach ($forbiddenKeywords as $keyword) {
            if (strpos($upperSql, $keyword) !== false) {
                $this->errorMessage = "Operasi ditolak. SQL Assistant hanya mendukung perintah SELECT (read-only) demi keamanan data.";
                return;
            }
        }

        if (strpos($upperSql, 'SELECT') === false) {
            $this->errorMessage = "Operasi tidak didukung. Harap gunakan kueri SELECT yang valid.";
            return;
        }

        try {
            // Execute physical query
            $rawResult = DB::select($sql);
            
            if (!empty($rawResult)) {
                $headers = array_keys((array) $rawResult[0]);
                $rows = array_map(fn($r) => (array) $r, $rawResult);
                
                $this->queryResult = [
                    'headers' => $headers,
                    'rows' => $rows
                ];

                // Save to Query History
                QueryHistory::create([
                    'natural_query' => end($this->history)['content'] ?? 'Manual Execute',
                    'generated_sql' => $sql,
                    'execution_status' => 'success'
                ]);

                // Auto determine chart visualization
                $this->autoDetectChart($headers, $rows);
            } else {
                $this->errorMessage = "Query berhasil dijalankan, tetapi tidak mengembalikan baris data.";
            }

        } catch (\Exception $e) {
            $this->errorMessage = "Gagal menjalankan kueri SQL: " . $e->getMessage();
            Log::error("SqlAssistant::executeSql query exception: " . $e->getMessage());

            QueryHistory::create([
                'natural_query' => end($this->history)['content'] ?? 'Manual Execute Failed',
                'generated_sql' => $sql,
                'execution_status' => 'failed',
                'execution_error' => $e->getMessage()
            ]);
        }
    }

    private function autoDetectChart(array $headers, array $rows): void
    {
        if (count($rows) < 1) return;

        // Find numeric columns and categoric columns
        $labelCol = null;
        $valueCol = null;

        $firstRow = $rows[0];
        foreach ($firstRow as $key => $val) {
            if (is_numeric($val) && $key !== 'id' && strpos(strtolower($key), 'id') === false) {
                $valueCol = $key;
            } else {
                if ($labelCol === null) {
                    $labelCol = $key;
                }
            }
        }

        // Default if not detected
        if (!$valueCol && count($headers) >= 2) {
            $valueCol = $headers[count($headers) - 1];
        }
        if (!$labelCol && count($headers) >= 1) {
            $labelCol = $headers[0];
        }

        if ($labelCol && $valueCol) {
            $dataPoints = [];
            foreach ($rows as $row) {
                $dataPoints[] = [
                    'label' => (string) $row[$labelCol],
                    'value' => (float) $row[$valueCol]
                ];
            }

            $this->chartData = json_encode($dataPoints);
            // Default chart is bar
            $this->chartType = 'bar';
            
            // If date/time in label, line chart is better
            if (strpos(strtolower($labelCol), 'date') !== false || strpos(strtolower($labelCol), 'month') !== false) {
                $this->chartType = 'line';
            }
            
            // Dispatch event to Alpine/JS to draw the chart
            $this->dispatch('render-sql-chart', type: $this->chartType, data: $this->chartData);
        }
    }

    public function exportCsv()
    {
        if (empty($this->queryResult['rows'])) return null;

        $headers = $this->queryResult['headers'];
        $rows = $this->queryResult['rows'];

        $callback = function() use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, array_values($row));
            }
            fclose($file);
        };

        $fileName = 'query_result_' . date('Ymd_His') . '.csv';
        return response()->stream($callback, 200, [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ]);
    }

    public function render()
    {
        return view('livewire.sql-assistant');
    }
}
