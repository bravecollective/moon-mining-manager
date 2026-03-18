<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'eve_id'        => $this->faker->unique()->numerify('########'),
            'name'          => $this->faker->name(),
            'avatar'        => $this->faker->imageUrl(128, 128),
            'token'         => Str::random(40),
            'refresh_token' => Str::random(40),
        ];
    }
}
