<?php

namespace App\Services;

use App\Enums\ParseStatus;
use App\Jobs\ParseOrganizationJob;
use App\Models\Organization;
use App\Models\ParseRun;
use App\Models\User;
use App\Services\YandexMaps\UrlResolver;

final class OrganizationService
{
    public function __construct(private readonly UrlResolver $resolver)
    {
    }

    public function connect(User $user, string $url): Organization
    {
        $yandexId = $this->resolver->resolve($url);

        $organization = $user->organizations()->firstOrNew(['yandex_id' => $yandexId]);
        $organization->source_url = $url;
        $organization->save();

        $this->startParse($organization);

        return $organization;
    }

    public function startParse(Organization $organization): ParseRun
    {
        $active = $organization->parseRuns()
            ->whereIn('status', [ParseStatus::Pending, ParseStatus::Running])
            ->latest('id')
            ->first();

        if ($active) {
            return $active;
        }

        $run = $organization->parseRuns()->create(['status' => ParseStatus::Pending]);
        ParseOrganizationJob::dispatch($run);

        return $run;
    }
}
