<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\SourceConnection;
use App\Services\ImportService;
use App\Jobs\DetectDuplicatesJob;
use Illuminate\Support\Facades\Log;

class UploadManager extends Component
{
    use WithFileUploads;

    // Step tracking
    public string $step = 'upload'; // upload | preview | done

    // Upload form
    public $file;
    public int|null $sourceId = null;
    public array $sources = [];

    // Preview staging — data NOT yet in DB
    public array $previewData = [];
    public int $previewTotal = 0;
    public int $previewShown = 20; // rows shown in preview table

    // Status
    public string $successMessage = '';
    public string $errorMessage   = '';

    public function mount(): void
    {
        $this->sources = SourceConnection::all()->toArray();
        if (!empty($this->sources)) {
            $this->sourceId = $this->sources[0]['id'];
        } else {
            $source = SourceConnection::create([
                'name' => 'Unggahan Excel Default',
                'type' => 'excel',
            ]);
            $this->sources  = SourceConnection::all()->toArray();
            $this->sourceId = $source->id;
        }
    }

    /**
     * Step 1: Parse and preview — NO database write yet.
     */
    public function previewFile(): void
    {
        $this->validate([
            'file'     => 'required|file|mimes:csv,txt,xlsx,xls|max:51200',
            'sourceId' => 'required',
        ]);

        $this->errorMessage = '';

        try {
            $parsed = $this->parseCsvFile($this->file->getRealPath());

            if (empty($parsed)) {
                $this->errorMessage = 'File kosong atau format tidak dikenali. Pastikan file CSV memiliki baris header.';
                return;
            }

            $this->previewData  = $parsed;
            $this->previewTotal = count($parsed);
            $this->step         = 'preview';

        } catch (\Exception $e) {
            $this->errorMessage = 'Gagal membaca file: ' . $e->getMessage();
            Log::error('UploadManager::previewFile error: ' . $e->getMessage());
        }
    }

    /**
     * Step 2: User confirms — NOW save to DB and dispatch job.
     * Detection is scoped to THIS import log only.
     */
    public function confirmAndImport(): void
    {
        if (empty($this->previewData) || $this->sourceId === null) {
            $this->errorMessage = 'Tidak ada data preview. Silakan unggah ulang file Anda.';
            return;
        }

        $this->errorMessage   = '';
        $this->successMessage = '';

        try {
            $importService = app(ImportService::class);
            $log = $importService->processImport($this->sourceId, $this->previewData);

            if ($log->status === 'completed') {
                // Dispatch job (sync or queued depending on QUEUE_CONNECTION env)
                $job = new \App\Jobs\DetectDuplicatesJob($log->id);
                dispatch($job);

                // If sync mode, candidates are already processed
                $candidateCount = \App\Models\DuplicateCandidate::where(function($q) use ($log) {
                    $q->whereHas('projectA', fn($q) => $q->where('import_log_id', $log->id))
                      ->orWhereHas('projectB', fn($q) => $q->where('import_log_id', $log->id));
                })->count();

                if ($candidateCount > 0) {
                    $this->successMessage = "✅ {$log->success_records} data berhasil disimpan. Ditemukan {$candidateCount} kandidat duplikat dalam batch ini!";
                } else {
                    $this->successMessage = "✅ {$log->success_records} data berhasil disimpan. Tidak ada duplikat terdeteksi dalam batch ini (threshold: 50%).";
                }

                $this->step        = 'done';
                $this->previewData = [];

                // ✅ Notify DashboardAnalytics and DuplicateCandidateTable to refresh
                $this->dispatch('import-completed');
            } else {
                $this->errorMessage = 'Gagal menyimpan data: ' . ($log->error_details ?? 'Error tidak diketahui.');
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UploadManager::confirmAndImport error: ' . $e->getMessage());
            $this->errorMessage = 'Terjadi kesalahan saat menyimpan: ' . $e->getMessage();
        }
    }

    /**
     * Reset back to upload step without saving anything.
     */
    public function cancelPreview(): void
    {
        $this->reset(['file', 'previewData', 'previewTotal', 'errorMessage', 'successMessage']);
        $this->step = 'upload';
    }

    /**
     * Start a new upload after completion.
     */
    public function startNew(): void
    {
        $this->reset(['file', 'previewData', 'previewTotal', 'errorMessage', 'successMessage']);
        $this->step = 'upload';
    }

    private function parseCsvFile(string $path): array
    {
        $data   = [];
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return $data;
        }

        // Detect BOM and skip
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            fseek($handle, 0);
        }

        $header = fgetcsv($handle, 0, ',');
        if ($header === false) {
            fclose($handle);
            return $data;
        }

        // Clean header keys
        $header = array_map(fn($h) => trim($h, " \t\n\r\0\x0B\""), $header);

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($header) === count($row)) {
                $data[] = array_combine($header, $row);
            }
        }

        fclose($handle);
        return $data;
    }

    public function render()
    {
        $sourceName = collect($this->sources)->firstWhere('id', $this->sourceId)['name'] ?? '-';

        return view('livewire.upload-manager', [
            'sourceName'   => $sourceName,
            'previewRows'  => array_slice($this->previewData, 0, $this->previewShown),
            'previewKeys'  => !empty($this->previewData) ? array_keys($this->previewData[0]) : [],
            'hasMore'      => $this->previewTotal > $this->previewShown,
        ]);
    }
}
