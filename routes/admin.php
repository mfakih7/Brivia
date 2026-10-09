<?php

use App\Http\Controllers\Admin\AccountSecurityController;
use App\Http\Controllers\Admin\Auth\InvitationAcceptanceController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\PasswordResetController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Middleware\EnsureActiveStaff;
use App\Http\Middleware\PrivateNoIndex;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(PrivateNoIndex::class)->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');
        Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
        Route::post('forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:admin-password-reset')->name('password.email');
        Route::get('reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:admin-password-reset')->name('password.update');
    });

    Route::get('invitations/{token}', [InvitationAcceptanceController::class, 'show'])->name('invitations.show');
    Route::post('invitations/{token}', [InvitationAcceptanceController::class, 'store'])->middleware('throttle:admin-invitation-accept')->name('invitations.accept');

    // Every admin data route requires an authenticated, active staff account.
    Route::middleware(['auth', EnsureActiveStaff::class])->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('account/security', [AccountSecurityController::class, 'show'])->name('account.security');
        Route::put('account/password', [AccountSecurityController::class, 'updatePassword'])->name('account.password');

        require __DIR__.'/admin-modules.php';
    });
});
