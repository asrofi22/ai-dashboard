<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportLog extends Model
{
    protected $fillable = [
        'source_connection_id',
        'status',
        'total_records',
        'success_records',
        'failed_records',
        'error_details',
    ];

    public function sourceConnection(): BelongsTo
    {
        return $this->belongsTo(SourceConnection::class);
    }

    public function importedProjects(): HasMany
    {
        return $this->hasMany(ImportedProject::class);
    }
}
