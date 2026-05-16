<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\SourceConnection;
use App\Services\ImportService;
use App\Services\DuplicateDetectionService;
use Illuminate\Support\Facades\Log;

class UploadManager extends Component
{
    use WithFileUploads;

    public $file;
    public $sourceId;
    public $sources = [];
    public $importing = false;
    public $successMessage = '';
    public $errorMessage = '';

    public function mount()
    {
        $this->sources = SourceConnection::where('status', 'active')->get();
        if ($this->sources->count() > 0) {
            $this->sourceId = $this->sources->first()->id;
        } else {
            // Create default source if none exist for demo purposes
            $source = SourceConnection::create([
                'name' => 'Unggahan Excel Default',
                'type' => 'excel',
            ]);
            $this->sources = SourceConnection::all();
            $this->sourceId = $source->id;
        }
    }

    public function processUpload()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
            'sourceId' => 'required|exists:source_connections,id',
        ]);

        $this->importing = true;
        $this->successMessage = '';
        $this->errorMessage = '';

        try {
            // Simulated parsing since we might not have maatwebsite/excel setup completely
            // In a real scenario, we'd use Excel::toArray or similar
            $data = $this->parseFile($this->file->getRealPath(), $this->file->getClientOriginalExtension());
            
            $importService = app(ImportService::class);
            $log = $importService->processImport($this->sourceId, $data);

            if ($log->status === 'completed') {
                $duplicateService = app(DuplicateDetectionService::class);
                $candidatesCount = $duplicateService->detectForLog($log->id);
                $this->successMessage = "Berhasil mengimpor {$log->success_records} data. Ditemukan {$candidatesCount} potensi duplikat.";
            } else {
                $this->errorMessage = "Impor gagal: " . $log->error_details;
            }
        } catch (\Exception $e) {
            Log::error("Upload Manager error: " . $e->getMessage());
            $this->errorMessage = "Terjadi kesalahan saat mengimpor: " . $e->getMessage();
        }

        $this->importing = false;
        $this->reset('file');
    }

    private function parseFile($path, $extension)
    {
        // Dummy basic CSV parser for the sake of completion
        $data = [];
        if (($handle = fopen($path, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($header) === count($row)) {
                    $data[] = array_combine($header, $row);
                } else {
                    $data[] = $row;
                }
            }
            fclose($handle);
        }
        return $data;
    }

    public function render()
    {
        return view('livewire.upload-manager');
    }
}
