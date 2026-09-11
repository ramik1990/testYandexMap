<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'author' => [
                'name' => $this->author_name,
                'avatar' => $this->author_avatar,
                'level' => $this->author_level,
            ],
            'rating' => $this->rating,
            'text' => $this->text,
            'published_at' => $this->published_at,
            'likes' => $this->likes,
            'dislikes' => $this->dislikes,
            'business_reply' => $this->business_reply ? [
                'text' => $this->business_reply,
                'at' => $this->business_reply_at,
            ] : null,
        ];
    }
}
