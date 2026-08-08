<?php

use App\Http\Controllers\GroupAdmin\Auth\LoginController;
use App\Http\Controllers\GroupAdmin\DashboardController;
use App\Http\Controllers\GroupAdmin\InstituteController;
use Illuminate\Support\Facades\Route;

Route::prefix('group-admin')->name('group_admin.')->group(function () {

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit')->middleware('throttle:5,1');

    Route::middleware('role.auth:group_admin')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/institutes', [InstituteController::class, 'index'])->name('institutes.index');
        Route::post('/institutes/{institute}/reset-password', [InstituteController::class, 'resetPassword'])->name('institutes.reset-password');
    });
});
