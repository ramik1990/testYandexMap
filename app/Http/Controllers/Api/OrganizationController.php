<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationController extends Controller
{
    public function __construct(private readonly OrganizationService $service)
    {
        $this->authorizeResource(Organization::class, 'organization');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $organizations = $request->user()->organizations()
            ->with('latestParseRun')
            ->withCount('reviews')
            ->latest('id')
            ->get();

        return OrganizationResource::collection($organizations);
    }

    public function store(StoreOrganizationRequest $request): OrganizationResource
    {
        $organization = $this->service->connect($request->user(), $request->validated('url'));

        return $this->resource($organization);
    }

    public function show(Organization $organization): OrganizationResource
    {
        return $this->resource($organization);
    }

    public function update(Organization $organization): OrganizationResource
    {
        $this->service->startParse($organization);

        return $this->resource($organization);
    }

    public function destroy(Organization $organization): JsonResponse
    {
        $organization->delete();

        return response()->json(null, 204);
    }

    private function resource(Organization $organization): OrganizationResource
    {
        return new OrganizationResource($organization->load('latestParseRun')->loadCount('reviews'));
    }
}
