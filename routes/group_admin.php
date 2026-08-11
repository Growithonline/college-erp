<?php

use App\Http\Controllers\GroupAdmin\Auth\LoginController;
use App\Http\Controllers\GroupAdmin\DashboardController;
use App\Http\Controllers\GroupAdmin\InstituteController;
use App\Http\Controllers\Institute\Finance\Wallet\DailyRegisterController;
use App\Http\Controllers\Institute\Finance\Wallet\WalletDashboardController;
use App\Http\Controllers\Institute\Reports\ReportController;
use App\Http\Controllers\Institute\StudentDirectoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('group-admin')->name('group_admin.')->group(function () {

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit')->middleware('throttle:5,1');

    Route::middleware('role.auth:group_admin')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/change-password', [LoginController::class, 'changePasswordForm'])->name('change-password');
        Route::post('/change-password', [LoginController::class, 'changePassword'])->name('change-password.update');

        Route::get('/institutes', [InstituteController::class, 'index'])->name('institutes.index');
        Route::get('/institutes/create', [InstituteController::class, 'create'])->name('institutes.create');
        Route::post('/institutes', [InstituteController::class, 'store'])->name('institutes.store');
        Route::get('/institutes/{institute}', [InstituteController::class, 'show'])->name('institutes.show');
        Route::get('/institutes/{institute}/edit', [InstituteController::class, 'edit'])->name('institutes.edit');
        Route::put('/institutes/{institute}', [InstituteController::class, 'update'])->name('institutes.update');
        Route::patch('/institutes/{institute}/toggle', [InstituteController::class, 'toggle'])->name('institutes.toggle');
        Route::post('/institutes/{institute}/reset-password', [InstituteController::class, 'resetPassword'])->name('institutes.reset-password');

        // Institute-wise reports — all reuse the same controllers/queries the institute
        // owner's own reports use, just with the institute resolved from the route and
        // ownership-checked against the Group Admin's group instead of Auth::user().
        Route::get('/institutes/{institute}/reports/fee-due-list', [ReportController::class, 'feeDueList'])->name('institutes.reports.fee-due-list');
        Route::get('/institutes/{institute}/reports/fee-collection', [ReportController::class, 'feeCollectionReport'])->name('institutes.reports.fee-collection');
        Route::get('/institutes/{institute}/reports/wallet-ledger', [WalletDashboardController::class, 'ledger'])->name('institutes.reports.wallet-ledger');
        Route::get('/institutes/{institute}/reports/expenses', [WalletDashboardController::class, 'expenseReport'])->name('institutes.reports.expenses');
        Route::get('/institutes/{institute}/reports/daily', [DailyRegisterController::class, 'index'])->name('institutes.reports.daily');
        Route::get('/institutes/{institute}/reports/students', [StudentDirectoryController::class, 'groupAdminIndex'])->name('institutes.reports.students');
    });
});
