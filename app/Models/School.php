<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'email', 'phone', 'city', 'accent', 'is_active', 'billing_name', 'billing_street', 'billing_house_number', 'billing_postal_code', 'billing_city', 'billing_country', 'billing_iban', 'invoice_prefix', 'invoice_due_days', 'payment_reminders_enabled', 'payment_reminder_delay_days', 'payment_reminder_interval_days', 'payment_reminder_max_count', 'payment_reminder_fee', 'terms_and_conditions', 'registration_fee_enabled', 'registration_fee_name', 'registration_fee_amount'])]
class School extends Model
{
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(DanceCourse::class);
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function discountRules(): HasMany
    {
        return $this->hasMany(DiscountRule::class);
    }

    public function paymentPlans(): HasMany
    {
        return $this->hasMany(PaymentPlan::class);
    }

    public function pricingCategories(): HasMany
    {
        return $this->hasMany(PricingCategory::class);
    }

    public function trialRequests(): HasMany
    {
        return $this->hasMany(TrialRequest::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function hasCompleteBillingSettings(): bool
    {
        return collect(['billing_name', 'billing_street', 'billing_house_number', 'billing_postal_code', 'billing_city', 'billing_country', 'billing_iban'])
            ->every(fn ($field) => filled($this->{$field}));
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'payment_reminders_enabled' => 'boolean', 'payment_reminder_fee' => 'decimal:2', 'registration_fee_enabled' => 'boolean', 'registration_fee_amount' => 'decimal:2'];
    }
}
