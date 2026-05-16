<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\DuplicateCandidate;
use App\Models\ImportedProject;
use App\Models\ImportLog;
use App\Models\SourceConnection;
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
            'content' => 'Halo! Saya adalah Asisten Analitik Data AI Anda. Tanyakan apa saja tentang data Anda — misalnya: *"Total data yang sudah diimpor ada berapa?"*, *"Berapa banyak kandidat duplikat dengan risiko tinggi?"*, atau *"Apa rekomendasimu untuk membersihkan data?"*',
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
        $totalProjects    = ImportedProject::count();
        $totalImports     = ImportLog::count();
        $completedImports = ImportLog::where('status', 'completed')->count();
        $failedImports    = ImportLog::where('status', 'failed')->count();
        $totalCandidates  = DuplicateCandidate::count();
        $highRisk         = DuplicateCandidate::where('confidence_level', 'high')->count();
        $mediumRisk       = DuplicateCandidate::where('confidence_level', 'medium')->count();
        $lowRisk          = DuplicateCandidate::where('confidence_level', 'low')->count();
        $aiValidated      = DuplicateCandidate::where('ai_validation_status', 'validated')->count();
        $pending          = DuplicateCandidate::where('status', 'pending')->count();
        $confirmed        = DuplicateCandidate::where('status', 'confirmed')->count();
        $rejected         = DuplicateCandidate::where('status', 'rejected')->count();
        $totalSources     = SourceConnection::count();

        $duplicateRate = $totalProjects > 0
            ? round(($totalCandidates / $totalProjects) * 100, 2)
            : 0;

        // Sample of recent candidates
        $recentCandidates = DuplicateCandidate::with(['projectA', 'projectB'])
            ->orderByDesc('similarity_score')
            ->limit(5)
            ->get()
            ->map(fn($c) => sprintf(
                '- "%s" <-> "%s" (skor: %s%%, level: %s)',
                $c->projectA->original_name ?? 'N/A',
                $c->projectB->original_name ?? 'N/A',
                number_format($c->similarity_score * 100, 1),
                $c->confidence_level
            ))->implode("\n");

        return <<<CONTEXT
        DATA STATISTIK PLATFORM SAAT INI:
        - Total proyek yang diimpor: {$totalProjects}
        - Total sesi impor: {$totalImports} (selesai: {$completedImports}, gagal: {$failedImports})
        - Jumlah sumber data (Source Connections): {$totalSources}
        - Total kandidat duplikat terdeteksi: {$totalCandidates}
        - Kandidat risiko TINGGI: {$highRisk}
        - Kandidat risiko MENENGAH: {$mediumRisk}
        - Kandidat risiko RENDAH: {$lowRisk}
        - Sudah divalidasi AI: {$aiValidated}
        - Status kandidat - Menunggu: {$pending}, Dikonfirmasi: {$confirmed}, Ditolak: {$rejected}
        - Tingkat duplikasi keseluruhan: {$duplicateRate}%

        5 KANDIDAT DUPLIKAT TERATAS (skor tertinggi):
        {$recentCandidates}
        CONTEXT;
    }

    private function callGemini(string $userQuestion): string
    {
        $apiKey = config('services.gemini.key', env('GEMINI_API_KEY'));

        if (empty($apiKey)) {
            return 'Gemini API Key belum dikonfigurasi. Silakan tambahkan GEMINI_API_KEY di file .env Anda.';
        }

        $dataContext = $this->getDataContext();

        // Build conversation history for multi-turn context
        // Only keep last 6 messages to reduce token usage and avoid rate limits
        $recentMessages = array_slice($this->messages, -7, 6); // exclude current user msg
        $conversationHistory = [];
        foreach ($recentMessages as $msg) {
            if ($msg['role'] === 'user') {
                $conversationHistory[] = ['role' => 'user', 'parts' => [['text' => $msg['content']]]];
            } elseif ($msg['role'] === 'assistant' && isset($msg['content'])) {
                $conversationHistory[] = ['role' => 'model', 'parts' => [['text' => $msg['content']]]];
            }
        }

        $systemPrompt = <<<PROMPT
        Anda adalah "AI DataGov Assistant", asisten analitik data cerdas yang tertanam dalam platform deteksi duplikat enterprise milik pengguna.

        KONTEKS DATA REAL-TIME DARI DATABASE:
        {$dataContext}

        ATURAN MENJAWAB:
        1. Jawab SELALU dalam Bahasa Indonesia yang sopan, jelas, dan profesional.
        2. Gunakan data statistik di atas sebagai sumber kebenaran. Jangan mengarang data.
        3. Jika data menunjukkan angka 0 untuk sesuatu yang ditanyakan, beritahu bahwa memang belum ada data.
        4. Berikan insight dan rekomendasi yang actionable, bukan hanya melaporkan angka.
        5. Format jawaban dengan Markdown (**bold**, bullet list, dll) agar mudah dibaca.
        6. Jika pertanyaan tidak berkaitan dengan platform data quality ini, arahkan kembali ke topik yang relevan dengan sopan.
        7. Jawaban harus ringkas (maks 3-4 paragraf atau 6 bullet points) kecuali diminta lebih detail.
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
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey,
                    $payload
                );

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text']
                    ?? 'Maaf, saya tidak dapat menghasilkan respons saat ini.';
            }

            Log::error('Gemini chat error', ['status' => $response->status(), 'body' => $response->body()]);

            if ($response->status() === 429) {
                return '⚠️ Layanan AI sedang sibuk (batas permintaan tercapai). Harap tunggu **10-15 detik** lalu coba lagi. Ini adalah batasan tier gratis Gemini API.';
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
