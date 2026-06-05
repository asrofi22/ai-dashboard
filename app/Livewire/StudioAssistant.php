<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\StudioPipeline;
use App\Models\EtlConnection;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

class StudioAssistant extends Component
{
    public string $prompt = '';
    public bool $isGenerating = false;
    public $generatedPlan = null; // Stores parsed output from Gemini/fallback
    public string $errorMessage = '';
    public string $successMessage = '';

    protected array $rules = [
        'prompt' => 'required|min:10'
    ];

    public function generatePipeline(): void
    {
        $this->validate();
        $this->isGenerating = true;
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->generatedPlan = null;

        try {
            $gemini = app(GeminiService::class);
            $plan = $gemini->generateEtlStudioPipeline($this->prompt);

            if ($plan && !empty($plan['pipeline_name'])) {
                $this->generatedPlan = $plan;
            } else {
                $this->errorMessage = 'Gagal menghasilkan pipeline. Coba ubah atau perjelas deskripsi Anda.';
            }
        } catch (\Exception $e) {
            Log::error("StudioAssistant::generatePipeline error: " . $e->getMessage());
            $this->errorMessage = 'Terjadi kesalahan sistem saat menghubungi AI: ' . $e->getMessage();
        }

        $this->isGenerating = false;
    }

    public function savePipeline(): void
    {
        if (!$this->generatedPlan) return;

        try {
            $likeOperator = \Illuminate\Support\Facades\DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

            // Find or fallback source connection
            $sourceConn = EtlConnection::where('name', $likeOperator, '%' . ($this->generatedPlan['source_connection_name'] ?? '') . '%')->first()
                ?? EtlConnection::where('type', 'Database')->first()
                ?? EtlConnection::first();

            // Find or fallback target connection
            $targetConn = EtlConnection::where('name', $likeOperator, '%' . ($this->generatedPlan['target_connection_name'] ?? '') . '%')->first()
                ?? EtlConnection::where('name', 'PostgreSQL Data Warehouse')->first()
                ?? EtlConnection::first();

            if (!$sourceConn || !$targetConn) {
                $this->errorMessage = 'Gagal menyimpan. Pastikan Anda telah membuat koneksi sumber dan target di modul Connections terlebih dahulu.';
                return;
            }

            // Verify mapping exists
            $mapping = [];
            foreach ($this->generatedPlan['column_mapping'] ?? [] as $m) {
                $mapping[] = [
                    'source' => $m['source'] ?? '',
                    'target' => $m['target'] ?? ''
                ];
            }

            $pipeline = StudioPipeline::create([
                'name' => $this->generatedPlan['pipeline_name'],
                'source_connection_id' => $sourceConn->id,
                'source_table' => $this->generatedPlan['source_table'] ?? 'customers_raw',
                'transformations' => $this->generatedPlan['transformations'] ?? [],
                'target_connection_id' => $targetConn->id,
                'target_table' => $this->generatedPlan['target_table'] ?? 'dim_customer',
                'column_mapping' => $mapping,
                'is_active' => 'active'
            ]);

            $this->successMessage = "✅ Pipeline '{$pipeline->name}' berhasil disimpan ke sistem! Anda sekarang dapat menjalankannya di submenu Pipeline Runs.";
            $this->generatedPlan = null;
            $this->prompt = '';
        } catch (\Exception $e) {
            Log::error("StudioAssistant::savePipeline error: " . $e->getMessage());
            $this->errorMessage = 'Gagal menyimpan pipeline ke database: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.studio-assistant');
    }
}
