<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['school_id', 'user_id', 'dance_course_id', 'pricing_category_id', 'payment_plan_id', 'payment_plan_name', 'installment_count', 'pricing_category_name', 'first_name', 'last_name', 'email', 'phone', 'dance_role', 'start_date', 'lessons_count', 'base_amount', 'category_discount_amount', 'discount_amount', 'discount_percentage', 'payment_adjustment_amount', 'amount', 'installment_amount', 'status', 'waitlist_token_hash', 'waitlist_invited_at', 'waitlist_invitation_expires_at', 'terms_accepted_at', 'terms_content_hash', 'registration_fee_name', 'registration_fee_amount', 'comment'])]
class Enrollment extends Model
{
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(DanceCourse::class, 'dance_course_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->oldestOfMany('installment_number');
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Invoice::class)->orderBy('installment_number'); }

    public function paymentPlan(): BelongsTo { return $this->belongsTo(PaymentPlan::class); }

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'category_discount_amount' => 'decimal:2',
            'payment_adjustment_amount' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'waitlist_invited_at' => 'datetime',
            'waitlist_invitation_expires_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'registration_fee_amount' => 'decimal:2',
        ];
    }
}
