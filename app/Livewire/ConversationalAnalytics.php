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
use App\Models\EtlConnection;
use App\Models\DataQualityRecommendation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ConversationalAnalytics extends Component
{
    public string $query = '';
    public array $messages = [];

    /** SQL result keyed by message index */
    public array $queryResults = [];

    public function mount(): void
    {
        $this->messages[] = [
            'role'    => 'assistant',
            'content' => 'Halo! Saya adalah **Asisten Data AI** yang kini bisa **query database langsung**. Tanyakan apa saja — *"Customer mana yang punya saldo tertinggi?"*, *"Tampilkan 5 order terakhir"*, atau *"Pipeline mana yang sering gagal?"*',
        ];
    }

    public function ask(): void
    {
        $userQuery = trim($this->query);
        if (empty($userQuery)) return;

        $this->messages[] = ['role' => 'user', 'content' => $userQuery];
        $this->query = '';

        // Single API call: AI decides & generates SQL (if needed) in one shot
        $decision = $this->callGeminiOnce($userQuery);

        $msgIdx    = count($this->messages);
        $answerText = $decision['answer'] ?? 'Maaf, tidak ada respons.';
        $answerText = $this->sanitizeUtf8($answerText);

        if (!empty($decision['sql'])) {
            // Execute the SQL
            $execResult = $this->executeSql($decision['sql'], $decision['connection_id'] ?? null);

            $this->queryResults[$msgIdx] = [
                'sql'   => $decision['sql'],
                'rows'  => $execResult['rows'] ?? [],
                'cols'  => $execResult['cols'] ?? [],
                'error' => $execResult['error'] ?? null,
                'count' => $execResult['count'] ?? 0,
            ];

            // Append data summary to the answer if AI didn't already mention it
            if (!empty($execResult['error'])) {
                $answerText .= "\n\n⚠️ *Catatan: Query gagal dieksekusi — " . $execResult['error'] . "*";
            } elseif (empty($execResult['rows'])) {
                $answerText .= "\n\n*Query dieksekusi namun tidak mengembalikan data.*";
            }

            $this->messages[] = ['role' => 'assistant', 'content' => $answerText, 'has_result' => $msgIdx];
        } else {
            $this->messages[] = ['role' => 'assistant', 'content' => $answerText];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SINGLE Gemini call — returns both answer text AND sql (if needed)
    // ─────────────────────────────────────────────────────────────
    private function callGeminiOnce(string $userQuestion): array
    {
        $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY') ?? '';
        if (empty($apiKey)) {
            return ['answer' => '❌ GEMINI_API_KEY belum dikonfigurasi di .env.', 'sql' => null];
        }

        $schemaContext = $this->buildSchemaContext();
        $dataContext   = $this->getDataContext();
        $historyText   = $this->buildHistoryText();

        $systemPrompt = <<<PROMPT
Anda adalah "AI Data Platform Assistant" — asisten analitik data cerdas yang tertanam dalam platform pengawasan kualitas data dan data warehouse.

SKEMA DATABASE (ETL Connections aktif):
{$schemaContext}

METADATA PLATFORM:
{$dataContext}

RIWAYAT PERCAKAPAN TERAKHIR:
{$historyText}

TUGAS DAN FORMAT RESPONS:
Analisis pertanyaan pengguna. Kembalikan HANYA satu objek JSON valid (tanpa markdown fence, tanpa komentar):

Jika pertanyaan memerlukan data aktual dari database (misal: siapa X terbesar, tampilkan record, hitung jumlah, dll):
{
  "needs_sql": true,
  "sql": "SELECT ... FROM ... LIMIT 20",
  "connection_id": <integer id koneksi atau null>,
  "answer": "<jawaban dalam Bahasa Indonesia yang menjelaskan SQL apa yang dijalankan dan apa yang diharapkan, format Markdown>"
}

Jika pertanyaan bisa dijawab dari metadata platform atau bersifat umum:
{
  "needs_sql": false,
  "sql": null,
  "connection_id": null,
  "answer": "<jawaban lengkap dalam Bahasa Indonesia, format Markdown, maksimal 3 paragraf>"
}

ATURAN SQL:
- Hanya boleh SELECT. DILARANG: INSERT, UPDATE, DELETE, DROP, TRUNCATE, ALTER.
- Selalu sertakan LIMIT 20.
- Gunakan HANYA nama tabel dan kolom yang ADA di skema di atas.
- Jika tidak yakin tabel/kolom ada, kembalikan needs_sql: false.

ATURAN JAWABAN:
- Selalu Bahasa Indonesia yang sopan dan profesional.
- Gunakan Markdown (**bold**, list, *italic*).
- Jangan mengarang data yang tidak ada di metadata atau hasil query.
PROMPT;

        try {
            $response = Http::timeout(25)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey,
                    [
                        'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                        'contents' => [['role' => 'user', 'parts' => [['text' => $userQuestion]]]],
                        'generationConfig' => [
                            'temperature'     => 0.2,
                            'maxOutputTokens' => 1024,
                        ],
                    ]
                );

            if ($response->successful()) {
                $raw = $response->json('candidates.0.content.parts.0.text', '{}');
                // Strip possible markdown code fences
                $raw = trim(preg_replace('/^```(?:json)?\s*/i', '', $raw));
                $raw = preg_replace('/\s*```\s*$/i', '', $raw);

                $decoded = json_decode($raw, true);
                if (is_array($decoded) && isset($decoded['answer'])) {
                    return $decoded;
                }
                // If JSON parsing failed, return raw text as answer
                return ['answer' => $raw ?: 'Maaf, tidak ada respons.', 'sql' => null];
            }

            Log::error('Gemini chat error', ['status' => $response->status(), 'body' => $response->body()]);

            if ($response->status() === 429) {
                return ['answer' => '⚠️ Layanan AI sedang sibuk (batas permintaan tercapai). Harap tunggu **30 detik** lalu coba lagi.', 'sql' => null];
            }

            return ['answer' => 'Terjadi kesalahan API (kode: ' . $response->status() . '). Silakan coba lagi.', 'sql' => null];

        } catch (\Exception $e) {
            Log::error('Gemini exception: ' . $e->getMessage());
            return ['answer' => 'Koneksi ke layanan AI gagal: ' . $e->getMessage(), 'sql' => null];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Execute SQL safely (SELECT only, max 20 rows)
    // ─────────────────────────────────────────────────────────────
    private function executeSql(?string $sql, ?int $connectionId): array
    {
        if (empty($sql)) {
            return ['rows' => [], 'cols' => [], 'error' => 'Query SQL kosong.', 'count' => 0];
        }

        // Safety: only allow SELECT
        if (!preg_match('/^\s*SELECT\s/i', $sql)) {
            return ['rows' => [], 'cols' => [], 'error' => 'Hanya SELECT yang diperbolehkan.', 'count' => 0];
        }

        // Ensure LIMIT is present
        if (!preg_match('/\bLIMIT\s+\d+/i', $sql)) {
            $sql = rtrim($sql, '; ') . ' LIMIT 20';
        }

        try {
            if ($connectionId) {
                $conn = EtlConnection::find($connectionId);
                $dbConn = $conn ? $conn->getDatabaseConnection() : DB::connection();
            } else {
                $active = EtlConnection::where('status', 'active')->first();
                $dbConn = $active ? $active->getDatabaseConnection() : DB::connection();
            }

            $results = $dbConn->select($sql);
            $rows    = array_map(fn($r) => (array) $r, $results);
            $cols    = !empty($rows) ? array_keys($rows[0]) : [];

            return ['rows' => $rows, 'cols' => $cols, 'error' => null, 'count' => count($rows)];
        } catch (\Exception $e) {
            Log::warning('SQL exec error: ' . $e->getMessage());
            return ['rows' => [], 'cols' => [], 'error' => $e->getMessage(), 'count' => 0];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────
    private function buildSchemaContext(): string
    {
        $connections = EtlConnection::where('status', 'active')->get();
        if ($connections->isEmpty()) return 'Tidak ada koneksi aktif.';

        return $connections->map(function ($conn) {
            $meta   = $conn->metadata ?? [];
            $tables = array_merge($meta['tables'] ?? [], $meta['views'] ?? []);
            $tLines = array_map(function ($t) {
                $cols = is_array($t['columns']) ? implode(', ', $t['columns']) : $t['columns'];
                return "  - {$t['name']}({$cols})";
            }, $tables);
            return "Koneksi #{$conn->id} [{$conn->name}] Driver:{$conn->driver}\n" . implode("\n", $tLines);
        })->implode("\n\n");
    }

    private function getDataContext(): string
    {
        $totalCandidates = DuplicateCandidate::count();
        $highRisk        = DuplicateCandidate::where('confidence_level', 'high')->count();
        $confirmed       = DuplicateCandidate::where('status', 'confirmed')->count();

        $tablesList = WarehouseTable::orderBy('name')->get()->map(fn($t) => sprintf(
            '- "%s": %s baris, %s kolom, kualitas %s%%',
            $t->name, number_format($t->row_count), $t->col_count, $t->quality_score
        ))->implode("\n");

        $failedRuns  = EtlJobRun::where('status', 'Failed')->count();
        $recentRuns  = EtlJobRun::with('pipeline')->orderBy('start_time', 'desc')->limit(3)->get()->map(fn($r) =>
            "- {$r->pipeline->name}: {$r->status}, error: " . ($r->error_message ?? 'OK')
        )->implode("\n");

        return <<<CTX
[WAREHOUSE]: {$tablesList}
[ETL RUNS]: {$failedRuns} gagal, 3 terakhir:
{$recentRuns}
[DUPLIKASI]: {$totalCandidates} kandidat, {$highRisk} risiko tinggi, {$confirmed} dikonfirmasi
CTX;
    }

    // ─────────────────────────────────────────────────────────────
    // Strip non-UTF-8 characters that crash CommonMark / Str::markdown()
    // ─────────────────────────────────────────────────────────────
    private function sanitizeUtf8(string $text): string
    {
        // Convert to UTF-8, replacing invalid sequences with ''
        $clean = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        // Remove any remaining non-printable / invalid bytes
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean ?? $text);
        return $clean ?? $text;
    }

    private function buildHistoryText(): string
    {
        $recent = array_slice($this->messages, -5);
        return implode("\n", array_map(
            fn($m) => ($m['role'] === 'user' ? 'User' : 'AI') . ': ' . mb_substr(strip_tags($m['content']), 0, 200),
            $recent
        ));
    }

    public function render()
    {
        return view('livewire.conversational-analytics');
    }
}
