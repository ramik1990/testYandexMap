<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'yandex_id' => $this->yandex_id,
            'source_url' => $this->source_url,
            'yandex_url' => $this->yandexUrl(),
            'title' => $this->title,
            'address' => $this->address,
            'rating' => $this->rating,
            'rating_count' => $this->rating_count,
            'review_count' => $this->review_count,
            'reviews_stored' => $this->reviews_count ?? $this->reviews()->count(),
            'last_parsed_at' => $this->last_parsed_at,
            'created_at' => $this->created_at,
            'parse_run' => new ParseRunResource($this->latestParseRun),
        ];
    }
}
