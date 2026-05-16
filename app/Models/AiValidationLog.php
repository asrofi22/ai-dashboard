<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiValidationLog extends Model
{
    protected $fillable = [
        'duplicate_candidate_id',
        'prompt',
        'response',
        'result',
        'reasoning',
        'confidence_score',
    ];

    public function duplicateCandidate(): BelongsTo
    {
        return $this->belongsTo(DuplicateCandidate::class);
    }
}
