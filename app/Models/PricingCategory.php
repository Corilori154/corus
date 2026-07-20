<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
#[Fillable(['school_id', 'name', 'discount_percentage'])]
class PricingCategory extends Model
{
    protected function casts(): array { return ['discount_percentage' => 'decimal:2']; }
}
