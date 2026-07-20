<?php

namespace App\Services;

use App\Models\DanceCourse;
use Carbon\CarbonImmutable;

class LessonSchedule
{
    private const DAYS = [
        'Dimanche' => 0,
        'Lundi' => 1,
        'Mardi' => 2,
        'Mercredi' => 3,
        'Jeudi' => 4,
        'Vendredi' => 5,
        'Samedi' => 6,
    ];

    public static function generate(DanceCourse $course): void
    {
        $date = CarbonImmutable::parse($course->start_date);
        $end = CarbonImmutable::parse($course->end_date);
        $targetDay = self::DAYS[$course->day];

        while ($date->dayOfWeek !== $targetDay) {
            $date = $date->addDay();
        }

        $lessons = [];
        while ($date->lte($end)) {
            $lessons[] = ['lesson_date' => $date->toDateString()];
            $date = $date->addWeek();
        }

        $course->lessons()->createMany($lessons);
    }
}
