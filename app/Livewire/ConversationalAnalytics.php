<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\DuplicateCandidate;
use App\Models\ImportedProject;
use App\Models\ImportLog;
use App\Models\SourceConnection;
use App\Models\WarehouseTable;
use App\Models\EtlPipeline;
use App\Models\EtlJobRun;
use App\Models\DataQualityRecommendation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConversationalAnalytics extends Component
{
    public string $query = '';
    public array $messages = [];

    public function mount(): void
    {
        $this->messages[] = [
            'role'    => 'assistant',
            'content' => 'Halo! Saya adalah Asisten Data AI Anda. Tanyakan apa saja tentang platform ini — misalnya: *"Tabel apa saja yang ada di DWH?"*, *"Mengapa quality score tabel fact_payment turun?"*, *"Pipeline mana yang mengalami kegagalan eksekusi?"*, atau *"Tuliskan SQL untuk melihat 5 transaksi teratas di fact_sales"*',
        ];
    }

    public function ask(): void
    {
        $userQuery = trim($this->query);
        if (empty($userQuery)) {
            return;
        }

        $this->messages[] = ['role' => 'user', 'content' => $userQuery];
        $this->query = '';

        $this->messages[] = ['role' => 'assistant', 'content' => $this->callGemini($userQuery)];
    }

    private function getDataContext(): string
    {
        // 1. Core upload and duplicate detection metrics
        $totalProjects    = ImportedProject::count();
        $totalImports     = ImportLog::count();
        $completedImports = ImportLog::where('status', 'completed')->count();
        $failedImports    = ImportLog::where('status', 'failed')->count();
        $totalCandidates  = DuplicateCandidate::count();
        $highRisk         = DuplicateCandidate::where('confidence_level', 'high')->count();
        $aiValidated      = DuplicateCandidate::where('ai_validation_status', 'validated')->count();
        $confirmed        = DuplicateCandidate::where('status', 'confirmed')->count();
        $rejected         = DuplicateCandidate::where('status', 'rejected')->count();
        $totalSources     = SourceConnection::count();
        $duplicateRate    = $totalProjects > 0 ? round(($totalCandidates / $totalProjects) * 100, 2) : 0;

        // 2. Data Warehouse Explorer metrics
        $tablesList = WarehouseTable::orderBy('name')->get()->map(fn($t) => sprintf(
            '- Tabel "%s" (%s baris, %s kolom, skor kualitas: %s%%, Owner: %s, Deskripsi: %s)',
            $t->name,
            number_format($t->row_count),
            $t->col_count,
            $t->quality_score,
            $t->business_owner ?? 'Belum ada owner',
            $t->description ?? 'Belum didokumentasikan'
        ))->implode("\n");

        // 3. ETL Job Monitoring metrics
        $pipelinesCount = EtlPipeline::count();
        $failedRunsCount = EtlJobRun::where('status', 'Failed')->count();
        $recentRuns = EtlJobRun::with('pipeline')->orderBy('start_time', 'desc')->limit(3)->get()->map(fn($r) => sprintf(
            '- Pipeline "%s" (Status: %s, Waktu: %s, Durasi: %ss, Baris: %s, Pesan Error: %s)',
            $r->pipeline->name,
            $r->status,
            $r->start_time->format('H:i:s d-M'),
            $r->duration_seconds,
            number_format($r->rows_processed),
            $r->error_message ?? 'Tidak ada'
        ))->implode("\n");

        // 4. Data Quality Recommendations
        $recs = DataQualityRecommendation::orderBy('priority_level', 'desc')->limit(4)->get()->map(fn($r) => sprintf(
            '- Tabel "%s": Isu "%s" (Skor Penalti: %s%%, Dampak: %s, Prioritas: %s)',
            $r->table_name,
            $r->finding_type,
            $r->quality_score_impact,
            $r->business_impact,
            $r->priority_level
        ))->implode("\n");

        return <<<CONTEXT
        DATA PLATFORM METADATA DAN STATISTIK:
        
        [1] DATA WAREHOUSE TABLES:
        {$tablesList}

        [2] PIPELINE ETL MONITORING:
        - Jumlah Pipeline Terdaftar: {$pipelinesCount}
        - Total Job Gagal Saat Ini: {$failedRunsCount}
        - 3 Riwayat Eksekusi ETL Terakhir:
        {$recentRuns}

        [3] REKOMENDASI KUALITAS DATA AKTIF:
        {$recs}

        [4] DATA PROYEK & DUPLIKASI (UNGGAHAN CSV):
        - Total proyek terunggah: {$totalProjects}
        - Total sesi impor: {$totalImports} (Selesai: {$completedImports}, Gagal: {$failedImports})
        - Jumlah Sumber Data: {$totalSources}
        - Total Kandidat Duplikat Terdeteksi: {$totalCandidates} (Level Risiko Tinggi: {$highRisk})
        - Status Duplikasi: Konfirmasi: {$confirmed}, Ditolak: {$rejected}, Divalidasi AI: {$aiValidated}
        - Rata-rata Duplikasi: {$duplicateRate}%
        CONTEXT;
    }

    private function callGemini(string $userQuestion): string
    {
        $apiKey = config('services.gemini.key', env('GEMINI_API_KEY'));

        if (empty($apiKey)) {
            return 'Gemini API Key belum dikonfigurasi. Silakan tambahkan GEMINI_API_KEY di file .env Anda.';
        }

        $dataContext = $this->getDataContext();

        // Build conversation history (keep last 6 turns)
        $recentMessages = array_slice($this->messages, -7, 6);
        $conversationHistory = [];
        foreach ($recentMessages as $msg) {
            if ($msg['role'] === 'user') {
                $conversationHistory[] = ['role' => 'user', 'parts' => [['text' => $msg['content']]]];
            } elseif ($msg['role'] === 'assistant' && isset($msg['content'])) {
                $conversationHistory[] = ['role' => 'model', 'parts' => [['text' => $msg['content']]]];
            }
        }

        $systemPrompt = <<<PROMPT
        Anda adalah "AI Data Platform Assistant", asisten analitik data cerdas profesional yang tertanam dalam platform pengawasan kualitas data dan data warehouse pengguna.
        
        Tugas Anda adalah menjawab pertanyaan pengguna secara akurat berdasarkan konteks metadata platform di bawah ini.

        KONTEKS DATA PLATFORM SAAT INI:
        {$dataContext}

        ATURAN MENJAWAB:
        1. Jawab SELALU dalam Bahasa Indonesia yang sopan, ramah, dan profesional.
        2. Gunakan data statistik di atas sebagai satu-satunya kebenaran (source of truth). Jangan mengarang data.
        3. Jika ditanya tentang kegagalan pipeline, jelaskan penyebab error dan rekomendasi berdasarkan data di atas.
        4. Jika diminta membuat query SQL untuk melihat data di dim_customer, dim_product, fact_sales, atau fact_payment, berikan kueri SQL PostgreSQL yang akurat.
        5. Gunakan format Markdown (**bold**, lists, dsb) agar jawaban terstruktur dan mudah dibaca.
        6. Jawaban harus ringkas namun informatif (maksimal 3 paragraf).
        PROMPT;

        try {
            $payload = [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]]
                ],
                'contents' => array_merge(
                    $conversationHistory,
                    [['role' => 'user', 'parts' => [['text' => $userQuestion]]]]
                ),
                'generationConfig' => [
                    'temperature'     => 0.4,
                    'maxOutputTokens' => 1024,
                ],
            ];

            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey,
                    $payload
                );

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text']
                    ?? 'Maaf, saya tidak dapat menghasilkan respons saat ini.';
            }

            Log::error('Gemini chat error', ['status' => $response->status(), 'body' => $response->body()]);

            if ($response->status() === 429) {
                return '⚠️ Layanan AI sedang sibuk (batas permintaan tercapai). Harap tunggu **10-15 detik** lalu coba lagi.';
            }

            return 'Terjadi kesalahan saat menghubungi AI (kode: ' . $response->status() . '). Silakan coba lagi.';

        } catch (\Exception $e) {
            Log::error('Gemini chat exception: ' . $e->getMessage());
            return 'Koneksi ke layanan AI gagal: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.conversational-analytics');
    }
}
