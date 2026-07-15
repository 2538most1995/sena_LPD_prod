<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('next.home');

Route::get('/login', fn () => redirect(url('/').'#/login'))
    ->name('login');

Route::prefix('next-api/auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware(['guest', 'throttle:login'])
        ->name('next.login');

    Route::post('/logout', [AuthController::class, 'destroy'])
        ->middleware('auth')
        ->name('next.logout');
});
