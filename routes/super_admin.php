<?php

use App\Http\Controllers\SuperAdmin\Auth\LoginController;
use App\Http\Controllers\SuperAdmin\BackupController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\GroupController;
use App\Http\Controllers\SuperAdmin\InstituteController;
use App\Http\Controllers\SuperAdmin\SmsSettingController;
use App\Http\Controllers\SuperAdmin\UidBackfillController;
use Illuminate\Support\Facades\Route;

Route::prefix('super-admin')->name('super_admin.')->group(function () {

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit')->middleware('throttle:5,1');

    Route::middleware('auth:super_admin')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('/institutes',        [InstituteController::class, 'index'])->name('institutes.index');
        Route::get('/institutes/create', [InstituteController::class, 'create'])->name('institutes.create');
        Route::post('/institutes',       [InstituteController::class, 'store'])->name('institutes.store');
        Route::get('/institutes/{institute}',       [InstituteController::class, 'show'])->name('institutes.show');
        Route::patch('/institutes/{institute}/toggle', [InstituteController::class, 'toggle'])->name('institutes.toggle');
        Route::post('/institutes/{institute}/branding', [InstituteController::class, 'updateBranding'])->name('institutes.branding');
        Route::post('/institutes/{institute}/reset-password', [InstituteController::class, 'resetPassword'])->name('institutes.reset-password');
        Route::post('/institutes/{institute}/resend-credentials', [InstituteController::class, 'resendCredentials'])->name('institutes.resend-credentials');
        Route::post('/institutes/{institute}/clean-data',         [InstituteController::class, 'cleanData'])->name('institutes.clean-data');
        Route::get('/institutes/{institute}/export-data',          [InstituteController::class, 'exportData'])->name('institutes.export-data');
        Route::get('/institutes/{institute}/consent-pdf',           [InstituteController::class, 'consentPdf'])->name('institutes.consent-pdf');
        Route::post('/institutes/{institute}/restore-data',        [InstituteController::class, 'restoreData'])->name('institutes.restore-data');
        Route::post('/institutes/{institute}/assign-group',        [InstituteController::class, 'assignGroup'])->name('institutes.assign-group');
        Route::post('/institutes/{institute}/notify-login-ids',    [InstituteController::class, 'notifyAllLoginIds'])->name('institutes.notify-login-ids');

        // Groups / Trusts
        Route::get('/groups',                                     [GroupController::class, 'index'])->name('groups.index');
        Route::get('/groups/create',                               [GroupController::class, 'create'])->name('groups.create');
        Route::post('/groups',                                     [GroupController::class, 'store'])->name('groups.store');
        Route::get('/groups/{group}',                              [GroupController::class, 'show'])->name('groups.show');
        Route::patch('/groups/{group}/toggle',                     [GroupController::class, 'toggle'])->name('groups.toggle');
        Route::patch('/groups/{group}',                            [GroupController::class, 'update'])->name('groups.update');
        Route::post('/groups/{group}/institutes',                  [GroupController::class, 'assignInstitute'])->name('groups.institutes.store');
        Route::post('/groups/{group}/admins',                      [GroupController::class, 'storeAdmin'])->name('groups.admins.store');
        Route::patch('/groups/{group}/admins/{groupAdmin}/toggle-status', [GroupController::class, 'toggleAdminStatus'])->name('groups.admins.toggle-status');
        Route::patch('/groups/{group}/admins/{groupAdmin}/toggle-reset-permission', [GroupController::class, 'toggleResetPermission'])->name('groups.admins.toggle-reset-permission');
        Route::patch('/groups/{group}/admins/{groupAdmin}/toggle-create-permission', [GroupController::class, 'toggleCreatePermission'])->name('groups.admins.toggle-create-permission');

        // Login-UID Backfill (Staff/Partner/Center/Library-Staff)
        Route::get('/uid-backfill',             [UidBackfillController::class, 'index'])->name('uid-backfill.index');
        Route::post('/uid-backfill/run',        [UidBackfillController::class, 'run'])->name('uid-backfill.run');
        Route::post('/uid-backfill/reset-legacy', [UidBackfillController::class, 'resetLegacy'])->name('uid-backfill.reset-legacy');

        // Database Backup
        Route::get('/backup',                   [BackupController::class, 'index'])->name('backup.index');
        Route::get('/backup/full',              [BackupController::class, 'fullBackup'])->name('backup.full');
        Route::get('/backup/schema',            [BackupController::class, 'schemaBackup'])->name('backup.schema');
        Route::get('/backup/download/{file}',   [BackupController::class, 'downloadFile'])->name('backup.download')
            ->where('file', '[a-zA-Z0-9_\-\.]+');

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



