<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lesson_date'])]
class CourseLesson extends Model
{
    public function course(): BelongsTo
    {
        return $this->belongsTo(DanceCourse::class, 'dance_course_id');
    }

    protected function casts(): array
    {
        return ['lesson_date' => 'date:Y-m-d'];
    }
}
