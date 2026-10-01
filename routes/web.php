<?php

use App\Http\Controllers\Admin\TechnicianDocumentPreviewController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\GoogleRegistrationController;
use App\Http\Controllers\Auth\PendingTechnicianRegistrationController;
use App\Http\Controllers\Auth\StaffLoginController;
use App\Http\Controllers\Auth\TechnicianEmailVerificationController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\RealtimeController;
use App\Http\Controllers\SupportAttachmentController;
use App\Http\Controllers\Technician\ApplicationResubmissionController;
use App\Http\Controllers\VercelCronController;
use App\Http\Controllers\WalkInReceiptController;
use App\Http\Middleware\EnsurePendingGoogleRegistration;
use App\Livewire\Customer\ModulePage as CustomerModulePage;
use App\Livewire\SuperAdmin\Dashboard as SuperAdminDashboard;
use App\Livewire\SuperAdmin\ModulePage as SuperAdminModulePage;
use App\Livewire\Technician\ModulePage as TechnicianModulePage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::get('/', LandingPageController::class)->name('home');
Route::get('internal/cron/expire-walk-ins', VercelCronController::class)
    ->name('internal.cron.expire-walk-ins');

Route::get('admin', function (): RedirectResponse {
    if (auth()->guest()) {
        return to_route('admin.login');
    }

    abort_unless(auth()->user()->isSuperAdmin(), 403);

    return to_route('admin.dashboard');
})->name('admin.index');

Route::get('staff', function (): RedirectResponse {
    if (auth()->guest()) {
        return to_route('staff.login');
    }

    abort_unless(auth()->user()->isStaff(), 403);

    return to_route('staff.dashboard');
})->name('staff.index');

Route::middleware('guest:web')->prefix('admin')->name('admin.')->group(function () {
    Route::get('login', AdminLoginController::class)->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware('guest:web')->prefix('staff')->name('staff.')->group(function () {
    Route::get('login', StaffLoginController::class)->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::middleware(['guest:web', EnsurePendingGoogleRegistration::class])
    ->prefix('auth/google/register')
    ->name('auth.google.register.')
    ->group(function () {
        Route::get('/', [GoogleRegistrationController::class, 'show'])->name('show');
        Route::post('/', [GoogleRegistrationController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('store');
    });

Route::middleware('guest:web')->prefix('technician/registration')->name('technician.registration.')->group(function () {
    Route::post('/', [PendingTechnicianRegistrationController::class, 'store'])
        ->middleware('throttle:5,1')->name('store');
    Route::get('verify-email', [PendingTechnicianRegistrationController::class, 'show'])->name('show');
    Route::post('verify-email', [PendingTechnicianRegistrationController::class, 'verify'])
        ->middleware('throttle:6,1')->name('verify');
    Route::post('verify-email/resend', [PendingTechnicianRegistrationController::class, 'resend'])
        ->middleware('throttle:2,1')->name('resend');
    Route::post('verify-email/change', [PendingTechnicianRegistrationController::class, 'updateEmail'])
        ->middleware('throttle:2,1')->name('email.update');
});

Route::middleware('auth')->group(function () {
    Route::get('technician/verify-email', [TechnicianEmailVerificationController::class, 'show'])
        ->name('technician.email-otp.show');
    Route::post('technician/verify-email', [TechnicianEmailVerificationController::class, 'verify'])
        ->middleware('throttle:6,1')
        ->name('technician.email-otp.verify');
    Route::post('technician/verify-email/resend', [TechnicianEmailVerificationController::class, 'resend'])
        ->middleware('throttle:2,1')
        ->name('technician.email-otp.resend');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('support/messages/{message}/attachment', SupportAttachmentController::class)->name('support.attachment');
    Route::get('walk-ins/{entry}/receipt', WalkInReceiptController::class)->name('walk-ins.receipt');
    Route::get('dashboard', function () {
        if (auth()->user()->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if (auth()->user()->isStaff()) {
            return redirect()->route('staff.dashboard');
        }

        if (auth()->user()->isTechnician()) {
            return redirect()->route('technician.module');
        }

        return redirect()->route('customer.module');
    })->name('dashboard');
    Route::get('realtime/{scope}', [RealtimeController::class, 'snapshot'])
        ->where('scope', '[a-z0-9-]+')
        ->middleware('throttle:realtime')
        ->name('realtime.snapshot');
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', SuperAdminDashboard::class)->name('dashboard');
        Route::get('technician-documents/{document}', TechnicianDocumentPreviewController::class)
            ->name('technician-documents.show');
        Route::get('{module}', SuperAdminModulePage::class)
            ->where('module', '[a-z0-9-]+')
            ->name('module');
    });
    Route::middleware('role:staff')->prefix('staff')->name('staff.')->group(function () {
        Route::get('dashboard', SuperAdminDashboard::class)->name('dashboard');
        Route::get('technician-documents/{document}', TechnicianDocumentPreviewController::class)
            ->name('technician-documents.show');
        Route::get('{module}', SuperAdminModulePage::class)
            ->where('module', '[a-z0-9-]+')
            ->name('module');
    });
    Route::middleware(['role:technician', 'technician.email.verified'])->group(function () {
        Route::post('technician/application/resubmit', ApplicationResubmissionController::class)
            ->middleware('throttle:5,1')
            ->name('technician.application.resubmit');
        Route::get('technician/{module?}', TechnicianModulePage::class)
            ->where('module', '[a-z0-9-]+')
            ->name('technician.module');
    });
    Route::middleware('role:customer')->group(function () {
        Route::get('customer/{module?}', CustomerModulePage::class)
            ->where('module', '[a-z0-9-]+')
            ->name('customer.module');
    });
});

require __DIR__.'/settings.php';
