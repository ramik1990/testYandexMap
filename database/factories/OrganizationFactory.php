<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        $id = (string) $this->faker->numberBetween(100000000, 999999999);

        return [
            'user_id' => User::factory(),
            'yandex_id' => $id,
            'source_url' => "https://yandex.ru/maps/org/{$id}/",
            'title' => $this->faker->company(),
            'rating' => 4.5,
            'rating_count' => 100,
            'review_count' => 40,
        ];
    }
}
