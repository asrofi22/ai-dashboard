<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseTable extends Model
{
    protected $table = 'warehouse_tables';

    protected $fillable = [
        'name',
        'row_count',
        'col_count',
        'source_system',
        'quality_score',
        'description',
        'dashboards_used',
        'key_columns',
        'business_owner',
        'last_refresh'
    ];

    protected $casts = [
        'dashboards_used' => 'array',
        'key_columns' => 'array',
        'last_refresh' => 'datetime'
    ];

    public function columns(): HasMany
    {
        return $this->hasMany(WarehouseColumn::class, 'table_id');
    }
}
