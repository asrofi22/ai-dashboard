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

    // Modal state - Store only ID to prevent serialization issues
    public $showModal = false;
    public $selectedCandidateId = null;

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

    public function openDetail($candidateId): void
    {
        $this->selectedCandidateId = $candidateId;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedCandidateId = null;
    }

    /**
     * Computed-like helper to get the candidate with all relationships
     */
    public function getSelectedCandidateProperty()
    {
        if (!$this->selectedCandidateId) return null;

        return DuplicateCandidate::with([
            'projectA.sourceConnection', 
            'projectB.sourceConnection', 
            'aiValidationLog'
        ])->find($this->selectedCandidateId);
    }

    public function resolveAsDuplicate(): void
    {
        $candidate = $this->getSelectedCandidateProperty();
        if ($candidate) {
            $candidate->update(['status' => 'confirmed']);
            $this->closeModal();
            $this->dispatch('import-completed'); 
        }
    }

    public function resolveAsNotDuplicate(): void
    {
        $candidate = $this->getSelectedCandidateProperty();
        if ($candidate) {
            $candidate->update(['status' => 'rejected']);
            $this->closeModal();
            $this->dispatch('import-completed');
        }
    }

    public function validateWithAi($candidateId)
    {
        $candidate = DuplicateCandidate::with(['projectA', 'projectB'])->findOrFail($candidateId);
        
        $gemini = app(GeminiService::class);
        $result = $gemini->validateDuplicate($candidate->projectA->original_name, $candidate->projectB->original_name);
        
        if ($result) {
            $candidate->aiValidationLog()->updateOrCreate(
                ['duplicate_candidate_id' => $candidate->id],
                [
                    'prompt' => $result['prompt'],
                    'response' => $result['response'],
                    'result' => $result['result'],
                    'reasoning' => $result['reasoning'],
                    'confidence_score' => $result['confidence_score'],
                ]
            );
            
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
            $likeOperator = \Illuminate\Support\Facades\DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function($q) use ($likeOperator) {
                $q->whereHas('projectA', function($q) use ($likeOperator) {
                    $q->where('original_name', $likeOperator, '%' . $this->search . '%');
                })->orWhereHas('projectB', function($q) use ($likeOperator) {
                    $q->where('original_name', $likeOperator, '%' . $this->search . '%');
                });
            });
        }

        $candidates = $query->orderBy('similarity_score', 'desc')->paginate(10);
        $batches = ImportLog::with('sourceConnection')->orderBy('created_at', 'desc')->get();

        return view('livewire.duplicate-candidate-table', [
            'candidates' => $candidates,
            'batches' => $batches,
            'selectedCandidate' => $this->getSelectedCandidateProperty()
        ]);
    }
}
