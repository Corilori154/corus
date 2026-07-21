<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['school_id', 'season_id', 'school_location_id', 'dance_discipline_id', 'dance_level_id', 'title', 'style', 'level', 'day', 'time', 'start_date', 'end_date', 'teacher', 'location', 'description', 'places', 'capacity', 'price', 'session_price', 'trial_is_free', 'trial_price', 'accent', 'image', 'is_active', 'couple_mode', 'max_role_gap', 'balance_after_count', 'waitlist_invitation_hours', 'sort_order'])]
class DanceCourse extends Model
{
    use HasFactory;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class)->orderBy('lesson_date');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function pricingCategories(): BelongsToMany
    {
        return $this->belongsToMany(PricingCategory::class)->withPivot('price')->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'session_price' => 'decimal:2',
            'trial_price' => 'decimal:2',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'is_active' => 'boolean',
            'couple_mode' => 'boolean',
            'trial_is_free' => 'boolean',
        ];
    }
}
