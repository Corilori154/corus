<?php

use App\Http\Controllers\CourseCatalogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DiscountRuleController;
use App\Http\Controllers\Admin\CatalogReferenceController;
use App\Http\Controllers\Admin\PaymentPlanController;
use App\Http\Controllers\Admin\BillingSettingsController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SeasonController;
use App\Http\Controllers\Admin\AdministratorController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\PaymentReminderSettingsController;
use App\Http\Controllers\Admin\TermsSettingsController;
use App\Http\Controllers\Admin\RegistrationFeeSettingsController;
use App\Http\Controllers\Admin\ContactButtonSettingsController;
use App\Http\Controllers\Admin\TrialRequestController;
use App\Http\Controllers\SchoolRegistrationController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\WaitlistController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Student\AccountController as StudentAccountController;
use App\Http\Controllers\Student\AuthController as StudentAuthController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Inertia::render('Saas/Home'))->name('home');
Route::get('/creer-mon-ecole', [SchoolRegistrationController::class, 'create'])->name('schools.register');
Route::post('/creer-mon-ecole', [SchoolRegistrationController::class, 'store'])->name('schools.store');
Route::get('/facture/{invoice}', [PublicInvoiceController::class, 'show'])->middleware('signed')->name('invoices.public');
Route::get('/liste-attente/{enrollment}/confirmer', [WaitlistController::class, 'accept'])->middleware('signed')->name('waitlist.accept');

Route::middleware('guest')->group(function () {
    Route::get('/admin/connexion', [AuthController::class, 'create'])->name('login');
    Route::post('/admin/connexion', [AuthController::class, 'store'])->name('admin.login.store');
    Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::get('/ecole/{school}', [CourseCatalogController::class, 'index'])->name('courses.index');
Route::get('/ecole/{school}/connexion', [StudentAuthController::class, 'create'])->name('students.login');
Route::post('/ecole/{school}/connexion', [StudentAuthController::class, 'store'])->middleware('guest')->name('students.login.store');
Route::get('/ecole/{school}/cours/{course}', [CourseCatalogController::class, 'show'])->name('courses.show');
Route::post('/ecole/{school}/inscriptions', [CourseCatalogController::class, 'store'])->name('courses.enroll');
Route::post('/ecole/{school}/devis', [CourseCatalogController::class, 'quote'])->middleware('throttle:30,1')->name('courses.quote');
Route::post('/ecole/{school}/cours-essai', [CourseCatalogController::class, 'storeTrial'])->middleware('throttle:20,1')->name('courses.trial');

Route::prefix('ecole/{school}/mon-espace')->name('student.')->middleware(['auth', 'student'])->group(function () {
    Route::get('/', StudentDashboardController::class)->name('dashboard');
    Route::put('/mot-de-passe', [StudentAccountController::class, 'updatePassword'])->name('password.update');
    Route::post('/deconnexion', [StudentAuthController::class, 'destroy'])->name('logout');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [CourseController::class, 'index'])->name('dashboard');
    Route::get('/eleves/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::put('/eleves/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/eleves/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::get('/cours/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/cours/{course}/eleves/export', [CourseController::class, 'exportStudents'])->name('courses.students.export');
    Route::post('/cours', [CourseController::class, 'store'])->name('courses.store');
    Route::put('/cours/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/cours/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::delete('/inscriptions/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
    Route::put('/inscriptions/{enrollment}', [EnrollmentController::class, 'update'])->name('enrollments.update');
    Route::patch('/inscriptions/{enrollment}/position-liste-attente', [EnrollmentController::class, 'reposition'])->name('enrollments.waitlist.reposition');
    Route::post('/inscriptions/{enrollment}/forcer-acceptation', [EnrollmentController::class, 'forceAccept'])->name('enrollments.force-accept');
    Route::post('/saisons', [SeasonController::class, 'store'])->name('seasons.store');
    Route::put('/saisons/{season}', [SeasonController::class, 'update'])->name('seasons.update');
    Route::delete('/saisons/{season}', [SeasonController::class, 'destroy'])->name('seasons.destroy');
    Route::post('/rabais', [DiscountRuleController::class, 'store'])->name('discounts.store');
    Route::put('/rabais/{discountRule}', [DiscountRuleController::class, 'update'])->name('discounts.update');
    Route::delete('/rabais/{discountRule}', [DiscountRuleController::class, 'destroy'])->name('discounts.destroy');
    Route::post('/referentiels/{type}', [CatalogReferenceController::class, 'store'])->name('references.store');
    Route::put('/referentiels/{type}/{reference}', [CatalogReferenceController::class, 'update'])->name('references.update');
    Route::delete('/referentiels/{type}/{reference}', [CatalogReferenceController::class, 'destroy'])->name('references.destroy');
    Route::post('/plans-paiement', [PaymentPlanController::class, 'store'])->name('payment-plans.store');
    Route::put('/plans-paiement/{paymentPlan}', [PaymentPlanController::class, 'update'])->name('payment-plans.update');
    Route::delete('/plans-paiement/{paymentPlan}', [PaymentPlanController::class, 'destroy'])->name('payment-plans.destroy');
    Route::put('/facturation', [BillingSettingsController::class, 'update'])->name('billing.update');
    Route::put('/rappels-paiement', [PaymentReminderSettingsController::class, 'update'])->name('payment-reminders.update');
    Route::put('/conditions-generales', [TermsSettingsController::class, 'update'])->name('terms.update');
    Route::put('/frais-inscription', [RegistrationFeeSettingsController::class, 'update'])->name('registration-fees.update');
    Route::put('/bouton-contact', [ContactButtonSettingsController::class, 'update'])->name('contact-button.update');
    Route::get('/factures/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::put('/factures/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('/factures/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::get('/factures/{invoice}/document', [InvoiceController::class, 'document'])->name('invoices.document');
    Route::patch('/factures/{invoice}/payer', [InvoiceController::class, 'markPaid'])->name('invoices.paid');
    Route::post('/factures/{invoice}/paiements', [InvoiceController::class, 'payment'])->name('invoices.payments.store');
    Route::put('/factures/{invoice}/paiements/{payment}', [InvoiceController::class, 'updatePayment'])->name('invoices.payments.update');
    Route::delete('/factures/{invoice}/paiements/{payment}', [InvoiceController::class, 'destroyPayment'])->name('invoices.payments.destroy');
    Route::post('/factures/{invoice}/envoyer', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::delete('/cours/{course}/lecons/{lesson}', [CourseController::class, 'destroyLesson'])->name('lessons.destroy');
    Route::put('/cours/{course}/lecons/{lesson}', [CourseController::class, 'updateLesson'])->name('lessons.update');
    Route::post('/deconnexion', [AuthController::class, 'destroy'])->name('logout');
    Route::post('/administrateurs', [AdministratorController::class, 'store'])->name('administrators.store');
    Route::delete('/administrateurs/{administrator}', [AdministratorController::class, 'destroy'])->name('administrators.destroy');
    Route::put('/administrateurs/{administrator}', [AdministratorController::class, 'update'])->name('administrators.update');
    Route::put('/cours-essai/{trialRequest}', [TrialRequestController::class, 'update'])->name('trials.update');
    Route::delete('/cours-essai/{trialRequest}', [TrialRequestController::class, 'destroy'])->name('trials.destroy');
    Route::put('/mon-compte', [AdministratorController::class, 'updateProfile'])->name('account.update');
    Route::put('/mon-compte/mot-de-passe', [AdministratorController::class, 'updatePassword'])->name('account.password.update');
});
