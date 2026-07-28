<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['school_id', 'dance_course_id', 'first_name', 'last_name', 'email', 'phone', 'dance_role', 'preferred_date', 'trial_is_free', 'trial_price', 'trial_payment_on_site', 'message', 'status'])]
class TrialRequest extends Model
{
    public function course(): BelongsTo { return $this->belongsTo(DanceCourse::class, 'dance_course_id'); }
    protected function casts(): array { return ['preferred_date' => 'date:Y-m-d', 'trial_is_free' => 'boolean', 'trial_price' => 'decimal:2', 'trial_payment_on_site' => 'boolean']; }
}
