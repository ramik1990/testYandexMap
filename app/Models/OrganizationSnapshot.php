<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSnapshot extends Model
{
    protected $fillable = [
        'organization_id',
        'parse_run_id',
        'rating',
        'rating_count',
        'review_count',
        'reviews_stored',
        'changes',
    ];

    protected $casts = [
        'rating' => 'float',
        'rating_count' => 'integer',
        'review_count' => 'integer',
        'reviews_stored' => 'integer',
        'changes' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function parseRun(): BelongsTo
    {
        return $this->belongsTo(ParseRun::class);
    }
}
