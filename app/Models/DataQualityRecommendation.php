<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataQualityRecommendation extends Model
{
    protected $table = 'dq_recommendations';

    protected $fillable = [
        'table_name',
        'finding_type',
        'finding_summary',
        'business_impact',
        'recommended_action',
        'priority_level',
        'quality_score_impact',
        'is_resolved'
    ];

    protected $casts = [
        'quality_score_impact' => 'integer'
    ];
}
