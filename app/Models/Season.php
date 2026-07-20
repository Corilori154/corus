<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'name', 'start_date', 'end_date', 'is_active'])]
class Season extends Model
{
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function courses(): HasMany { return $this->hasMany(DanceCourse::class); }

    protected function casts(): array
    {
        return ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d', 'is_active' => 'boolean'];
    }
}
