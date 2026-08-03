<?php
namespace App\Services;
use App\Models\DanceCourse;
use App\Models\School;
class RegistrationFeeService {
    public function amountFor(School $school, DanceCourse $course, string $email, ?int $exceptEnrollmentId = null): float {
        if ($course->is_workshop || ! $school->registration_fee_enabled || (float) $school->registration_fee_amount <= 0 || ! $course->season_id) return 0;
        $alreadyCharged = $school->enrollments()->where('email', $email)->where('registration_fee_amount', '>', 0)
            ->when($exceptEnrollmentId, fn ($query) => $query->where('id', '!=', $exceptEnrollmentId))
            ->whereHas('course', fn ($query) => $query->where('season_id', $course->season_id))->exists();
        return $alreadyCharged ? 0 : (float) $school->registration_fee_amount;
    }
}
