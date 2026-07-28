<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'course_count', 'discount_type', 'percentage', 'fixed_amount'])]
class DiscountRule extends Model
{
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'fixed_amount' => 'decimal:2',
        ];
    }
}
