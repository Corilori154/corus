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
use App\Http\Controllers\SchoolRegistrationController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\WaitlistController;
use App\Http\Controllers\Auth\PasswordResetController;
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
Route::get('/ecole/{school}/cours/{course}', [CourseCatalogController::class, 'show'])->name('courses.show');
Route::post('/ecole/{school}/inscriptions', [CourseCatalogController::class, 'store'])->name('courses.enroll');
Route::post('/ecole/{school}/devis', [CourseCatalogController::class, 'quote'])->middleware('throttle:30,1')->name('courses.quote');
Route::post('/ecole/{school}/cours-essai', [CourseCatalogController::class, 'storeTrial'])->middleware('throttle:20,1')->name('courses.trial');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [CourseController::class, 'index'])->name('dashboard');
    Route::get('/eleves/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::post('/cours', [CourseController::class, 'store'])->name('courses.store');
    Route::put('/cours/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/cours/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::post('/saisons', [SeasonController::class, 'store'])->name('seasons.store');
    Route::put('/saisons/{season}', [SeasonController::class, 'update'])->name('seasons.update');
    Route::delete('/saisons/{season}', [SeasonController::class, 'destroy'])->name('seasons.destroy');
    Route::post('/rabais', [DiscountRuleController::class, 'store'])->name('discounts.store');
    Route::delete('/rabais/{discountRule}', [DiscountRuleController::class, 'destroy'])->name('discounts.destroy');
    Route::post('/referentiels/{type}', [CatalogReferenceController::class, 'store'])->name('references.store');
    Route::delete('/referentiels/{type}/{reference}', [CatalogReferenceController::class, 'destroy'])->name('references.destroy');
    Route::post('/plans-paiement', [PaymentPlanController::class, 'store'])->name('payment-plans.store');
    Route::delete('/plans-paiement/{paymentPlan}', [PaymentPlanController::class, 'destroy'])->name('payment-plans.destroy');
    Route::put('/facturation', [BillingSettingsController::class, 'update'])->name('billing.update');
    Route::get('/factures/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/factures/{invoice}/document', [InvoiceController::class, 'document'])->name('invoices.document');
    Route::patch('/factures/{invoice}/payer', [InvoiceController::class, 'markPaid'])->name('invoices.paid');
    Route::post('/factures/{invoice}/paiements', [InvoiceController::class, 'payment'])->name('invoices.payments.store');
    Route::post('/factures/{invoice}/envoyer', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::delete('/cours/{course}/lecons/{lesson}', [CourseController::class, 'destroyLesson'])->name('lessons.destroy');
    Route::post('/deconnexion', [AuthController::class, 'destroy'])->name('logout');
});
