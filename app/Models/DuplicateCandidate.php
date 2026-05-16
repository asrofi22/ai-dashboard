<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DuplicateCandidate extends Model
{
    protected $fillable = [
        'import_log_id',
        'project_a_id',
        'project_b_id',
        'similarity_score',
        'confidence_level',
        'status',
        'ai_validation_status',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportLog::class, 'import_log_id');
    }

    public function projectA(): BelongsTo
    {
        return $this->belongsTo(ImportedProject::class, 'project_a_id');
    }

    public function projectB(): BelongsTo
    {
        return $this->belongsTo(ImportedProject::class, 'project_b_id');
    }

    public function aiValidationLog(): HasOne
    {
        return $this->hasOne(AiValidationLog::class);
    }

    public function reviewHistory(): HasMany
    {
        return $this->hasMany(DuplicateReviewHistory::class);
    }
}
