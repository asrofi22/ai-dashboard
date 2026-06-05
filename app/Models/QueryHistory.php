<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueryHistory extends Model
{
    protected $table = 'query_histories';

    protected $fillable = [
        'natural_query',
        'generated_sql',
        'execution_status',
        'execution_error',
        'chart_type'
    ];
}
