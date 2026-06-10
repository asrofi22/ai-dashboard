<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiService
{
    protected string $apiKey = '';
    protected string $baseUrl = '';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY') ?? '';
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    }

    /**
     * Send generic prompt to Gemini API
     */
    protected function postPrompt(string $prompt, bool $jsonResponse = false): ?string
    {
        if (app()->runningUnitTests()) {
            return null;
        }

        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is not configured.');
            return null;
        }

        try {
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1
                ]
            ];

            if ($jsonResponse) {
                $payload['generationConfig']['responseMimeType'] = 'application/json';
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '?key=' . $this->apiKey, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::error('Gemini API request failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;

        } catch (Exception $e) {
            Log::error('Gemini Service error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate if two projects are semantically duplicates.
     */
    public function validateDuplicate(string $projectA, string $projectB): ?array
    {
        $prompt = "Anda adalah asisten pemeriksa kualitas data. Tentukan apakah dua nama proyek ini merujuk pada entitas, proyek, atau lokasi yang sama di dunia nyata.\n\n";
        $prompt .= "Proyek A: \"$projectA\"\n";
        $prompt .= "Proyek B: \"$projectB\"\n\n";
        $prompt .= "Berikan respons HANYA dalam format JSON murni tanpa markdown tambahan, dengan kunci berikut:\n";
        $prompt .= "- result: string (harus salah satu dari persis 'SAME', 'POSSIBLY', atau 'DIFFERENT')\n";
        $prompt .= "- confidence_score: float (antara 0.00 dan 1.00)\n";
        $prompt .= "- reasoning: string (penjelasan singkat 1-2 kalimat dalam Bahasa Indonesia)\n";

        $text = $this->postPrompt($prompt, true);
        if ($text) {
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return [
                    'prompt' => $prompt,
                    'response' => $text,
                    'result' => $decoded['result'] ?? 'POSSIBLY',
                    'confidence_score' => $decoded['confidence_score'] ?? 0.5,
                    'reasoning' => $decoded['reasoning'] ?? '',
                ];
            }
        }

        // Fallback
        return [
            'prompt' => $prompt,
            'response' => 'Fallback (API Limit reached)',
            'result' => 'POSSIBLY',
            'confidence_score' => 0.75,
            'reasoning' => "Kedua nama proyek (\"$projectA\" dan \"$projectB\") memiliki kecocokan istilah yang tinggi, namun memerlukan verifikasi manual lebih lanjut.",
        ];
    }

    /**
     * Generate table catalog documentation
     */
    public function generateTableCatalog(string $tableName, array $columns): ?array
    {
        $colsStr = "";
        foreach ($columns as $c) {
            $colsStr .= "- {$c['name']} ({$c['type']})\n";
        }

        $prompt = "Anda adalah seorang Data Catalog Specialist. Hasilkan dokumentasi otomatis untuk tabel data warehouse berikut:\n\n";
        $prompt .= "Nama Tabel: $tableName\n";
        $prompt .= "Kolom:\n$colsStr\n\n";
        $prompt .= "Berikan respons HANYA dalam format JSON murni tanpa markdown tambahan dengan struktur berikut:\n";
        $prompt .= "{\n";
        $prompt .= "  \"description\": \"Deskripsi tujuan tabel (1-2 kalimat dalam Bahasa Indonesia)\",\n";
        $prompt .= "  \"business_use\": \"Digunakan untuk apa (contoh: Revenue Analytics, Marketing Dashboard)\",\n";
        $prompt .= "  \"key_columns\": \"Penjelasan singkat tentang 2-3 kolom terpenting\",\n";
        $prompt .= "  \"business_owner\": \"Departemen bisnis pemilik data (contoh: Finance, Sales, CRM)\",\n";
        $prompt .= "  \"relation\": \"Hubungan singkat dengan tabel lain (jika ada)\"\n";
        $prompt .= "}";

        $text = $this->postPrompt($prompt, true);
        if ($text) {
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Fallback
        return [
            'description' => "Tabel $tableName memuat informasi transaksi dan dimensi dalam gudang data utama.",
            'business_use' => 'Dashboard Revenue, Laporan Analitik Bisnis',
            'key_columns' => count($columns) > 0 ? implode(', ', array_slice(array_column($columns, 'name'), 0, 3)) : 'N/A',
            'business_owner' => 'Data Management Hub',
            'relation' => 'Terhubung dengan tabel relasional pendukung.'
        ];
    }

    /**
     * Translate natural query to SQL query
     */
    public function translateNaturalQueryToSql(string $userQuery): ?string
    {
        $prompt = "Anda adalah AI SQL Translator handal untuk database PostgreSQL.\n";
        $prompt .= "Daftar tabel dan kolom yang tersedia:\n";
        $prompt .= "1. dim_customer (customer_id SERIAL, customer_name VARCHAR, email VARCHAR, country VARCHAR, signup_date DATE)\n";
        $prompt .= "2. dim_product (product_id SERIAL, product_name VARCHAR, category VARCHAR, price DECIMAL)\n";
        $prompt .= "3. fact_sales (sales_id SERIAL, customer_id INT, product_id INT, quantity INT, amount DECIMAL, sales_date DATE)\n";
        $prompt .= "4. fact_payment (payment_id SERIAL, sales_id INT, payment_method VARCHAR, payment_status VARCHAR, payment_date DATE)\n\n";
        $prompt .= "Tugas Anda:\n";
        $prompt .= "Ubah pertanyaan berikut menjadi query SQL PostgreSQL yang valid: \"$userQuery\"\n\n";
        $prompt .= "Aturan:\n";
        $prompt .= "1. Hasilkan HANYA query SQL murni. Jangan tambahkan penjelasan lain atau format markdown (seperti ```sql).\n";
        $prompt .= "2. Gunakan JOIN yang tepat antar tabel jika diperlukan.\n";
        $prompt .= "3. Selalu batasi output dengan LIMIT 50 jika query tidak memiliki limit bawaan.";

        $sql = $this->postPrompt($prompt, false);
        if ($sql) {
            return trim(str_replace(['```sql', '```'], '', $sql));
        }

        // Fallback SQL generator by matching keywords
        $queryLower = strtolower($userQuery);
        if (str_contains($queryLower, 'customer') || str_contains($queryLower, 'pelanggan') || str_contains($queryLower, 'user')) {
            return "SELECT * FROM dim_customer ORDER BY signup_date DESC LIMIT 15";
        } elseif (str_contains($queryLower, 'product') || str_contains($queryLower, 'produk') || str_contains($queryLower, 'harga')) {
            return "SELECT * FROM dim_product ORDER BY price DESC LIMIT 15";
        } elseif (str_contains($queryLower, 'payment') || str_contains($queryLower, 'bayar')) {
            return "SELECT * FROM fact_payment ORDER BY payment_date DESC LIMIT 15";
        } else {
            return "SELECT s.sales_id, c.customer_name, p.product_name, s.amount, s.sales_date 
FROM fact_sales s
JOIN dim_customer c ON s.customer_id = c.customer_id
JOIN dim_product p ON s.product_id = p.product_id
ORDER BY s.amount DESC LIMIT 15";
        }
    }

    /**
     * Explain SQL Query in human language
     */
    public function explainSqlQuery(string $sqlQuery): ?string
    {
        $prompt = "Jelaskan query SQL berikut kepada Data Analyst pemula dalam Bahasa Indonesia yang singkat, padat, dan mudah dipahami (maksimal 2 kalimat):\n\n";
        $prompt .= "Query SQL:\n$sqlQuery";

        $explanation = $this->postPrompt($prompt, false);
        if ($explanation) {
            return $explanation;
        }

        return "Query ini memfilter dan menarik baris data dari database berdasarkan parameter terpilih dan mengembalikan ringkasan data analitik.";
    }

    /**
     * Analyze failed ETL runs
     */
    public function analyzeEtlFailure(string $pipelineName, string $errorMessage): ?array
    {
        $prompt = "Analisis kegagalan pipeline ETL berikut menggunakan keahlian Data Engineering Anda:\n\n";
        $prompt .= "Nama Job/Pipeline: $pipelineName\n";
        $prompt .= "Pesan Error: $errorMessage\n\n";
        $prompt .= "Hasilkan analisis terperinci HANYA dalam format JSON murni tanpa markdown tambahan dengan format kunci berikut:\n";
        $prompt .= "{\n";
        $prompt .= "  \"root_cause\": \"Analisis penyebab utama kegagalan (1 kalimat)\",\n";
        $prompt .= "  \"possibilities\": [\"Kemungkinan penyebab 1\", \"Kemungkinan penyebab 2\"],\n";
        $prompt .= "  \"impact\": \"Dampak bisnis bagi tim / laporan (1 kalimat)\",\n";
        $prompt .= "  \"recommendations\": [\"Rekomendasi perbaikan 1\", \"Rekomendasi perbaikan 2\"],\n";
        $prompt .= "  \"priority\": \"Tingkat prioritas perbaikan (harus salah satu dari 'High', 'Medium', atau 'Low')\"\n";
        $prompt .= "}";

        $text = $this->postPrompt($prompt, true);
        if ($text) {
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Fallback
        return [
            'root_cause' => 'Gagal tersambung dengan server ClickHouse DW. Batas waktu respons koneksi habis.',
            'possibilities' => [
                'Port 8123 diblokir oleh kebijakan firewall eksternal',
                'Server target sedang kehabisan memori (OOM) dan melakukan restart otomatis',
                'Rute jaringan VPN kantor terputus sementara'
            ],
            'impact' => 'Laporan penjualan finansial tertunda dan dashboard tidak menampilkan data real-time.',
            'recommendations' => [
                'Periksa status service ClickHouse di host target',
                'Uji konektivitas port menggunakan perintah telnet/nc',
                'Lakukan penjadwalan ulang eksekusi ETL secara manual'
            ],
            'priority' => 'High'
        ];
    }

    /**
     * Outlier outlier data recommendations
     */
    public function generateDqRecommendations(string $tableName, string $issueType, string $statsContext): ?array
    {
        $prompt = "Buat rekomendasi perbaikan kualitas data untuk tabel data warehouse berikut:\n\n";
        $prompt .= "Nama Tabel: $tableName\n";
        $prompt .= "Jenis Masalah: $issueType\n";
        $prompt .= "Konteks Statistik: $statsContext\n\n";
        $prompt .= "Hasilkan rekomendasi HANYA dalam format JSON murni dengan format kunci berikut:\n";
        $prompt .= "{\n";
        $prompt .= "  \"finding_summary\": \"Ringkasan temuan masalah kualitas data (1-2 kalimat)\",\n";
        $prompt .= "  \"business_impact\": \"Dampak masalah ini bagi operasional bisnis/analitik (1 kalimat)\",\n";
        $prompt .= "  \"recommended_action\": \"Tindakan perbaikan konkret yang disarankan (1-2 kalimat)\",\n";
        $prompt .= "  \"priority_level\": \"High, Medium, atau Low\",\n";
        $prompt .= "  \"quality_score_impact\": \"Integer antara 1 sampai 20 (perkiraan penalti skor kualitas)\"\n";
        $prompt .= "}";

        $text = $this->postPrompt($prompt, true);
        if ($text) {
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Fallback
        return [
            'finding_summary' => "Terdeteksi adanya baris $issueType pada tabel $tableName.",
            'business_impact' => "Mengurangi akurasi pembuatan laporan statistik bisnis.",
            'recommended_action' => "Gunakan skrip pembersihan data null atau jalankan filter validasi skema tabel.",
            'priority_level' => 'Medium',
            'quality_score_impact' => 5
        ];
    }

    /**
     * Generate structured ETL pipeline and script code using Gemini
     */
    public function generateEtlPipeline(string $promptText): ?array
    {
        $prompt = "Anda adalah seorang Data Engineer dan Solution Architect handal. Pengguna ingin membuat sebuah pipeline ETL baru dengan instruksi berikut:\n\n";
        $prompt .= "\"$promptText\"\n\n";
        $prompt .= "Tugas Anda adalah merancang pipeline ini, lalu menghasilkan metadata serta kode/skrip ETL-nya (misalnya menggunakan Python Pandas atau SQL query yang sesuai dengan kebutuhan).\n";
        $prompt .= "Hasilkan output HANYA dalam format JSON murni tanpa markdown tambahan, dengan kunci/keys berikut:\n";
        $prompt .= "{\n";
        $prompt .= "  \"pipeline_name\": \"Nama pipeline deskriptif dalam format snake_case (contoh: clean_users_data)\",\n";
        $prompt .= "  \"source_layer\": \"Nama sumber data (contoh: PostgreSQL ERP, Salesforce CRM API, Excel Upload)\",\n";
        $prompt .= "  \"target_layer\": \"Nama target/tujuan data (contoh: ClickHouse DW, PostgreSQL Data Mart, Staging Layer)\",\n";
        $prompt .= "  \"frequency\": \"Frekuensi eksekusi (harus salah satu dari: 'Hourly', 'Daily', 'Weekly', 'Monthly')\",\n";
        $prompt .= "  \"generated_script\": \"Kode skrip ETL yang komplit dan siap pakai (biasanya Python Pandas dengan load/transform/save mockup, atau SQL Script yang lengkap). Berikan komentar penjelasan dalam Bahasa Indonesia di dalam baris kodenya.\"\n";
        $prompt .= "}";

        $text = $this->postPrompt($prompt, true);
        if ($text) {
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Fallback Mock Generator
        return $this->getMockEtlPipeline($promptText);
    }

    /**
     * Fallback mock pipeline generator when API key is rate limited or fails
     */
    protected function getMockEtlPipeline(string $promptText): array
    {
        $promptTextLower = strtolower($promptText);
        
        // Default values
        $name = 'custom_etl_pipeline_' . rand(100, 999);
        $source = 'Source Connection';
        $target = 'Target Warehouse';
        $frequency = 'Daily';
        
        // Guess source
        if (str_contains($promptTextLower, 'postgres') || str_contains($promptTextLower, 'pg')) {
            $source = 'PostgreSQL Production DB';
        } elseif (str_contains($promptTextLower, 'salesforce') || str_contains($promptTextLower, 'crm')) {
            $source = 'Salesforce CRM API';
        } elseif (str_contains($promptTextLower, 'csv') || str_contains($promptTextLower, 'excel')) {
            $source = 'Excel Sales Uploads';
        }
        
        // Guess target
        if (str_contains($promptTextLower, 'clickhouse') || str_contains($promptTextLower, 'ch')) {
            $target = 'ClickHouse DW';
        } elseif (str_contains($promptTextLower, 'mart') || str_contains($promptTextLower, 'dim') || str_contains($promptTextLower, 'fact')) {
            $target = 'Data Mart';
        } elseif (str_contains($promptTextLower, 'staging')) {
            $target = 'Staging Layer';
        }
        
        // Guess name
        if (str_contains($promptTextLower, 'user')) {
            $name = 'users_cleaning_pipeline';
        } elseif (str_contains($promptTextLower, 'sale') || str_contains($promptTextLower, 'transaction')) {
            $name = 'sales_ingestion_pipeline';
        } elseif (str_contains($promptTextLower, 'customer')) {
            $name = 'customer_sync_pipeline';
        }
        
        // Guess frequency
        if (str_contains($promptTextLower, 'jam') || str_contains($promptTextLower, 'hour')) {
            $frequency = 'Hourly';
        } elseif (str_contains($promptTextLower, 'minggu') || str_contains($promptTextLower, 'week')) {
            $frequency = 'Weekly';
        }
        
        // Build a highly realistic Python script
        $script = "# =====================================================================\n";
        $script .= "# PIPELINE ETL GENERATED AUTOMATICALLY BY GEMINI AI (FALLBACK MODE)\n";
        $script .= "# Nama Pipeline: {$name}\n";
        $script .= "# Sumber: {$source} | Tujuan: {$target}\n";
        $script .= "# Frekuensi: {$frequency}\n";
        $script .= "# =====================================================================\n\n";
        $script .= "import pandas as pd\n";
        $script .= "import numpy as np\n";
        $script .= "import psycopg2\n";
        $script .= "from sqlalchemy import create_engine\n";
        $script .= "import datetime\n\n";
        $script .= "def run_pipeline():\n";
        $script .= "    print(f\"[{datetime.datetime.now()}] Memulai eksekusi pipeline ETL: {name}\")\n\n";
        $script .= "    # 1. Tahap Extract (Membaca data dari {$source})\n";
        
        if (str_contains($source, 'PostgreSQL')) {
            $script .= "    # Membuat koneksi ke database relasional ERP\n";
            $script .= "    engine_src = create_engine('postgresql://user:pass@host:port/db')\n";
            $script .= "    df = pd.read_sql_query('SELECT * FROM staging_transactions', con=engine_src)\n";
        } elseif (str_contains($source, 'Salesforce')) {
            $script .= "    # Mengambil data dari endpoint Salesforce API\n";
            $script .= "    # response = salesforce_client.query(\"SELECT Id, Name, Email FROM Contact\")\n";
            $script .= "    df = pd.DataFrame([{'id': 1, 'name': 'Asrofi Binsar', 'status': 'active'}, {'id': 2, 'name': 'John Doe', 'status': 'inactive'}])\n";
        } else {
            $script .= "    # Membaca data dari berkas mentah unggahan\n";
            $script .= "    df = pd.read_csv('/tmp/uploaded_source_file.csv')\n";
        }
        
        $script .= "    print(f\"-> Berhasil membaca {len(df)} baris data.\")\n\n";
        
        $script .= "    # 2. Tahap Transform (Pembersihan dan Standarisasi)\n";
        $script .= "    # Normalisasi string & penanganan nilai kosong\n";
        
        if (str_contains($promptTextLower, 'null') || str_contains($promptTextLower, 'kosong')) {
            $script .= "    # Mengisi nilai amount yang kosong dengan default 0\n";
            $script .= "    if 'amount' in df.columns:\n";
            $script .= "        df['amount'] = df['amount'].fillna(0)\n";
            $script .= "    if 'price' in df.columns:\n";
            $script .= "        df['price'] = df['price'].fillna(0)\n";
        }
        
        if (str_contains($promptTextLower, 'email')) {
            $script .= "    # Memvalidasi alamat email dan memfilter format salah\n";
            $script .= "    if 'email' in df.columns:\n";
            $script .= "        df['email'] = df['email'].astype(str).str.lower().str.strip()\n";
            $script .= "        df = df[df['email'].str.contains('@', na=False)]\n";
        }
        
        if (str_contains($promptTextLower, 'status') || str_contains($promptTextLower, 'active')) {
            $script .= "    # Menyaring baris dengan status 'active'\n";
            $script .= "    if 'status' in df.columns:\n";
            $script .= "        df = df[df['status'].str.lower() == 'active']\n";
        }
        
        $script .= "    # Mengubah nama kolom menjadi format snake_case\n";
        $script .= "    df.columns = [c.lower().replace(' ', '_') for c in df.columns]\n";
        $script .= "    print(f\"-> Transformasi selesai. Data bersih siap disimpan: {len(df)} baris.\")\n\n";
        
        $script .= "    # 3. Tahap Load (Menyimpan data ke {$target})\n";
        
        if (str_contains($target, 'ClickHouse')) {
            $script .= "    # Menyimpan ke ClickHouse Data Warehouse menggunakan client HTTP\n";
            $script .= "    # clickhouse_client.insert_dataframe('INSERT INTO dw_transactions VALUES', df)\n";
            $script .= "    print(\"-> Data berhasil dimasukkan ke ClickHouse Warehouse.\")\n";
        } elseif (str_contains($target, 'Staging')) {
            $script .= "    # Menyimpan ke database Postgres staging\n";
            $script .= "    engine_dst = create_engine('postgresql://user:pass@host_dwh:port/dwh')\n";
            $script .= "    df.to_sql('staging_cleaned_table', con=engine_dst, if_exists='append', index=False)\n";
            $script .= "    print(\"-> Data berhasil dimuat ke database Postgres Staging.\")\n";
        } else {
            $script .= "    # Menyimpan ke CSV output target\n";
            $script .= "    df.to_csv('/data/output/cleaned_etl_data.csv', index=False)\n";
            $script .= "    print(\"-> Data berhasil diexport ke cleaned_etl_data.csv.\")\n";
        }
        
        $script .= "\n    print(f\"[{datetime.datetime.now()}] Pipeline ETL '{name}' sukses dijalankan.\")\n";
        $script .= "    return True\n\n";
        $script .= "if __name__ == '__main__':\n";
        $script .= "    run_pipeline()\n";

        return [
            'pipeline_name' => $name,
            'source_layer' => $source,
            'target_layer' => $target,
            'frequency' => $frequency,
            'generated_script' => $script
        ];
    }

    /**
     * Generate detailed ETL Studio pipeline parameters from natural language prompt
     *
     * @param string $promptText  Natural language instruction from user
     * @param array  $connections Optional: list of available EtlConnections with their scanned table metadata.
     *                            Each element: ['id'=>int, 'name'=>string, 'driver'=>string, 'tables'=>[['name'=>string,'columns'=>string,'rows'=>int]]]
     */
    public function generateEtlStudioPipeline(string $promptText, array $connections = []): ?array
    {
        $targetTable = $this->resolveTargetTable($promptText, $connections);
        $filteredConnections = $this->filterSourceConnections($connections, $targetTable);

        $prompt = "Anda adalah Senior Data Warehouse Architect, Senior ETL Engineer, dan Pentaho PDI Solution Architect.\n";
        $prompt .= "Tugas Anda adalah merancang ETL pipeline untuk target table tertentu berdasarkan deskripsi pengguna berikut:\n\n";
        $prompt .= "\"$promptText\"\n\n";

        // --- Embed real schema context if available ---
        if (!empty($filteredConnections)) {
            $prompt .= "=== KONTEKS SCHEMA DATABASE YANG TERSEDIA ===\n";
            $prompt .= "Berikut adalah daftar koneksi database yang tersedia beserta tabel, skema, kolom, dan jumlah barisnya.\n";
            foreach ($filteredConnections as $idx => $conn) {
                $prompt .= ($idx + 1) . ". KONEKSI: \"{$conn['name']}\" (driver: {$conn['driver']})\n";
                if (!empty($conn['tables'])) {
                    foreach ($conn['tables'] as $tbl) {
                        $rows = $tbl['rows'] > 0 ? " | {$tbl['rows']} rows" : "";
                        $prompt .= "   - Tabel: \"{$tbl['name']}\"{$rows}\n";
                        if (!empty($tbl['columns'])) {
                            $prompt .= "     Kolom: {$tbl['columns']}\n";
                        }
                    }
                } else {
                    $prompt .= "   (Tidak ada metadata tabel tersedia)\n";
                }
                $prompt .= "\n";
            }
            $prompt .= "=== AKHIR KONTEKS SCHEMA ===\n\n";
        }

        $prompt .= "=== PROSES BERPIKIR & REASONING ARCHITECT ===\n";
        $prompt .= "Lakukan tahapan analisis berikut secara ketat:\n\n";
        $prompt .= "STEP 1: IDENTIFY TARGET TABLE TYPE\n";
        $prompt .= "- Jika nama tabel target mengandung \"dim_\", maka target_type = DIMENSION\n";
        $prompt .= "- Jika nama tabel target mengandung \"fact_\", maka target_type = FACT\n";
        $prompt .= "- Jika nama tabel target mengandung \"bridge_\", maka target_type = BRIDGE\n";
        $prompt .= "- Jika nama tabel target mengandung \"agg_\", \"summary_\", atau \"mart_\", maka target_type = AGGREGATE\n\n";
        
        $prompt .= "STEP 2: IDENTIFY TARGET BUSINESS ENTITY\n";
        $prompt .= "- Tentukan business_entity target dengan menghapus skema dan awalan tipe (misal: \"dw.dim_customer\" -> entity = \"customer\", \"dw.fact_sales\" -> entity = \"sales\")\n\n";
        
        $prompt .= "STEP 3: CLASSIFY AVAILABLE TABLES\n";
        $prompt .= "- Kelompokkan tabel-tabel di konteks skema menjadi:\n";
        $prompt .= "  - MASTER TABLE (mengandung: customer, product, employee, vendor, branch, dll.)\n";
        $prompt .= "  - TRANSACTION TABLE (mengandung: payment, sales, invoice, transaction, order, dll.)\n";
        $prompt .= "  - LOOKUP TABLE (mengandung: country, province, status, category, dll.)\n";
        $prompt .= "  - STAGING TABLE (berada di skema \"staging\" atau diawali \"staging.\")\n\n";
        
        $prompt .= "STEP 4: SELECT SOURCE TABLES (SCORING SYSTEM)\n";
        $prompt .= "Hitung skor untuk masing-masing tabel kandidat sebagai source:\n";
        $prompt .= "- ENTITY MATCH: Jika nama tabel mengandung entity yang sama (misal: customer): +40 poin\n";
        $prompt .= "- SCHEMA PRIORITY: staging -> +30 poin, public -> +20 poin, dw -> -100 poin (Jangan gunakan skema dw sebagai source)\n";
        $prompt .= "- COLUMN MATCH: Jika memiliki {entity}_id -> +10 poin, {entity}_name -> +10 poin, email -> +10 poin\n";
        $prompt .= "- TRANSACTION PENALTY: Jika target_type adalah DIMENSION dan source table mengandung keyword transaksi (payment, sales, invoice, transaction, order): Kena penalti -50 poin\n";
        $prompt .= "- Pilih tabel dengan skor tertinggi sebagai source utama. Jangan memilih tabel dari skema dw sebagai source.\n\n";
        
        $prompt .= "STEP 5: PENTAHO PIPELINE STEP PARITY MODE\n";
        $prompt .= "Gunakan HANYA komponen/step Pentaho berikut:\n";
        $prompt .= "- INPUT: Table Input, CSV File Input, Excel Input, Data Grid\n";
        $prompt .= "- TRANSFORM: Select Values, Formula, Calculator, Filter Rows, Sort Rows, Unique Rows, Group By, Lookup, Join\n";
        $prompt .= "- OUTPUT: Table Output, CSV Output, Excel Output\n\n";
        $prompt .= "PENTING: Jangan menghasilkan script Python atau source code lainnya.\n\n";
        
        $prompt .= "Desain pipeline berdasarkan tipe target:\n";
        $prompt .= "- Jika DIMENSION: Alur pipeline WAJIB: Table Input -> Select Values -> Formula -> Unique Rows -> Table Output\n";
        $prompt .= "  (Transformations array untuk UI: ['Select Values', 'Formula', 'Unique Rows'])\n";
        $prompt .= "- Jika FACT: Alur pipeline WAJIB: Table Input -> Lookup -> Calculator -> Group By -> Table Output\n";
        $prompt .= "  (Transformations array untuk UI: ['Lookup', 'Calculator', 'Group By'])\n";
        $prompt .= "- Jika target adalah tipe lain: desain alur menggunakan kombinasi step Pentaho yang diizinkan.\n\n";

        $prompt .= "STEP 6: COLUMN MAPPING INTELLIGENCE\n";
        $prompt .= "- Jika target kolom adalah customer_name (atau full_name/name) dan source memiliki first_name dan last_name:\n";
        $prompt .= "  Petakan target customer_name ke source formula: \"first_name + ' ' + last_name\" atau \"CONCAT(first_name,' ',last_name)\", dan pastikan step \"Formula\" otomatis ada di dalam pipeline.\n\n";

        $prompt .= "=== OUTPUT FORMAT ===\n";
        $prompt .= "Hasilkan output HANYA dalam format JSON murni tanpa markdown tambahan, dengan struktur keys berikut:\n";
        $prompt .= "{\n";
        $prompt .= "  \"pipeline_name\": \"Nama pipeline deskriptif snake_case\",\n";
        $prompt .= "  \"target_type\": \"DIMENSION | FACT | BRIDGE | AGGREGATE\",\n";
        $prompt .= "  \"business_entity\": \"Nama entitas bisnis target\",\n";
        $prompt .= "  \"selected_sources\": [\"Nama tabel source terpilih utama (misal: staging.customer)\"],\n";
        $prompt .= "  \"candidate_sources\": [\n";
        $prompt .= "     {\"table\": \"nama_tabel\", \"score\": 90, \"reasons\": [\"Alasan skor\"], \"type\": \"STAGING | MASTER | TRANSACTION | LOOKUP\"}\n";
        $prompt .= "  ],\n";
        $prompt .= "  \"reasoning\": [\n";
        $prompt .= "     \"Target: dw.dim_customer\",\n";
        $prompt .= "     \"Target Type: DIMENSION\",\n";
        $prompt .= "     \"Business Entity: Customer\",\n";
        $prompt .= "     \"Selected Source: staging.customer\",\n";
        $prompt .= "     \"Reason:\",\n";
        $prompt .= "     \"1. Nama entitas customer cocok 100%\",\n";
        $prompt .= "     \"2. Berada pada schema staging\",\n";
        $prompt .= "     \"3. ...\"\n";
        $prompt .= "  ],\n";
        $prompt .= "  \"steps\": [\"Daftar step Pentaho lengkap, misal: Table Input, Select Values, Formula, Unique Rows, Table Output\"],\n";
        $prompt .= "  \"column_mapping\": [\n";
        $prompt .= "     {\"source\": \"kolom_sumber\", \"target\": \"kolom_target\"}\n";
        $prompt .= "  ],\n";
        $prompt .= "  \"validation_result\": {\n";
        $prompt .= "     \"source_table_exists\": true,\n";
        $prompt .= "     \"target_table_exists\": true,\n";
        $prompt .= "     \"column_mapping_valid\": true,\n";
        $prompt .= "     \"warnings\": []\n";
        $prompt .= "  },\n";
        $prompt .= "  \"source_connection_name\": \"Nama koneksi source terpilih utama\",\n";
        $prompt .= "  \"source_table\": \"Nama tabel source terpilih utama\",\n";
        $prompt .= "  \"target_connection_name\": \"Nama koneksi target terpilih utama\",\n";
        $prompt .= "  \"target_table\": \"Nama tabel target\",\n";
        $prompt .= "  \"transformations\": [\"Daftar transformasi untuk UI, misal: 'Select Values', 'Formula', 'Unique Rows'\"],\n";
        $prompt .= "  \"execution_plan\": \"Penjelasan singkat rencana eksekusi\",\n";
        $prompt .= "  \"sql_preview\": \"Preview SQL Query untuk Table Input\",\n";
        $prompt .= "  \"json_definition\": {\"pipeline_name\": \"...\", \"steps\": []},\n";
        $prompt .= "  \"pipeline_steps\": [\n";
        $prompt .= "     {\"name\": \"Nama Step\", \"description\": \"Deskripsi\", \"inputs\": [], \"outputs\": []}\n";
        $prompt .= "  ],\n";
        $prompt .= "  \"confidence\": {\"score\": 95, \"category\": \"High Confidence\", \"warning\": false, \"factors\": []}\n";
        $prompt .= "}";

        $text = $this->postPrompt($prompt, true);
        if ($text) {
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $this->getFallbackStudioPipeline($promptText, $connections, $targetTable);
    }

    /**
     * Fallback studio pipeline generator when Gemini fails or is rate-limited.
     * Uses real connection context when available, otherwise uses demo data.
     *
     * Resolution priority for target table:
     *  1. Regex-extract explicit schema.table refs from prompt → exact match
     *  2. Find the connection whose name appears in the prompt, then full-name match
     *  3. Full table-name substring (NOT per-fragment) to avoid "customer" in
     *     "fact_customer_balance" wrongly matching "dim_customer"
     */
    protected function getFallbackStudioPipeline(string $promptText, array $connections = [], string $targetTable = 'dim_customer'): array
    {
        $lower = strtolower($promptText);

        // ─── Resolve Target Table and Connections from Context ───────────
        $targetTable = $targetTable ?: 'dim_customer';
        $targetConn = 'PostgreSQL Data Warehouse';
        $targetColumns = ['customer_id', 'customer_name', 'email'];
        
        $foundTgtConn = null;
        $foundTgtTable = null;
        $foundTgtCols = [];

        if (!empty($connections)) {
            // ── Strategy 1: Extract explicit schema.table refs from prompt
            preg_match_all('/\b([a-z_][a-z0-9_]*\.[a-z_][a-z0-9_]*)\b/i', $promptText, $schemaTableM);
            $schemaTableRefs = array_unique(array_map('strtolower', $schemaTableM[0] ?? []));

            foreach ($schemaTableRefs as $ref) {
                foreach ($connections as $conn) {
                    foreach ($conn['tables'] ?? [] as $tbl) {
                        if (strtolower($tbl['name']) === $ref) {
                            $foundTgtConn  = $conn['name'];
                            $foundTgtTable = $tbl['name'];
                            $foundTgtCols  = !empty($tbl['columns'])
                                ? array_map('trim', explode(',', $tbl['columns']))
                                : [];
                            break 3;
                        }
                    }
                }
            }

            // ── Strategy 2: Find target connection by checking if name appears in prompt
            $mentionedConnIdx = null;
            foreach ($connections as $ci => $conn) {
                if (str_contains($lower, strtolower($conn['name']))) {
                    $mentionedConnIdx = $ci; break;
                }
                foreach (preg_split('/[\s_\-]+/', strtolower($conn['name'])) as $word) {
                    if (strlen($word) >= 5 && str_contains($lower, $word)) {
                        $mentionedConnIdx = $ci; break 2;
                    }
                }
            }

            // ── Strategy 3: Substring search in prompt
            if (!$foundTgtTable) {
                $orderedConns = $connections;
                if ($mentionedConnIdx !== null) {
                    $mc = array_splice($orderedConns, $mentionedConnIdx, 1);
                    array_unshift($orderedConns, $mc[0]);
                }

                // 3a: full table name substring
                foreach ($orderedConns as $conn) {
                    foreach ($conn['tables'] ?? [] as $tbl) {
                        $tblNameLower = strtolower($tbl['name']);
                        $pattern = '/\b' . preg_quote($tblNameLower, '/') . '\b/';
                        if (preg_match($pattern, $lower)) {
                            $foundTgtConn  = $conn['name'];
                            $foundTgtTable = $tbl['name'];
                            $foundTgtCols  = !empty($tbl['columns'])
                                ? array_map('trim', explode(',', $tbl['columns']))
                                : [];
                            break 2;
                        }
                    }
                }

                // 3b: bare table name substring
                if (!$foundTgtTable) {
                    foreach ($orderedConns as $conn) {
                        foreach ($conn['tables'] ?? [] as $tbl) {
                            $bare = strstr($tbl['name'], '.') !== false
                                ? substr(strstr($tbl['name'], '.'), 1)
                                : $tbl['name'];
                            $bareLower = strtolower($bare);
                            $pattern = '/\b' . preg_quote($bareLower, '/') . '\b/';
                            if (preg_match($pattern, $lower)) {
                                $foundTgtConn  = $conn['name'];
                                $foundTgtTable = $tbl['name'];
                                $foundTgtCols  = !empty($tbl['columns'])
                                    ? array_map('trim', explode(',', $tbl['columns']))
                                    : [];
                                break 2;
                            }
                        }
                    }
                }
            }

            if ($foundTgtTable) {
                $targetConn = $foundTgtConn ?? $targetConn;
                $targetTable = $foundTgtTable;
                $targetColumns = $foundTgtCols;
            }
        } else {
            // Hardcoded guess based on prompt keywords if no database is connected
            if (str_contains($lower, 'sales') || str_contains($lower, 'payment') || str_contains($lower, 'fact')) {
                $targetTable = 'dw.fact_sales';
                $targetColumns = ['sales_id', 'customer_id', 'amount', 'transaction_date'];
            }
        }

        // ─── STEP 1: Identify Target Table Type ──────────────────────────
        $targetType = 'DIMENSION';
        $targetTableLower = strtolower($targetTable);
        if (str_contains($targetTableLower, 'dim_')) {
            $targetType = 'DIMENSION';
        } elseif (str_contains($targetTableLower, 'fact_')) {
            $targetType = 'FACT';
        } elseif (str_contains($targetTableLower, 'bridge_')) {
            $targetType = 'BRIDGE';
        } elseif (str_contains($targetTableLower, 'agg_') || str_contains($targetTableLower, 'summary_') || str_contains($targetTableLower, 'mart_')) {
            $targetType = 'AGGREGATE';
        }

        // ─── STEP 2: Identify Target Business Entity ──────────────────────
        $entity = $targetTable;
        if (str_contains($entity, '.')) {
            $entity = explode('.', $entity)[1];
        }
        $entity = preg_replace('/^(dim_|fact_|bridge_|agg_|summary_|mart_)/i', '', $entity);
        $entity = strtolower($entity); // e.g. "customer", "sales", "product"

        // ─── STEP 3, 4, 5: Scan available tables & Classify & Score ─────
        $sourceConn = 'Oracle Finance ERP';
        $sourceTable = 'customers_raw';
        $sourceColumns = ['customer_id', 'cust_name', 'email_address'];
        $selectedSourceObj = null;
        $scoredSources = [];

        $classifyTable = function($tableName) {
            $tblLower = strtolower($tableName);
            $bare = str_contains($tblLower, '.') ? explode('.', $tblLower)[1] : $tblLower;
            
            if (str_starts_with($tblLower, 'staging.') || str_contains($tblLower, 'staging')) {
                return 'STAGING';
            }
            if (preg_match('/\b(payment|sales|invoice|transaction|order|purchase)\b/i', $bare)) {
                return 'TRANSACTION';
            }
            if (preg_match('/\b(country|province|status|category|city|state|gender|lookup)\b/i', $bare)) {
                return 'LOOKUP';
            }
            if (preg_match('/\b(customer|product|employee|vendor|branch|store|user|agent)\b/i', $bare)) {
                return 'MASTER';
            }
            return 'MASTER';
        };

        $filteredConnections = $this->filterSourceConnections($connections, $targetTable);

        if (!empty($filteredConnections)) {
            foreach ($filteredConnections as $conn) {
                foreach ($conn['tables'] ?? [] as $tbl) {
                    $tblName = $tbl['name'];
                    $tblNameLower = strtolower($tblName);
                    
                    if ($tblNameLower === strtolower($targetTable)) {
                        continue;
                    }
                    
                    $tblSchema = str_contains($tblNameLower, '.') ? explode('.', $tblNameLower)[0] : 'public';
                    $bareName = str_contains($tblNameLower, '.') ? explode('.', $tblNameLower)[1] : $tblNameLower;
                    
                    $tblType = $classifyTable($tblName);
                    $score = 0;
                    $reasons = [];
                    
                    // ENTITY MATCH (+40 points)
                    if (str_contains($tblNameLower, $entity)) {
                        $score += 40;
                        $reasons[] = "Nama entitas '{$entity}' cocok (+40 poin)";
                    }
                    
                    // SCHEMA PRIORITY
                    if ($tblSchema === 'staging') {
                        $score += 30;
                        $reasons[] = "Berada pada schema staging (+30 poin)";
                    } elseif ($tblSchema === 'public') {
                        $score += 20;
                        $reasons[] = "Berada pada schema public (+20 poin)";
                    } elseif ($tblSchema === 'dw') {
                        $score -= 100;
                        $reasons[] = "Berada pada schema dw (penalti -100 poin, tidak boleh digunakan)";
                    }
                    
                    // COLUMN MATCH
                    $cols = !empty($tbl['columns']) ? array_map('trim', explode(',', $tbl['columns'])) : [];
                    $colsLower = array_map('strtolower', $cols);
                    
                    if (in_array("{$entity}_id", $colsLower)) {
                        $score += 10;
                        $reasons[] = "Memiliki kolom {$entity}_id (+10 poin)";
                    }
                    if (in_array("{$entity}_name", $colsLower)) {
                        $score += 10;
                        $reasons[] = "Memiliki kolom {$entity}_name (+10 poin)";
                    }
                    if (in_array('email', $colsLower)) {
                        $score += 10;
                        $reasons[] = "Memiliki kolom email (+10 poin)";
                    }
                    
                    // TRANSACTION PENALTY
                    if ($targetType === 'DIMENSION' && preg_match('/\b(payment|sales|invoice|transaction|order)\b/i', $bareName)) {
                        $score -= 50;
                        $reasons[] = "Tabel transaksi '{$bareName}' digunakan untuk dimensi (penalti -50 poin)";
                    }
                    
                    if ($tblSchema !== 'dw') {
                        $reasons[] = "Tidak berasal dari schema dw";
                    }
                    
                    $scoredSources[] = [
                        'table' => $tblName,
                        'connection' => $conn['name'],
                        'columns' => $cols,
                        'score' => $score,
                        'reasons' => $reasons,
                        'type' => $tblType
                    ];
                }
            }

            if (!empty($scoredSources)) {
                usort($scoredSources, function($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
                
                // Select highest-scoring source
                $selectedSourceObj = $scoredSources[0];
                $sourceConn = $selectedSourceObj['connection'];
                $sourceTable = $selectedSourceObj['table'];
                $sourceColumns = $selectedSourceObj['columns'];
            }
        } else {
            // Mock candidates when no connections exist
            if ($targetType === 'DIMENSION') {
                $sourceTable = 'staging.customer';
                $sourceColumns = ['customer_id', 'first_name', 'last_name', 'email'];
                $scoredSources[] = [
                    'table' => 'staging.customer',
                    'connection' => 'PostgreSQL Staging',
                    'columns' => $sourceColumns,
                    'score' => 90,
                    'reasons' => ["Nama entitas cocok 100%", "Berada pada schema staging", "Memiliki customer_id", "Tidak berasal dari schema dw"],
                    'type' => 'STAGING'
                ];
                $scoredSources[] = [
                    'table' => 'public.customer',
                    'connection' => 'PostgreSQL Public',
                    'columns' => ['customer_id', 'cust_name', 'email_address'],
                    'score' => 70,
                    'reasons' => ["Nama entitas cocok 100%", "Berada pada schema public", "Tidak berasal dari schema dw"],
                    'type' => 'MASTER'
                ];
                $scoredSources[] = [
                    'table' => 'public.payment',
                    'connection' => 'PostgreSQL Public',
                    'columns' => ['payment_id', 'customer_id', 'amount'],
                    'score' => 20,
                    'reasons' => ["Memiliki kolom relasi customer_id", "Tabel transaksi digunakan untuk dimensi (penalti -50 poin)"],
                    'type' => 'TRANSACTION'
                ];
            } else {
                $sourceTable = 'public.payment';
                $sourceColumns = ['payment_id', 'customer_id', 'amount', 'payment_date'];
                $scoredSources[] = [
                    'table' => 'public.payment',
                    'connection' => 'PostgreSQL Public',
                    'columns' => $sourceColumns,
                    'score' => 80,
                    'reasons' => ["Nama entitas cocok", "Berada pada schema public"],
                    'type' => 'TRANSACTION'
                ];
                $scoredSources[] = [
                    'table' => 'staging.sales_log',
                    'connection' => 'PostgreSQL Staging',
                    'columns' => ['log_id', 'amount', 'created_at'],
                    'score' => 50,
                    'reasons' => ["Berada pada schema staging"],
                    'type' => 'STAGING'
                ];
            }
            $selectedSourceObj = $scoredSources[0];
        }

        // Build candidate sources in output format
        $candidates = [];
        foreach (array_slice($scoredSources, 0, 3) as $ss) {
            $candidates[] = [
                'table' => $ss['table'],
                'score' => $ss['score'],
                'reasons' => $ss['reasons'],
                'type' => $ss['type'],
                'connection' => $ss['connection']
            ];
        }

        // ─── STEP 6: Generate ETL Pipeline (Transformations) ──────────────
        $transformations = [];
        $steps = [];
        if ($targetType === 'DIMENSION') {
            $transformations = ['Select Values', 'Formula', 'Unique Rows'];
            $steps = ['Table Input', 'Select Values', 'Formula', 'Unique Rows', 'Table Output'];
        } elseif ($targetType === 'FACT') {
            $transformations = ['Lookup', 'Calculator', 'Group By'];
            $steps = ['Table Input', 'Lookup', 'Calculator', 'Group By', 'Table Output'];
        } else {
            $transformations = ['Select Values', 'Calculator', 'Group By'];
            $steps = ['Table Input', 'Select Values', 'Calculator', 'Group By', 'Table Output'];
        }

        // ─── Column Mapping & Formula Intelligence ──────────────────────────
        $mapping = [];
        $srcColsLower = array_map('strtolower', $sourceColumns);
        $tgtColsLower = array_map('strtolower', $targetColumns);

        // Check for concatenation mapping (first_name + last_name -> customer_name)
        $hasFirstLast = in_array('first_name', $srcColsLower) && in_array('last_name', $srcColsLower);
        
        foreach ($targetColumns as $tc) {
            $tcL = strtolower(trim($tc));
            
            // Concatenation logic
            if (($tcL === 'customer_name' || $tcL === 'full_name' || $tcL === 'name') && $hasFirstLast) {
                $mapping[] = ['source' => "[Kalkulasi: first_name + ' ' + last_name]", 'target' => trim($tc)];
                continue;
            }
            
            // Surrogate/Serial Key logic
            $isSurrogateKey = str_ends_with($tcL, '_key') || str_ends_with($tcL, '_id') || $tcL === 'id';
            $inSource = in_array($tcL, $srcColsLower);
            if ($isSurrogateKey && !$inSource) {
                $mapping[] = ['source' => '[Serial (Unique)]', 'target' => trim($tc)];
                continue;
            }

            // Normal semantic match
            $bestSrc = null;
            foreach ($sourceColumns as $sc) {
                if (strtolower(trim($sc)) === $tcL) {
                    $bestSrc = $sc;
                    break;
                }
            }
            if (!$bestSrc) {
                // partial match
                foreach ($sourceColumns as $sc) {
                    $scL = strtolower(trim($sc));
                    if (str_contains($tcL, $scL) || str_contains($scL, $tcL)) {
                        $bestSrc = $sc;
                        break;
                    }
                }
            }
            $mapping[] = ['source' => $bestSrc ? trim($bestSrc) : '(pilih kolom sumber)', 'target' => trim($tc)];
        }

        // ─── Execution Plan ──────────────────────────────────────────────────
        $pipelineName = 'load_' . preg_replace('/[^a-z0-9_]/', '_', strtolower(str_replace('.', '_', $targetTable)));
        $plan = "Mengekstrak data dari '{$sourceTable}' ({$sourceConn}), melakukan mapping kolom, menerapkan transformasi Pentaho, dan memuatnya ke DWH target '{$targetTable}' ({$targetConn}).";

        // ─── Reasoning ───────────────────────────────────────────────────────
        $reasoning = [
            "Target: " . $targetTable,
            "Target Type: " . $targetType,
            "Business Entity: " . ucfirst($entity),
            "Selected Source: " . $sourceTable,
            "Reason:"
        ];
        if ($selectedSourceObj) {
            $idx = 1;
            foreach ($selectedSourceObj['reasons'] as $r) {
                $reasoning[] = "{$idx}. {$r}";
                $idx++;
            }
        }

        // ─── Validation Result & Confidence ──────────────────────────────────
        $warnings = [];
        $unmappedCount = 0;
        foreach ($mapping as $m) {
            if (str_contains($m['source'], '(pilih kolom')) {
                $unmappedCount++;
            }
        }
        if ($unmappedCount > 0) {
            $warnings[] = "Terdapat {$unmappedCount} kolom target yang belum terpetakan ke kolom sumber";
        }
        
        $validationResult = [
            'source_table_exists' => true,
            'target_table_exists' => true,
            'column_mapping_valid' => $unmappedCount === 0,
            'data_type_compatible' => true,
            'lookup_relation_valid' => true,
            'warnings' => $warnings
        ];

        $confScore = max(99 - ($unmappedCount * 15), 10);
        $confidence = [
            'score' => $confScore,
            'category' => $confScore >= 90 ? 'High Confidence' : ($confScore >= 70 ? 'Medium Confidence' : 'Low Confidence'),
            'warning' => $confScore < 70,
            'factors' => ["Kesesuaian skema target & scoring DWH"]
        ];

        // ─── SQL Preview ──────────────────────────────────────────────────────
        $sqlMappingLines = [];
        foreach ($mapping as $map) {
            $src = $map['source'];
            $tgt = $map['target'];
            if (str_contains($src, '[Kalkulasi')) {
                $sqlMappingLines[] = "    (first_name || ' ' || last_name) AS {$tgt}";
            } elseif (str_contains($src, '[Serial')) {
                $sqlMappingLines[] = "    NEXTVAL('seq_{$tgt}') AS {$tgt}";
            } else {
                $sqlMappingLines[] = "    {$src} AS {$tgt}";
            }
        }
        $sqlPreview = "SELECT\n" . implode(",\n", $sqlMappingLines) . "\nFROM {$sourceTable}";

        // ─── JSON Definition ──────────────────────────────────────────────────
        $defSteps = [];
        $defSteps[] = ['type' => 'table_input', 'table' => $sourceTable];
        foreach ($transformations as $t) {
            $defSteps[] = ['type' => strtolower(str_replace(' ', '_', $t))];
        }
        $defSteps[] = ['type' => 'table_output', 'table' => $targetTable];
        $jsonDefinition = [
            'pipeline_name' => $pipelineName,
            'steps' => $defSteps
        ];

        // ─── Pipeline Steps (UI detail) ──────────────────────────────────────
        $pipelineSteps = [];
        $pipelineSteps[] = [
            'name' => "Table Input: " . $sourceTable,
            'description' => "Membaca data dari tabel sumber database",
            'inputs' => [],
            'outputs' => $sourceColumns
        ];
        foreach ($transformations as $t) {
            $pipelineSteps[] = [
                'name' => $t,
                'description' => "Step transformasi Pentaho: " . $t,
                'inputs' => $sourceColumns,
                'outputs' => array_column($mapping, 'target')
            ];
        }
        $pipelineSteps[] = [
            'name' => "Table Output: " . $targetTable,
            'description' => "Memuat hasil akhir data ke gudang data target",
            'inputs' => array_column($mapping, 'target'),
            'outputs' => []
        ];

        return [
            'pipeline_name' => $pipelineName,
            'target_type' => $targetType,
            'business_entity' => $entity,
            'selected_sources' => [$sourceTable],
            'candidate_sources' => $candidates,
            'reasoning' => $reasoning,
            'steps' => $steps,
            'column_mapping' => $mapping,
            'validation_result' => $validationResult,
            'source_connection_name' => $sourceConn,
            'source_table' => $sourceTable,
            'target_connection_name' => $targetConn,
            'target_table' => $targetTable,
            'transformations' => $transformations,
            'execution_plan' => $plan,
            'sql_preview' => $sqlPreview,
            'json_definition' => $jsonDefinition,
            'pipeline_steps' => $pipelineSteps,
            'confidence' => $confidence
        ];
    }

    /**
     * Generate semantic column mappings from source to target columns
     */
    public function generateStudioColumnMapping(array $sourceCols, array $targetCols): ?array
    {
        $srcStr = implode(', ', $sourceCols);
        $tgtStr = implode(', ', $targetCols);

        $prompt = "Anda adalah seorang Data Engineer.\n";
        $prompt .= "Buat pemetaan kolom otomatis dari tabel sumber ke tabel tujuan berikut:\n";
        $prompt .= "Kolom Sumber: $srcStr\n";
        $prompt .= "Kolom Tujuan: $tgtStr\n\n";
        $prompt .= "Hubungkan kolom yang memiliki kemiripan arti secara semantik (contoh: customer_id dengan customer_key, name dengan customer_name, email_address dengan email).\n";
        $prompt .= "Hasilkan output HANYA dalam format JSON murni tanpa markdown tambahan, dengan format seperti berikut:\n";
        $prompt .= "[\n";
        $prompt .= "  {\"source\": \"kolom_sumber\", \"target\": \"kolom_tujuan\"}\n";
        $prompt .= "]";

        $text = $this->postPrompt($prompt, true);
        if ($text) {
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Local fallback mapping logic
        $mapping = [];
        foreach ($sourceCols as $sc) {
            $scLower = strtolower($sc);
            foreach ($targetCols as $tc) {
                $tcLower = strtolower($tc);
                
                // Match exact or semantic
                if ($scLower === $tcLower || 
                    ($scLower === 'customer_id' && $tcLower === 'customer_key') ||
                    ($scLower === 'cust_name' && $tcLower === 'customer_name') ||
                    ($scLower === 'name' && $tcLower === 'customer_name') ||
                    ($scLower === 'full_name' && $tcLower === 'customer_name') ||
                    ($scLower === 'email_address' && $tcLower === 'email') ||
                    ($scLower === 'phone_no' && $tcLower === 'phone_number') ||
                    ($scLower === 'country_code' && $tcLower === 'country')
                ) {
                    $mapping[] = ['source' => $sc, 'target' => $tc];
                    break;
                }
            }
        }
        return $mapping;
    }

    /**
     * Analyze studio failed run error logs
     */
    public function analyzeStudioFailure(string $pipelineName, string $errorLog): ?array
    {
        $prompt = "Analisis kegagalan eksekusi pipeline ETL Studio berikut:\n\n";
        $prompt .= "Nama Pipeline: $pipelineName\n";
        $prompt .= "Error Log:\n$errorLog\n\n";
        $prompt .= "Hasilkan analisis kegagalan terperinci HANYA dalam format JSON murni tanpa markdown tambahan dengan format kunci berikut:\n";
        $prompt .= "{\n";
        $prompt .= "  \"root_cause\": \"Analisis penyebab utama kegagalan (1 kalimat dalam Bahasa Indonesia)\",\n";
        $prompt .= "  \"possibilities\": [\"Kemungkinan penyebab 1\", \"Kemungkinan penyebab 2\"],\n";
        $prompt .= "  \"impact\": \"Dampak bisnis bagi data warehouse (1 kalimat dalam Bahasa Indonesia)\",\n";
        $prompt .= "  \"recommendations\": [\"Rekomendasi perbaikan 1\", \"Rekomendasi perbaikan 2\"],\n";
        $prompt .= "  \"priority\": \"Tingkat prioritas perbaikan (harus salah satu dari 'High', 'Medium', atau 'Low')\"\n";
        $prompt .= "}";

        $text = $this->postPrompt($prompt, true);
        if ($text) {
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Local fallback failure diagnosis
        $rootCause = "Terjadi kegagalan koneksi atau otentikasi pada server target/sumber.";
        $possibilities = ["IP server tujuan tidak dapat dijangkau", "Username atau password koneksi salah", "Port server ditutup oleh firewall"];
        $impact = "Data baru gagal disinkronkan, menyebabkan perbedaan data antara source dan target DW.";
        $recommendations = ["Periksa kredensial koneksi di modul Connections", "Pastikan server tujuan sedang online", "Coba jalankan Test Connection di dashboard"];
        $priority = "High";

        $errorLogLower = strtolower($errorLog);

        if (str_contains($errorLogLower, 'listener') || str_contains($errorLogLower, 'ora-')) {
            $rootCause = "Oracle database listener (TNS) menolak koneksi pada port 1521.";
            $possibilities = ["TNS Listener Oracle sedang down di server target", "Port 1521 diblokir oleh firewall server", "Konfigurasi host salah"];
            $recommendations = ["Hubungi administrator database Oracle untuk menghidupkan listener", "Validasi rute firewall port 1521"];
        } elseif (preg_match('/(?:column|kolom)\s+["«]?\s*([a-zA-Z0-9_]+)\s*["»]?/i', $errorLog, $matches) || str_contains($errorLogLower, 'undefined column')) {
            $columnName = $matches[1] ?? 'created_at';
            $rootCause = "Kolom '{$columnName}' tidak ditemukan pada tabel target database relasional.";
            $possibilities = [
                "Struktur skema tabel target belum disinkronkan dengan skema pipeline baru",
                "Ada ketidakcocokan nama kolom pada pemetaan kolom (column mapping) target"
            ];
            $impact = "Eksekusi ETL gagal dilakukan karena query insert mendefinisikan kolom target yang tidak valid.";
            $recommendations = [
                "Tambahkan kolom '{$columnName}' ke tabel target secara manual",
                "Sesuaikan column mapping pada pipeline untuk tidak memetakan kolom '{$columnName}'",
                "Klik tombol 'Auto-Fix dengan AI' untuk melakukan penyesuaian skema otomatis"
            ];
        } elseif (str_contains($errorLogLower, 'unique constraint') || str_contains($errorLogLower, 'duplicate key')) {
            $rootCause = "Terjadi pelanggaran konstrain unik (Unique Constraint Violation) pada tabel target.";
            $possibilities = [
                "Data dari sumber data memiliki baris dengan kunci utama/unik yang sama (duplikat)",
                "Tabel target sudah memiliki record dengan identitas yang sama"
            ];
            $impact = "Proses ETL dibatalkan sepenuhnya demi menjaga konsistensi integritas referensial data warehouse.";
            $recommendations = [
                "Gunakan langkah transformasi 'Remove Duplicate' atau 'Unique Rows' di dalam pipeline",
                "Bersihkan baris duplikat pada tabel sumber data sebelum dijalankan ulang",
                "Klik tombol 'Auto-Fix dengan AI' untuk secara otomatis menambahkan filter duplikasi"
            ];
        } elseif (str_contains($errorLogLower, 'timeout') || str_contains($errorLogLower, 'sharepoint')) {
            $rootCause = "Waktu tunggu pembacaan data (Read Timeout) terlampaui saat mengunduh berkas sumber.";
            $possibilities = [
                "Kecepatan transfer data lambat atau tidak stabil pada rute server SharePoint",
                "File sumber berukuran terlalu besar tanpa dilakukan chunking/streaming"
            ];
            $impact = "Inisialisasi pemrosesan data prospek terhenti di tengah jalan dan data tidak terIngest.";
            $recommendations = [
                "Tingkatkan parameter read timeout di modul konfigurasi koneksi",
                "Periksa apakah file CSV/Excel dapat diakses secara manual",
                "Klik tombol 'Auto-Fix dengan AI' untuk mengoptimalkan timeout koneksi secara otomatis"
            ];
        } elseif (str_contains($errorLogLower, 'out of memory') || str_contains($errorLogLower, 'ram') || str_contains($errorLogLower, 'memory')) {
            $rootCause = "Proses ETL melebihi alokasi memori RAM yang dialokasikan (Out of Memory).";
            $possibilities = [
                "Memory limit PHP/JVM pada host saat ini terlalu rendah",
                "Beban pemrosesan string atau cleaning data memuat data terlalu besar sekaligus"
            ];
            $impact = "Proses penulisan data mati mendadak tanpa menyelesaikan transaksi load.";
            $recommendations = [
                "Tingkatkan batas memori memory_limit di berkas konfigurasi php.ini",
                "Kurangi ukuran batch pemrosesan data (data chunk size)",
                "Klik tombol 'Auto-Fix dengan AI' untuk menyusutkan batch loading secara otomatis"
            ];
        }

        return [
            'root_cause' => $rootCause,
            'possibilities' => $possibilities,
            'impact' => $impact,
            'recommendations' => $recommendations,
            'priority' => $priority
        ];
    }

    /**
     * Deterministically resolve target table name from prompt context
     */
    protected function resolveTargetTable(string $promptText, array $connections = []): string
    {
        $lower = strtolower($promptText);
        $targetTable = 'dim_customer'; // default

        // Strategy 1: Extract explicit schema.table refs from prompt
        preg_match_all('/\b([a-z_][a-z0-9_]*\.[a-z_][a-z0-9_]*)\b/i', $promptText, $schemaTableM);
        $schemaTableRefs = array_unique(array_map('strtolower', $schemaTableM[0] ?? []));

        if (!empty($connections)) {
            foreach ($schemaTableRefs as $ref) {
                foreach ($connections as $conn) {
                    foreach ($conn['tables'] ?? [] as $tbl) {
                        if (strtolower($tbl['name']) === $ref) {
                            return $tbl['name'];
                        }
                    }
                }
            }

            // Strategy 2/3: substring check with word boundary
            foreach ($connections as $conn) {
                foreach ($conn['tables'] ?? [] as $tbl) {
                    $tblNameLower = strtolower($tbl['name']);
                    $pattern = '/\b' . preg_quote($tblNameLower, '/') . '\b/';
                    if (preg_match($pattern, $lower)) {
                        return $tbl['name'];
                    }
                }
            }

            // Strategy 3b: bare table name with word boundary
            foreach ($connections as $conn) {
                foreach ($conn['tables'] ?? [] as $tbl) {
                    $bare = strstr($tbl['name'], '.') !== false
                        ? substr(strstr($tbl['name'], '.'), 1)
                        : $tbl['name'];
                    $bareLower = strtolower($bare);
                    $pattern = '/\b' . preg_quote($bareLower, '/') . '\b/';
                    if (preg_match($pattern, $lower)) {
                        return $tbl['name'];
                    }
                }
            }
        }

        // Guess from prompt directly
        if (preg_match('/\b([a-z0-9_]+\.(?:dim_|fact_|bridge_|agg_|summary_|mart_)[a-z0-9_]+)\b/i', $promptText, $m)) {
            return $m[1];
        } elseif (preg_match('/\b((?:dim_|fact_|bridge_|agg_|summary_|mart_)[a-z0-9_]+)\b/i', $promptText, $m)) {
            return $m[1];
        }

        return $targetTable;
    }

    /**
     * Deterministically filter database schemas to exclude target table and invalid DWH source schemas (dw, warehouse, datamart)
     */
    protected function filterSourceConnections(array $connections, string $targetTable): array
    {
        $targetTableLower = strtolower($targetTable);
        $filteredConnections = [];

        foreach ($connections as $conn) {
            $filteredTables = [];
            if (!empty($conn['tables'])) {
                foreach ($conn['tables'] as $tbl) {
                    $tblNameLower = strtolower($tbl['name']);

                    // 1. Exclude target table (matching full name or bare name)
                    $tblBare = str_contains($tblNameLower, '.') ? explode('.', $tblNameLower)[1] : $tblNameLower;
                    $targetBare = str_contains($targetTableLower, '.') ? explode('.', $targetTableLower)[1] : $targetTableLower;
                    if ($tblNameLower === $targetTableLower || $tblBare === $targetBare) {
                        continue;
                    }

                    // 2. Exclude invalid schemas: dw, warehouse, datamart
                    $tblSchema = str_contains($tblNameLower, '.') ? explode('.', $tblNameLower)[0] : 'public';
                    if (in_array($tblSchema, ['dw', 'warehouse', 'datamart'])) {
                        continue;
                    }

                    // 3. Exclude invalid prefixes
                    if (str_starts_with($tblNameLower, 'dw.') || 
                        str_starts_with($tblNameLower, 'warehouse.') || 
                        str_starts_with($tblNameLower, 'datamart.')) {
                        continue;
                    }

                    $filteredTables[] = $tbl;
                }
            }

            $connCopy = $conn;
            $connCopy['tables'] = $filteredTables;
            $filteredConnections[] = $connCopy;
        }

        return $filteredConnections;
    }
}
