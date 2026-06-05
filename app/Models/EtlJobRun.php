<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtlJobRun extends Model
{
    protected $table = 'etl_job_runs';

    protected $fillable = [
        'pipeline_id',
        'status',
        'start_time',
        'end_time',
        'duration_seconds',
        'rows_processed',
        'error_message',
        'ai_failure_analysis'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_seconds' => 'integer',
        'rows_processed' => 'integer',
        'ai_failure_analysis' => 'array'
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(EtlPipeline::class, 'pipeline_id');
    }
}
