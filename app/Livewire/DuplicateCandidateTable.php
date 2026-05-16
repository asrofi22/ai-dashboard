<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DuplicateCandidate;
use App\Services\GeminiService;

class DuplicateCandidateTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

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
        $query = DuplicateCandidate::with(['projectA.sourceConnection', 'projectB.sourceConnection', 'aiValidationLog']);
        
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search) {
            $query->whereHas('projectA', function($q) {
                $q->where('original_name', 'ilike', '%' . $this->search . '%');
            })->orWhereHas('projectB', function($q) {
                $q->where('original_name', 'ilike', '%' . $this->search . '%');
            });
        }

        $candidates = $query->orderBy('similarity_score', 'desc')->paginate(10);

        return view('livewire.duplicate-candidate-table', [
            'candidates' => $candidates,
        ]);
    }
}
