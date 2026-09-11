<?php

namespace Tests\Feature;

use App\Jobs\ParseOrganizationJob;
use App\Models\Organization;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_access_organizations(): void
    {
        $this->getJson('/api/organizations')->assertUnauthorized();
    }

    public function test_store_validates_url(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/organizations', ['url' => 'https://2gis.ru/moscow/firm/1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('url');
    }

    public function test_store_creates_organization_and_dispatches_job(): void
    {
        Queue::fake();

        $this->actingAs($this->user)
            ->postJson('/api/organizations', ['url' => 'https://yandex.ru/maps/org/yandeks/1124715036/reviews/'])
            ->assertCreated()
            ->assertJsonPath('data.yandex_id', '1124715036')
            ->assertJsonPath('data.parse_run.status', 'pending');

        Queue::assertPushed(ParseOrganizationJob::class, 1);

        $this->actingAs($this->user)
            ->postJson('/api/organizations', ['url' => 'https://yandex.ru/maps/org/1124715036/'])
            ->assertOk();

        $this->assertSame(1, Organization::count());
        Queue::assertPushed(ParseOrganizationJob::class, 1);
    }

    public function test_reviews_are_paginated_by_fifty(): void
    {
        $organization = Organization::factory()->for($this->user)->create();
        Review::factory()->count(120)->for($organization)->create();

        $this->actingAs($this->user)
            ->getJson("/api/organizations/{$organization->id}/reviews?page=3")
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.per_page', 50)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_user_cannot_see_foreign_organization(): void
    {
        $organization = Organization::factory()->for(User::factory())->create();

        $this->actingAs($this->user)->getJson("/api/organizations/{$organization->id}")->assertForbidden();
    }
}
