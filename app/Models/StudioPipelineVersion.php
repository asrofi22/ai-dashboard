<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudioPipelineVersion extends Model
{
    protected $table = 'studio_pipeline_versions';

    public $timestamps = false;

    protected $fillable = [
        'pipeline_id',
        'version_number',
        'name',
        'source_connection_id',
        'source_table',
        'transformations',
        'target_connection_id',
        'target_table',
        'column_mapping',
        'canvas_data',
        'schedule_interval'
    ];

    protected $casts = [
        'transformations' => 'array',
        'column_mapping' => 'array',
        'canvas_data' => 'array'
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(StudioPipeline::class, 'pipeline_id');
    }

    public function sourceConnection(): BelongsTo
    {
        return $this->belongsTo(EtlConnection::class, 'source_connection_id');
    }

    public function targetConnection(): BelongsTo
    {
        return $this->belongsTo(EtlConnection::class, 'target_connection_id');
    }
}
