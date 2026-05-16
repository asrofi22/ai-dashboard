<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuplicateReviewHistory extends Model
{
    protected $table = 'duplicate_review_history';

    protected $fillable = [
        'duplicate_candidate_id',
        'user_id',
        'action',
        'notes',
    ];

    public function duplicateCandidate(): BelongsTo
    {
        return $this->belongsTo(DuplicateCandidate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
