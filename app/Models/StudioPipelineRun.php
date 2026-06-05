<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudioPipelineRun extends Model
{
    protected $table = 'studio_pipeline_runs';

    protected $fillable = [
        'pipeline_id',
        'status',
        'start_time',
        'end_time',
        'duration_seconds',
        'rows_read',
        'rows_written',
        'rows_rejected',
        'execution_logs',
        'error_log',
        'ai_failure_analysis'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'ai_failure_analysis' => 'array'
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(StudioPipeline::class, 'pipeline_id');
    }
}
