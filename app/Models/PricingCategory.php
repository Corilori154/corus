<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
#[Fillable(['school_id', 'name'])]
class PricingCategory extends Model
{
    public function courses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(DanceCourse::class)->withPivot('price')->withTimestamps();
    }
}
