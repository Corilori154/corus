<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
#[Fillable(['school_id', 'name', 'installment_count', 'schedule_mode', 'adjustment_direction', 'adjustment_mode', 'adjustment_value', 'is_active'])]
class PaymentPlan extends Model
{
    protected function casts(): array
    {
        return ['adjustment_value' => 'decimal:2', 'is_active' => 'boolean'];
    }
}
