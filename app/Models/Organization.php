<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'yandex_id',
        'source_url',
        'title',
        'address',
        'rating',
        'rating_count',
        'review_count',
        'last_parsed_at',
    ];

    protected $casts = [
        'rating' => 'float',
        'rating_count' => 'integer',
        'review_count' => 'integer',
        'last_parsed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function parseRuns(): HasMany
    {
        return $this->hasMany(ParseRun::class);
    }

    public function latestParseRun(): HasOne
    {
        return $this->hasOne(ParseRun::class)->latestOfMany();
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(OrganizationSnapshot::class);
    }

    public function yandexUrl(): string
    {
        return sprintf('https://%s/maps/org/%s/reviews/', config('yandex.entry_host'), $this->yandex_id);
    }
}
