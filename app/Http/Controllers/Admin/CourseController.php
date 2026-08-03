<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanceCourse;
use App\Models\CourseLesson;
use App\Services\LessonSchedule;
use App\Models\SchoolLocation;
use App\Models\DanceDiscipline;
use App\Models\DanceLevel;
use App\Models\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseController extends Controller
{
    public function index(): Response
    {
        $school = request()->user()->school;
        $enrollments = $school->enrollments()->with(['course:id,title,day,time', 'user:id,name,email', 'invoice'])->latest()->get();
        $students = $enrollments->groupBy('email')->map(function ($studentEnrollments) {
            $latest = $studentEnrollments->first();
            $accepted = $studentEnrollments->whereNotIn('status', ['waitlist', 'expired']);

            return [
                'id' => $latest->user_id ?: 'email-'.md5($latest->email),
                'name' => trim($latest->first_name.' '.$latest->last_name),
                'first_name' => $latest->first_name,
                'last_name' => $latest->last_name,
                'email' => $latest->email,
                'phone' => $studentEnrollments->firstWhere('phone', '!=', null)?->phone,
                'has_account' => (bool) $latest->user_id,
                'enrollments_count' => $studentEnrollments->count(),
                'accepted_count' => $accepted->count(),
                'waitlist_count' => $studentEnrollments->where('status', 'waitlist')->count(),
                'total_amount' => round((float) $accepted->sum('amount'), 2),
                'courses' => $studentEnrollments->pluck('course.title')->filter()->unique()->values(),
                'last_enrollment_at' => $latest->created_at,
            ];
        })->values();

        return Inertia::render('Admin/Dashboard', [
            'courses' => $school->courses()->with(['lessons', 'season', 'pricingCategories'])->latest()->get(),
            'seasons' => $school->seasons()->withCount('courses')->orderByDesc('start_date')->get(),
            'enrollments' => $enrollments,
            'students' => $students,
            'discountRules' => $school->discountRules()->orderBy('course_count')->get(),
            'paymentPlans' => $school->paymentPlans()->latest()->get(),
            'trialRequests' => $school->trialRequests()->with('course:id,title,day,time,location')->latest()->get(),
            'invoices' => $school->invoices()->with(['payments', 'enrollment:id,first_name,last_name,email,dance_course_id', 'enrollment.course:id,title'])->latest()->get(),
            'billingSettings' => [
                ...$school->only('billing_name', 'billing_street', 'billing_house_number', 'billing_postal_code', 'billing_city', 'billing_country', 'billing_iban', 'invoice_prefix', 'invoice_due_days'),
                'complete' => $school->hasCompleteBillingSettings(),
            ],
            'administrators' => $school->users()->where('is_admin', true)->orderBy('name')->get(['id', 'name', 'email']),
            'paymentReminderSettings' => ['payment_reminders_enabled' => $school->payment_reminders_enabled, 'payment_reminder_steps' => $school->paymentReminderSteps()],
            'termsAndConditions' => $school->terms_and_conditions,
            'registrationFeeSettings' => $school->only('registration_fee_enabled', 'registration_fee_name', 'registration_fee_amount'),
            'contactButtonSettings' => $school->only('contact_button_label', 'contact_button_url'),
            'references' => [
                'locations' => SchoolLocation::where('school_id', $school->id)->orderBy('name')->get(),
                'disciplines' => DanceDiscipline::where('school_id', $school->id)->orderBy('name')->get(),
                'levels' => DanceLevel::where('school_id', $school->id)->orderBy('name')->get(),
                'categories' => \App\Models\PricingCategory::where('school_id', $school->id)->orderBy('name')->get(),
            ],
            'stats' => [
                'courses' => $school->courses()->count(),
                'active' => $school->courses()->where('is_active', true)->count(),
                'places' => $school->courses()->sum('places'),
                'enrollments' => $school->enrollments()->count(),
                'students' => $students->count(),
                'trials' => $school->trialRequests()->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCourse($request);
        $data = $this->storeCourseImage($request, $data);
        $categoryPrices = $data['category_prices'] ?? [];
        unset($data['category_prices']);

        DB::transaction(function () use ($request, $data, $categoryPrices) {
            $course = $request->user()->school->courses()->create([
                ...$data,
                'places' => $data['capacity'],
            ]);

            LessonSchedule::generate($course);
            $this->syncCategoryPrices($course, $categoryPrices);
        });

        return back()->with('success', 'Le cours a été créé et publié sur le catalogue.');
    }

    public function show(Request $request, DanceCourse $course): Response
    {
        abort_unless($course->school_id === $request->user()->school_id, 404);

        $course->load([
            'season',
            'lessons',
            'enrollments' => fn ($query) => $query->with(['user:id,name,email', 'invoices'])->latest(),
        ]);

        $trials = $request->user()->school->trialRequests()
            ->where('dance_course_id', $course->id)
            ->latest()
            ->get();
        $confirmed = $course->enrollments->whereNotIn('status', ['waitlist', 'invited', 'expired']);

        return Inertia::render('Admin/Courses/Show', [
            'course' => $course,
            'trialRequests' => $trials,
            'stats' => [
                'confirmed' => $confirmed->count(),
                'waitlist' => $course->enrollments->whereIn('status', ['waitlist', 'invited'])->count(),
                'trials' => $trials->count(),
                'revenue' => round((float) $confirmed->sum('amount'), 2),
            ],
        ]);
    }

    public function exportStudents(Request $request, DanceCourse $course): StreamedResponse
    {
        abort_unless($course->school_id === $request->user()->school_id, 404);

        $enrollments = $course->enrollments()
            ->whereNotIn('status', ['waitlist', 'invited', 'expired'])
            ->oldest('last_name')
            ->oldest('first_name')
            ->get();
        $filename = 'eleves-'.Str::slug($course->title).'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($course, $enrollments) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Cours', 'Prénom', 'Nom', 'E-mail', 'Téléphone', 'Mineur', 'Représentant légal', 'Rôle', 'Date de début', 'Leçons', 'Montant (CHF)', 'Statut', 'Date d’inscription'], ';', '"', '', "\r\n");

            foreach ($enrollments as $enrollment) {
                fputcsv($output, [
                    $this->excelSafe($course->title),
                    $this->excelSafe($enrollment->first_name),
                    $this->excelSafe($enrollment->last_name),
                    $this->excelSafe($enrollment->email),
                    $this->excelSafe($enrollment->phone),
                    $enrollment->is_minor ? 'Oui' : 'Non',
                    $enrollment->is_minor
                        ? $this->excelSafe(trim($enrollment->legal_guardian_first_name.' '.$enrollment->legal_guardian_last_name))
                        : '',
                    $enrollment->dance_role ?: '—',
                    $enrollment->start_date?->format('d.m.Y'),
                    $enrollment->lessons_count,
                    number_format((float) $enrollment->amount, 2, ',', ''),
                    'Inscrit',
                    $enrollment->created_at?->format('d.m.Y H:i'),
                ], ';', '"', '', "\r\n");
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function excelSafe(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/u', $value) ? "'".$value : $value;
    }

    public function update(Request $request, DanceCourse $course): RedirectResponse
    {
        abort_unless($course->school_id === $request->user()->school_id, 404);
        $data = $this->validateCourse($request);
        $previousImage = $course->image;
        $data = $this->storeCourseImage($request, $data);
        $categoryPrices = $data['category_prices'] ?? [];
        unset($data['category_prices']);
        $scheduleChanged = $course->is_workshop !== $data['is_workshop']
            || $course->day !== $data['day']
            || $course->start_date->toDateString() !== $data['start_date']
            || $course->end_date->toDateString() !== $data['end_date'];
        $enrolled = max(0, $course->capacity - $course->places);

        DB::transaction(function () use ($course, $data, $categoryPrices, $scheduleChanged, $enrolled) {
            $course->update([...$data, 'places' => max(0, $data['capacity'] - $enrolled)]);
            $this->syncCategoryPrices($course, $categoryPrices);

            if ($scheduleChanged) {
                $course->lessons()->delete();
                LessonSchedule::generate($course->fresh());
            }
        });
        if ($data['image'] !== $previousImage) $this->deleteUploadedImage($previousImage);

        return back()->with('success', 'Le cours a été mis à jour.');
    }

    public function destroy(Request $request, DanceCourse $course): RedirectResponse
    {
        abort_unless($course->school_id === $request->user()->school_id, 404);
        $image = $course->image;
        $course->delete();
        $this->deleteUploadedImage($image);

        return back()->with('success', 'Le cours et ses données associées ont été supprimés.');
    }

    private function validateCourse(Request $request): array
    {
        $schoolId = $request->user()->school_id;
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'is_workshop' => ['sometimes', 'boolean'],
            'season_id' => ['required', Rule::exists('seasons', 'id')->where('school_id', $schoolId)],
            'dance_discipline_id' => ['required', Rule::exists('dance_disciplines', 'id')->where('school_id', $schoolId)],
            'dance_level_id' => ['required', Rule::exists('dance_levels', 'id')->where('school_id', $schoolId)],
            'school_location_id' => ['required', Rule::exists('school_locations', 'id')->where('school_id', $schoolId)],
            'day' => ['required', 'in:Lundi,Mardi,Mercredi,Jeudi,Vendredi,Samedi,Dimanche'],
            'time' => ['required', 'string', 'max:40'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'teacher' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999'],
            'session_price' => ['required', 'numeric', 'min:0', 'max:99999'],
            'category_prices' => ['nullable', 'array'],
            'category_prices.*' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'trial_enabled' => ['sometimes', 'boolean'],
            'trial_is_free' => ['sometimes', 'boolean'],
            'trial_price' => [
                Rule::excludeIf(($request->has('trial_enabled') && ! $request->boolean('trial_enabled')) || $request->boolean('trial_is_free')),
                Rule::requiredIf(! $request->boolean('trial_is_free')),
                'nullable', 'numeric', 'min:0.01', 'max:9999',
            ],
            'trial_payment_on_site' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'string', 'max:2000', function (string $attribute, mixed $value, \Closure $fail) {
                if ($value && ! Str::startsWith($value, ['http://', 'https://', '/storage/course-images/'])) $fail('Utilisez une URL valide ou téléversez une image.');
            }],
            'image_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_active' => ['required', 'boolean'],
            'couple_mode' => ['required', 'boolean'],
            'max_role_gap' => ['nullable', 'required_if:couple_mode,true', 'integer', 'min:0', 'max:100'],
            'balance_after_count' => ['required', 'integer', 'min:0', 'max:500'],
            'waitlist_invitation_hours' => ['nullable', 'numeric', 'min:0.01', 'max:720'],
        ]);

        $data['waitlist_invitation_hours'] ??= 72;
        $data['is_workshop'] = (bool) ($data['is_workshop'] ?? false);
        if ($data['is_workshop']) {
            $data['end_date'] = $data['start_date'];
            $data['day'] = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'][\Carbon\CarbonImmutable::parse($data['start_date'])->isoWeekday() - 1];
            $data['price'] = $data['session_price'];
            $data['category_prices'] = [];
            $data['trial_enabled'] = false;
        }
        $data['trial_enabled'] ??= true;
        $data['trial_is_free'] ??= true;
        $data['trial_price'] = ! $data['trial_enabled'] || $data['trial_is_free'] ? 0 : ($data['trial_price'] ?? 0);
        $data['trial_payment_on_site'] = $data['trial_enabled'] && ! $data['trial_is_free'] && ($data['trial_payment_on_site'] ?? false);
        if (! $request->hasFile('image_upload') && blank($data['image'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['image' => 'Ajoutez une URL ou téléversez une image.']);
        }

        $data['style'] = DanceDiscipline::find($data['dance_discipline_id'])->name;
        $data['level'] = DanceLevel::find($data['dance_level_id'])->name;
        $data['location'] = SchoolLocation::find($data['school_location_id'])->name;
        $season = Season::findOrFail($data['season_id']);
        if ($data['start_date'] < $season->start_date->toDateString() || $data['end_date'] > $season->end_date->toDateString()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'season_id' => 'Les dates du cours doivent être comprises dans les dates de la saison sélectionnée.',
            ]);
        }

        return $data;
    }

    private function syncCategoryPrices(DanceCourse $course, array $prices): void
    {
        $validCategoryIds = $course->school->pricingCategories()->pluck('id')->map(fn ($id) => (string) $id);
        $sync = collect($prices)
            ->filter(fn ($price, $categoryId) => $price !== null && $price !== '' && $validCategoryIds->contains((string) $categoryId))
            ->mapWithKeys(fn ($price, $categoryId) => [$categoryId => ['price' => $price]])
            ->all();

        $course->pricingCategories()->sync($sync);
    }

    private function storeCourseImage(Request $request, array $data): array
    {
        unset($data['image_upload']);
        if ($request->hasFile('image_upload')) {
            $path = $request->file('image_upload')->store('course-images', 'public');
            $data['image'] = Storage::disk('public')->url($path);
        }
        return $data;
    }

    private function deleteUploadedImage(?string $image): void
    {
        if (! $image) return;

        if (Str::startsWith($image, '/storage/course-images/')) {
            Storage::disk('public')->delete(Str::after($image, '/storage/'));
            return;
        }

        $path = ltrim((string) parse_url($image, PHP_URL_PATH), '/');
        if (Str::startsWith($path, 'course-images/')) Storage::disk('public')->delete($path);
    }

    public function destroyLesson(Request $request, DanceCourse $course, CourseLesson $lesson): RedirectResponse
    {
        abort_unless($course->school_id === $request->user()->school_id, 404);
        abort_unless($lesson->dance_course_id === $course->id, 404);

        $lesson->delete();

        return back()->with('success', 'La leçon a été retirée du calendrier et du calcul tarifaire.');
    }

    public function updateLesson(Request $request, DanceCourse $course, CourseLesson $lesson): RedirectResponse
    {
        abort_unless($course->school_id === $request->user()->school_id && $lesson->dance_course_id === $course->id, 404);
        $data = $request->validate(['lesson_date' => ['required', 'date', 'after_or_equal:'.$course->start_date->toDateString(), 'before_or_equal:'.$course->end_date->toDateString()]]);
        $lesson->update($data);
        return back()->with('success', 'La date de la leçon a été modifiée.');
    }
}
