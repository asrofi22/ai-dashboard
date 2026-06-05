<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EtlPipeline extends Model
{
    protected $table = 'etl_pipelines';

    protected $fillable = [
        'name',
        'source_layer',
        'target_layer',
        'frequency',
        'is_active',
        'definition_prompt',
        'generated_script'
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(EtlJobRun::class, 'pipeline_id');
    }
}
