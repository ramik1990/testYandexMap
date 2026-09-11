<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'rating' => $this->rating,
            'rating_count' => $this->rating_count,
            'review_count' => $this->review_count,
            'reviews_stored' => $this->reviews_stored,
            'changes' => $this->changes,
        ];
    }
}
