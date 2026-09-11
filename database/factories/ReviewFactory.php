<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'external_id' => $this->faker->unique()->lexify('????????????'),
            'author_name' => $this->faker->name(),
            'rating' => $this->faker->numberBetween(1, 5),
            'text' => $this->faker->sentence(12),
            'published_at' => $this->faker->dateTimeBetween('-2 years'),
            'content_hash' => sha1($this->faker->uuid()),
            'last_seen_at' => now(),
        ];
    }
}
