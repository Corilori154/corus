<?php

namespace App\Http\Controllers;

use App\Models\DanceCourse;
use App\Models\School;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\PricingCategory;
use App\Models\PaymentPlan;
use App\Models\TrialRequest;
use App\Notifications\StudentAccountCreated;
use App\Notifications\InvoiceCreated;
use App\Services\InvoiceService;
use App\Services\WaitlistService;
use App\Services\RegistrationFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CourseCatalogController extends Controller
{
    public function index(School $school): Response
    {
        return Inertia::render('Courses/Index', [
            'school' => $school->only('name', 'slug', 'city', 'email', 'phone', 'accent', 'terms_and_conditions', 'contact_button_label', 'contact_button_url'),
            'courses' => $school->courses()
                ->with(['lessons:id,dance_course_id,lesson_date', 'pricingCategories'])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('day')
                ->get(),
            'paymentPlans' => PaymentPlan::where('school_id', $school->id)->where('is_active', true)->orderBy('installment_count')->get(),
        ]);
    }

    public function show(School $school, DanceCourse $course): Response
    {
        abort_unless($course->school_id === $school->id && $course->is_active, 404);

        return Inertia::render('Courses/Show', [
            'school' => $school->only('name', 'slug', 'city', 'email', 'phone', 'accent', 'terms_and_conditions', 'contact_button_label', 'contact_button_url'),
            'course' => $course->load(['lessons:id,dance_course_id,lesson_date', 'pricingCategories']),
            'pricingCategories' => $course->pricingCategories,
            'paymentPlans' => PaymentPlan::where('school_id', $school->id)->where('is_active', true)->orderBy('installment_count')->get(),
        ]);
    }

    public function store(Request $request, School $school): \Symfony\Component\HttpFoundation\Response
    {
        $data = $request->validate([
            'course_id' => [
                'required',
                'integer',
                Rule::exists('dance_courses', 'id')->where(fn ($query) => $query
                    ->where('school_id', $school->id)
                    ->where('is_active', true)),
            ],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[0-9+() .\-]+$/'],
            'is_minor' => ['sometimes', 'boolean'],
            'legal_guardian_first_name' => ['nullable', 'required_if:is_minor,true', 'string', 'max:80'],
            'legal_guardian_last_name' => ['nullable', 'required_if:is_minor,true', 'string', 'max:80'],
            'start_date' => ['required', 'date'],
            'dance_role' => ['nullable', 'in:lead,follow'],
            'pricing_category_id' => ['nullable', Rule::exists('pricing_categories', 'id')->where('school_id', $school->id)],
            'payment_plan_id' => ['nullable', Rule::exists('payment_plans', 'id')->where(fn ($query) => $query->where('school_id', $school->id)->where('is_active', true))],
            'terms_accepted' => ['accepted'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'terms_accepted.accepted' => 'Vous devez accepter les conditions générales pour vous inscrire.',
        ]);
        $data['is_minor'] ??= false;

        $course = $school->courses()->with('lessons')->where('is_active', true)->findOrFail($data['course_id']);

        if ($course->couple_mode && empty($data['dance_role'])) {
            throw ValidationException::withMessages([
                'dance_role' => 'Choisissez votre rôle Lead ou Follow pour ce cours.',
            ]);
        }

        if ($data['start_date'] < $course->start_date->toDateString() || $data['start_date'] > $course->end_date->toDateString()) {
            throw ValidationException::withMessages([
                'start_date' => 'La date de début doit être comprise dans la période de la session.',
            ]);
        }

        $totalLessons = $course->lessons->count();
        $remainingLessons = $course->lessons
            ->filter(fn ($lesson) => $lesson->lesson_date->toDateString() >= $data['start_date'])
            ->count();
        $listAmount = $totalLessons > 0
            ? round((float) $course->session_price * $remainingLessons / $totalLessons, 2)
            : 0;
        $category = ! empty($data['pricing_category_id']) ? $course->pricingCategories()->find($data['pricing_category_id']) : null;
        if (! empty($data['pricing_category_id']) && ! $category) {
            throw ValidationException::withMessages(['pricing_category_id' => 'Cette catégorie tarifaire n’est pas disponible pour ce cours.']);
        }
        $paymentPlan = ! empty($data['payment_plan_id']) ? PaymentPlan::find($data['payment_plan_id']) : null;
        $baseAmount = $category && $totalLessons > 0
            ? round((float) $category->pivot->price * $remainingLessons / $totalLessons, 2)
            : $listAmount;
        $categoryDiscount = 0;

        $temporaryPassword = Str::password(14);

        $result = DB::transaction(function () use ($school, $course, $data, $remainingLessons, $baseAmount, $category, $categoryDiscount, $paymentPlan, $temporaryPassword) {
            $lockedSchool = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
            if (Enrollment::where('dance_course_id', $course->id)->where('email', $data['email'])->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Une inscription existe déjà avec cette adresse pour ce cours.',
                ]);
            }

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'school_id' => $school->id,
                    'name' => trim($data['first_name'].' '.$data['last_name']),
                    'password' => $temporaryPassword,
                    'is_admin' => false,
                ],
            );

            $lockedCourse = DanceCourse::whereKey($course->id)->lockForUpdate()->firstOrFail();
            $status = $lockedCourse->places > 0 ? 'accepted' : 'waitlist';
            if ($status !== 'waitlist' && $lockedCourse->couple_mode) {
                $balancedEnrollments = $lockedCourse->enrollments()
                    ->whereNotIn('status', ['waitlist', 'expired'])
                    ->lockForUpdate()
                    ->get();
                $leads = $balancedEnrollments->where('dance_role', 'lead')->count();
                $follows = $balancedEnrollments->where('dance_role', 'follow')->count();
                $data['dance_role'] === 'lead' ? $leads++ : $follows++;

                $thresholdReached = $balancedEnrollments->count() >= $lockedCourse->balance_after_count;

                if ($thresholdReached && abs($leads - $follows) > $lockedCourse->max_role_gap) {
                    $status = 'waitlist';
                }
            }

            $pricing = $this->multiCoursePricing($school, $course, $data['email'], $baseAmount, true);
            $paymentPricing = $this->paymentPlanPricing($pricing['amount'], $paymentPlan);
            $registrationFee = $status === 'accepted' ? app(RegistrationFeeService::class)->amountFor($lockedSchool, $lockedCourse, $data['email']) : 0;
            $finalAmount = round($paymentPricing['amount'] + $registrationFee, 2);
            $installmentAmount = round($finalAmount / $paymentPricing['installment_count'], 2);

            $enrollment = $school->enrollments()->create([
                'user_id' => $user->id,
                'dance_course_id' => $course->id,
                'pricing_category_id' => $category?->id,
                'pricing_category_name' => $category?->name,
                'payment_plan_id' => $paymentPlan?->id,
                'payment_plan_name' => $paymentPlan?->name,
                'installment_count' => $paymentPricing['installment_count'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'is_minor' => $data['is_minor'],
                'legal_guardian_first_name' => $data['is_minor'] ? $data['legal_guardian_first_name'] : null,
                'legal_guardian_last_name' => $data['is_minor'] ? $data['legal_guardian_last_name'] : null,
                'comment' => $data['comment'] ?? null,
                'dance_role' => $data['dance_role'] ?? null,
                'start_date' => $data['start_date'],
                'lessons_count' => $remainingLessons,
                'base_amount' => $baseAmount,
                'category_discount_amount' => $categoryDiscount,
                'discount_amount' => $pricing['discount_amount'],
                'discount_percentage' => $pricing['discount_percentage'],
                'payment_adjustment_amount' => $paymentPricing['payment_adjustment_amount'],
                'amount' => $finalAmount,
                'installment_amount' => $installmentAmount,
                'registration_fee_name' => $registrationFee > 0 ? $lockedSchool->registration_fee_name : null,
                'registration_fee_amount' => $registrationFee,
                'status' => $status,
                'waitlist_position' => $status === 'waitlist'
                    ? $lockedCourse->enrollments()->whereIn('status', ['waitlist', 'invited', 'expired'])->max('waitlist_position') + 1
                    : null,
                'terms_accepted_at' => now(),
                'terms_content_hash' => hash('sha256', (string) $school->terms_and_conditions),
            ]);

            if ($status !== 'waitlist') {
                $lockedCourse->decrement('places');
            }

            $invoice = $status !== 'waitlist' ? app(InvoiceService::class)->createFor($enrollment) : null;

            return [
                'user' => $user,
                'created' => $user->wasRecentlyCreated,
                ...$pricing,
                ...$paymentPricing,
                'amount' => $finalAmount,
                'installment_amount' => $installmentAmount,
                'registration_fee_amount' => $registrationFee,
                'status' => $status,
                'invoice' => $invoice,
                'enrollment' => $enrollment,
            ];
        });

        if ($result['created']) {
            $result['user']->notify(new StudentAccountCreated($school, $temporaryPassword));
        }
        if ($result['invoice']) {
            $result['user']->notify(new InvoiceCreated($result['invoice']));
        }
        if ($result['status'] !== 'waitlist') {
            app(WaitlistService::class)->inviteNext($course->fresh());
        }

        $formattedAmount = number_format($result['amount'], 2, ',', ' ');
        $accountMessage = $result['created'] ? ' Votre compte client a été créé et vos identifiants ont été envoyés par e-mail.' : '';
        $discountLabel = $result['discount_type'] === 'percentage'
            ? "{$result['discount_percentage']} %"
            : number_format($result['discount_value'], 2, ',', ' ').' CHF fixe';
        $discountMessage = $result['discount_amount'] > 0
            ? ' Rabais multi-cours de '.number_format($result['discount_amount'], 2, ',', ' ')." CHF appliqué ({$discountLabel})."
            : '';
        $paymentMessage = $result['installment_count'] > 1
            ? ' Plan de paiement : '.$result['installment_count'].' échéances d’environ '.number_format($result['installment_amount'], 2, ',', ' ').' CHF.'
            : '';

        $statusMessage = $result['status'] === 'waitlist'
            ? ' L’équilibre Lead/Follow serait dépassé : vous avez été placé·e sur liste d’attente et ne serez pas facturé·e avant confirmation.'
            : " Montant à payer : {$formattedAmount} CHF pour {$remainingLessons} leçons restantes. Facture {$result['invoice']->number} générée.{$discountMessage}{$paymentMessage}";

        $message = "Merci {$data['first_name']} ! Votre demande pour {$course->title} a été envoyée.{$statusMessage}{$accountMessage}";

        if ($result['status'] === 'waitlist') {
            return back()->with('waitlist', $message);
        }

        $invoiceUrl = URL::temporarySignedRoute(
            'invoices.public',
            now()->addMonths(6),
            ['invoice' => $result['invoice']],
        );

        $request->session()->flash('success', $message);

        return Inertia::location($invoiceUrl);
    }

    public function quote(Request $request, School $school): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'course_id' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'pricing_category_id' => ['nullable', Rule::exists('pricing_categories', 'id')->where('school_id', $school->id)],
            'payment_plan_id' => ['nullable', Rule::exists('payment_plans', 'id')->where(fn ($query) => $query->where('school_id', $school->id)->where('is_active', true))],
        ]);
        $course = $school->courses()->with('lessons')->where('is_active', true)->findOrFail($data['course_id']);

        if ($data['start_date'] < $course->start_date->toDateString() || $data['start_date'] > $course->end_date->toDateString()) {
            throw ValidationException::withMessages(['start_date' => 'Date hors de la session.']);
        }

        $totalLessons = $course->lessons->count();
        $remainingLessons = $course->lessons
            ->filter(fn ($lesson) => $lesson->lesson_date->toDateString() >= $data['start_date'])
            ->count();
        $listAmount = $totalLessons > 0
            ? round((float) $course->session_price * $remainingLessons / $totalLessons, 2)
            : 0;
        $category = ! empty($data['pricing_category_id']) ? $course->pricingCategories()->find($data['pricing_category_id']) : null;
        if (! empty($data['pricing_category_id']) && ! $category) {
            throw ValidationException::withMessages(['pricing_category_id' => 'Cette catégorie tarifaire n’est pas disponible pour ce cours.']);
        }
        $paymentPlan = ! empty($data['payment_plan_id']) ? PaymentPlan::find($data['payment_plan_id']) : null;
        $baseAmount = $category && $totalLessons > 0
            ? round((float) $category->pivot->price * $remainingLessons / $totalLessons, 2)
            : $listAmount;
        $categoryDiscount = 0;

        $multiCoursePricing = $this->multiCoursePricing($school, $course, $data['email'], $baseAmount);
        $paymentPricing = $this->paymentPlanPricing($multiCoursePricing['amount'], $paymentPlan);
        $registrationFee = app(RegistrationFeeService::class)->amountFor($school, $course, $data['email']);
        $finalAmount = round($paymentPricing['amount'] + $registrationFee, 2);

        return response()->json([
            ...$multiCoursePricing,
            ...$paymentPricing,
            'amount' => $finalAmount,
            'installment_amount' => round($finalAmount / $paymentPricing['installment_count'], 2),
            'registration_fee_amount' => $registrationFee,
            'registration_fee_name' => $registrationFee > 0 ? $school->registration_fee_name : null,
            'base_amount' => $baseAmount,
            'list_amount' => $listAmount,
            'category_discount_amount' => $categoryDiscount,
            'category_name' => $category?->name,
            'payment_plan_name' => $paymentPlan?->name,
            'remaining_lessons' => $remainingLessons,
            'total_lessons' => $totalLessons,
        ]);
    }

    public function storeTrial(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => ['required', Rule::exists('dance_courses', 'id')->where(fn ($query) => $query->where('school_id', $school->id)->where('is_active', true))],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[0-9+() .\-]+$/'],
            'dance_role' => ['nullable', 'in:lead,follow'],
            'preferred_date' => ['required', 'date'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);
        $course = $school->courses()->with('lessons')->findOrFail($data['course_id']);
        if (! $course->trial_enabled) {
            throw ValidationException::withMessages(['course_id' => 'Les cours d’essai sont désactivés pour ce cours.']);
        }
        if ($course->couple_mode && empty($data['dance_role'])) {
            throw ValidationException::withMessages(['dance_role' => 'Choisissez votre rôle Lead ou Follow.']);
        }
        if (! $course->lessons->contains(fn ($lesson) => $lesson->lesson_date->toDateString() === $data['preferred_date'])) {
            throw ValidationException::withMessages(['preferred_date' => 'Choisissez une date de leçon disponible.']);
        }

        $school->trialRequests()->create([
            ...$data,
            'dance_course_id' => $data['course_id'],
            'trial_is_free' => $course->trial_is_free,
            'trial_price' => $course->trial_is_free ? 0 : $course->trial_price,
            'trial_payment_on_site' => ! $course->trial_is_free && $course->trial_payment_on_site,
        ]);

        return back()->with('success', "Votre demande de cours d’essai pour {$course->title} a bien été envoyée. L’école vous contactera pour la confirmer.");
    }

    private function multiCoursePricing(School $school, DanceCourse $course, string $email, float $baseAmount, bool $lock = false): array
    {
        $query = $school->enrollments()
            ->where('email', $email)
            ->whereNotIn('status', ['waitlist', 'expired'])
            ->whereHas('course', fn ($courseQuery) => $courseQuery->where('season_id', $course->season_id));
        $previousEnrollments = ($lock ? $query->lockForUpdate() : $query)->get();
        $newCourseCount = $previousEnrollments->count() + 1;
        $rule = $school->discountRules()
            ->where('course_count', '<=', $newCourseCount)
            ->orderByDesc('course_count')
            ->first();
        $grossTotal = (float) $previousEnrollments->sum('base_amount') + $baseAmount;
        $discountType = $rule?->discount_type ?? 'percentage';
        $discountPercentage = $rule && $discountType === 'percentage' ? (float) $rule->percentage : 0;
        $discountValue = $rule
            ? ($discountType === 'fixed' ? (float) $rule->fixed_amount : (float) $rule->percentage)
            : 0;
        $targetDiscount = $discountType === 'fixed'
            ? round($discountValue, 2)
            : round($grossTotal * $discountPercentage / 100, 2);
        $alreadyGranted = (float) $previousEnrollments->sum('discount_amount');
        $discountAmount = min($baseAmount, max(0, round($targetDiscount - $alreadyGranted, 2)));

        return [
            'amount' => round($baseAmount - $discountAmount, 2),
            'discount_amount' => $discountAmount,
            'discount_percentage' => $discountPercentage,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'course_count' => $newCourseCount,
        ];
    }

    private function paymentPlanPricing(float $subtotal, ?PaymentPlan $plan): array
    {
        if (! $plan) {
            return ['amount' => $subtotal, 'payment_adjustment_amount' => 0, 'installment_count' => 1, 'installment_amount' => $subtotal];
        }

        $adjustment = $plan->adjustment_mode === 'percentage'
            ? round($subtotal * (float) $plan->adjustment_value / 100, 2)
            : (float) $plan->adjustment_value;
        if ($plan->adjustment_direction === 'discount') $adjustment *= -1;
        $amount = max(0, round($subtotal + $adjustment, 2));

        return [
            'amount' => $amount,
            'payment_adjustment_amount' => $adjustment,
            'installment_count' => $plan->installment_count,
            'installment_amount' => round($amount / $plan->installment_count, 2),
        ];
    }
}
