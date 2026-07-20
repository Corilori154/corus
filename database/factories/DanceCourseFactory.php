<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class DanceCourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'title' => fake()->unique()->words(3, true),
            'style' => 'Contemporain',
            'level' => 'Tous niveaux',
            'day' => 'Lundi',
            'time' => '18:30 – 20:00',
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'teacher' => fake()->name(),
            'location' => 'Studio principal',
            'description' => 'Un cours dynamique et bienveillant adapté au niveau des participants.',
            'places' => 10,
            'capacity' => 12,
            'price' => 25,
            'session_price' => 750,
            'accent' => '#ef6f7f',
            'image' => 'https://images.unsplash.com/photo-1547153760-18fc86324498',
            'is_active' => true,
            'couple_mode' => false,
            'max_role_gap' => null,
            'balance_after_count' => 0,
        ];
    }
}
