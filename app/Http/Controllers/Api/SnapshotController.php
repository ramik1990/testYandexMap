<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SnapshotResource;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SnapshotController extends Controller
{
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('view', $organization);

        return SnapshotResource::collection($organization->snapshots()->latest('id')->limit(20)->get());
    }
}
