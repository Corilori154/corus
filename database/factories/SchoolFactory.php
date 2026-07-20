<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SchoolFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company().' Danse';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'email' => fake()->unique()->companyEmail(),
            'city' => fake()->city(),
            'accent' => '#ef6f7f',
            'is_active' => true,
        ];
    }
}
