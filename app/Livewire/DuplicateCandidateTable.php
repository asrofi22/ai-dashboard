<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use App\Models\DuplicateCandidate;
use App\Models\ImportLog;
use App\Services\GeminiService;

class DuplicateCandidateTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $batchId = '';

    /**
     * Auto-refresh table when a new import completes.
     */
    #[On('import-completed')]
    public function refresh(): void
    {
        $this->resetPage();
    }

    /**
     * Listen for filter updates from the dashboard
     */
    #[On('batch-filter-updated')]
    public function updateBatchFilter($batchId): void
    {
        $this->batchId = $batchId;
        $this->resetPage();
    }

    public function validateWithAi($candidateId)
    {
        $candidate = DuplicateCandidate::with(['projectA', 'projectB'])->findOrFail($candidateId);
        
        $gemini = app(GeminiService::class);
        $result = $gemini->validateDuplicate($candidate->projectA->original_name, $candidate->projectB->original_name);
        
        if ($result) {
            $candidate->aiValidationLog()->create([
                'prompt' => $result['prompt'],
                'response' => $result['response'],
                'result' => $result['result'],
                'reasoning' => $result['reasoning'],
                'confidence_score' => $result['confidence_score'],
            ]);
            
            $candidate->update(['ai_validation_status' => 'validated']);
        }
    }

    public function render()
    {
        $query = DuplicateCandidate::with(['projectA.sourceConnection', 'projectB.sourceConnection', 'aiValidationLog', 'batch']);
        
        if ($this->batchId) {
            $query->where('import_log_id', $this->batchId);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('projectA', function($q) {
                    $q->where('original_name', 'ilike', '%' . $this->search . '%');
                })->orWhereHas('projectB', function($q) {
                    $q->where('original_name', 'ilike', '%' . $this->search . '%');
                });
            });
        }

        $candidates = $query->orderBy('similarity_score', 'desc')->paginate(10);
        $batches = ImportLog::with('sourceConnection')->orderBy('created_at', 'desc')->get();

        return view('livewire.duplicate-candidate-table', [
            'candidates' => $candidates,
            'batches' => $batches,
        ]);
    }
}
