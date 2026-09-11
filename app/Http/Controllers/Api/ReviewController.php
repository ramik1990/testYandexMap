<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public const PER_PAGE = 50;

    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('view', $organization);

        return ReviewResource::collection(
            $organization->reviews()->orderByDesc('published_at')->orderByDesc('id')->paginate(self::PER_PAGE)
        );
    }
}
