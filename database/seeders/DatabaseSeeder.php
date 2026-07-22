<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\DanceCourse;
use App\Models\School;
use App\Services\LessonSchedule;
use App\Models\SchoolLocation;
use App\Models\DanceDiscipline;
use App\Models\DanceLevel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $school = School::updateOrCreate(['slug' => 'corus'], [
            'name' => 'Corus Studio',
            'email' => 'admin@corus.test',
            'city' => 'Genève',
            'accent' => '#ef6f7f',
            'is_active' => true,
        ]);

        User::updateOrCreate(['email' => 'admin@corus.test'], [
            'school_id' => $school->id,
            'name' => 'Administration Corus',
            'password' => 'Corus2026!',
            'is_admin' => true,
        ]);

        $courses = [
            ['title' => 'Danse contemporaine', 'style' => 'Contemporain', 'level' => 'Tous niveaux', 'day' => 'Lundi', 'time' => '18:30 – 20:00', 'teacher' => 'Clara Martin', 'places' => 5, 'capacity' => 14, 'price' => 28, 'accent' => '#e8a6a8', 'image' => 'https://images.unsplash.com/photo-1547153760-18fc86324498?auto=format&fit=crop&w=1000&q=85'],
            ['title' => 'Ballet classique', 'style' => 'Classique', 'level' => 'Intermédiaire', 'day' => 'Mardi', 'time' => '19:00 – 20:30', 'teacher' => 'Sofia Laurent', 'places' => 3, 'capacity' => 12, 'price' => 32, 'accent' => '#c6bdd9', 'image' => 'https://images.unsplash.com/photo-1518834107812-67b0b7c58434?auto=format&fit=crop&w=1000&q=85'],
            ['title' => 'Hip-hop foundations', 'style' => 'Hip-hop', 'level' => 'Débutant', 'day' => 'Mercredi', 'time' => '17:30 – 19:00', 'teacher' => 'Malik Johnson', 'places' => 8, 'capacity' => 18, 'price' => 25, 'accent' => '#edc77d', 'image' => 'https://images.unsplash.com/photo-1535525153412-5a42439a210d?auto=format&fit=crop&w=1000&q=85'],
            ['title' => 'Salsa & rythmes latins', 'style' => 'Salsa', 'level' => 'Tous niveaux', 'day' => 'Jeudi', 'time' => '20:00 – 21:30', 'teacher' => 'Camila Ruiz', 'places' => 2, 'capacity' => 16, 'price' => 28, 'accent' => '#dc8e72', 'image' => 'https://images.unsplash.com/photo-1504609813442-a8924e83f76e?auto=format&fit=crop&w=1000&q=85'],
            ['title' => 'Éveil à la danse', 'style' => 'Enfants', 'level' => '4 – 6 ans', 'day' => 'Samedi', 'time' => '09:30 – 10:30', 'teacher' => 'Léa Bernard', 'places' => 6, 'capacity' => 10, 'price' => 20, 'accent' => '#9dcbb8', 'image' => 'https://images.unsplash.com/photo-1594784054224-45ed155e7851?auto=format&fit=crop&w=1000&q=85'],
            ['title' => 'Jazz Broadway', 'style' => 'Jazz', 'level' => 'Intermédiaire', 'day' => 'Samedi', 'time' => '11:00 – 12:30', 'teacher' => 'Emma Wilson', 'places' => 0, 'capacity' => 14, 'price' => 30, 'accent' => '#9ebed1', 'image' => 'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?auto=format&fit=crop&w=1000&q=85'],
        ];

        foreach ($courses as $index => $course) {
            $location = SchoolLocation::firstOrCreate(['school_id' => $school->id, 'name' => 'Corus Studio']);
            $discipline = DanceDiscipline::firstOrCreate(['school_id' => $school->id, 'name' => $course['style']]);
            $level = DanceLevel::firstOrCreate(['school_id' => $school->id, 'name' => $course['level']]);
            $danceCourse = DanceCourse::updateOrCreate(
                ['school_id' => $school->id, 'title' => $course['title']],
                [
                    ...$course,
                    'school_location_id' => $location->id,
                    'dance_discipline_id' => $discipline->id,
                    'dance_level_id' => $level->id,
                    'start_date' => '2026-09-01',
                    'end_date' => '2027-06-30',
                    'session_price' => $course['price'] * 30,
                    'location' => 'Corus Studio',
                    'description' => 'Progressez à votre rythme dans un cours dynamique, créatif et bienveillant.',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );

            if ($danceCourse->lessons()->doesntExist()) {
                LessonSchedule::generate($danceCourse);
            }
        }
    }
}
