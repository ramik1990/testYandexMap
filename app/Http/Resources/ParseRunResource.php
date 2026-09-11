<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParseRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'progress' => $this->progressPercent(),
            'pages_done' => $this->pages_done,
            'pages_total' => $this->pages_total,
            'reviews_fetched' => $this->reviews_fetched,
            'attempts' => $this->attempts,
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'created_at' => $this->created_at,
        ];
    }
}
