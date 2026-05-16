<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceConnection extends Model
{
    protected $fillable = [
        'name',
        'type',
        'config',
        'status',
        'last_sync_at',
    ];

    protected $casts = [
        'config' => 'array',
        'last_sync_at' => 'datetime',
    ];

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    public function importedProjects(): HasMany
    {
        return $this->hasMany(ImportedProject::class);
    }
}
