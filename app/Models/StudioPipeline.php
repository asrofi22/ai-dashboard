<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudioPipeline extends Model
{
    protected $table = 'studio_pipelines';

    protected $fillable = [
        'name',
        'source_connection_id',
        'source_table',
        'transformations',
        'target_connection_id',
        'target_table',
        'column_mapping',
        'is_active'
    ];

    protected $casts = [
        'transformations' => 'array',
        'column_mapping' => 'array'
    ];

    public function sourceConnection(): BelongsTo
    {
        return $this->belongsTo(EtlConnection::class, 'source_connection_id');
    }

    public function targetConnection(): BelongsTo
    {
        return $this->belongsTo(EtlConnection::class, 'target_connection_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(StudioPipelineRun::class, 'pipeline_id');
    }
}
