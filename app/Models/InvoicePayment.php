<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['invoice_id', 'amount', 'paid_on', 'method', 'note', 'recorded_by'])]
class InvoicePayment extends Model
{
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    protected function casts(): array { return ['amount' => 'decimal:2', 'paid_on' => 'date:Y-m-d']; }
}
