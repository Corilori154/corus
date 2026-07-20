<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'enrollment_id', 'installment_number', 'installment_count', 'number', 'status', 'currency', 'amount', 'issued_at', 'due_at', 'paid_at'])]
class Invoice extends Model
{
    protected $appends = ['paid_amount', 'balance', 'payment_status'];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
    public function payments(): HasMany { return $this->hasMany(InvoicePayment::class)->orderByDesc('paid_on')->orderByDesc('id'); }

    public function getPaidAmountAttribute(): float
    {
        return round((float) ($this->relationLoaded('payments') ? $this->payments->sum('amount') : $this->payments()->sum('amount')), 2);
    }

    public function getBalanceAttribute(): float { return max(0, round((float) $this->amount - $this->paid_amount, 2)); }
    public function getPaymentStatusAttribute(): string
    {
        if ($this->balance <= 0) return 'paid';
        return $this->paid_amount > 0 ? 'partial' : 'open';
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'issued_at' => 'date', 'due_at' => 'date', 'paid_at' => 'datetime'];
    }
}
