<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'external_id',
        'author_name',
        'author_avatar',
        'author_level',
        'rating',
        'text',
        'published_at',
        'likes',
        'dislikes',
        'business_reply',
        'business_reply_at',
        'content_hash',
        'last_seen_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'likes' => 'integer',
        'dislikes' => 'integer',
        'published_at' => 'datetime',
        'business_reply_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
