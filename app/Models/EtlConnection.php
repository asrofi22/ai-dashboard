<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EtlConnection extends Model
{
    protected $table = 'etl_connections';

    protected $fillable = [
        'name',
        'type',
        'driver',
        'config',
        'status',
        'metadata'
    ];

    protected $casts = [
        'config' => 'array',
        'metadata' => 'array'
    ];

    public function sourcePipelines(): HasMany
    {
        return $this->hasMany(StudioPipeline::class, 'source_connection_id');
    }

    public function targetPipelines(): HasMany
    {
        return $this->hasMany(StudioPipeline::class, 'target_connection_id');
    }
}
