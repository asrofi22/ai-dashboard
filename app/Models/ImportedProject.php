<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportedProject extends Model
{
    protected $fillable = [
        'source_connection_id',
        'import_log_id',
        'external_id',
        'original_name',
        'normalized_name',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function sourceConnection(): BelongsTo
    {
        return $this->belongsTo(SourceConnection::class);
    }

    public function importLog(): BelongsTo
    {
        return $this->belongsTo(ImportLog::class);
    }
}
