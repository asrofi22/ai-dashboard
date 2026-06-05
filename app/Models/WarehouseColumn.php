<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseColumn extends Model
{
    protected $table = 'warehouse_columns';

    protected $fillable = [
        'table_id',
        'name',
        'data_type',
        'is_nullable',
        'distinct_count',
        'missing_percentage',
        'min_value',
        'max_value',
        'mean_value'
    ];

    protected $casts = [
        'distinct_count' => 'integer',
        'missing_percentage' => 'decimal:2'
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(WarehouseTable::class, 'table_id');
    }
}
