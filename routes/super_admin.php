<?php

use App\Http\Controllers\SuperAdmin\Auth\LoginController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\InstituteController;
use App\Http\Controllers\SuperAdmin\SmsSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('super-admin')->name('super_admin.')->group(function () {

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

    Route::middleware('auth:super_admin')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('/institutes',        [InstituteController::class, 'index'])->name('institutes.index');
        Route::get('/institutes/create', [InstituteController::class, 'create'])->name('institutes.create');
        Route::post('/institutes',       [InstituteController::class, 'store'])->name('institutes.store');
        Route::get('/institutes/{institute}',       [InstituteController::class, 'show'])->name('institutes.show');
        Route::patch('/institutes/{institute}/toggle', [InstituteController::class, 'toggle'])->name('institutes.toggle');
        Route::post('/institutes/{institute}/reset-password', [InstituteController::class, 'resetPassword'])->name('institutes.reset-password');

        // SMS Management
        Route::prefix('sms')->name('sms.')->group(function () {
            Route::get('/',                                    [SmsSettingController::class, 'index'])->name('index');
            Route::get('/analytics',                           [SmsSettingController::class, 'analytics'])->name('analytics');
            Route::post('/save',                               [SmsSettingController::class, 'saveSettings'])->name('save');
            Route::post('/toggle-active',                      [SmsSettingController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/test-connection',                    [SmsSettingController::class, 'testConnection'])->name('test-connection');
            Route::post('/institutes/{instituteId}/toggle',    [SmsSettingController::class, 'toggleInstituteSmS'])->name('toggle-institute');
            Route::get('/institutes/{instituteId}/logs',       [SmsSettingController::class, 'instituteLogs'])->name('institute-logs');
            Route::get('/broadcast',                           [SmsSettingController::class, 'showBroadcast'])->name('broadcast');
            Route::post('/broadcast',                          [SmsSettingController::class, 'sendBroadcast'])->name('broadcast.send');
        });
    });
});



