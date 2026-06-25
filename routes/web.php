<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Subscriptions (public). The Planes view + its JSON are guest-reachable; the
// notify/register actions require an authenticated user (guest or registered).
Route::get('/plans', [SubscriptionController::class, 'plans'])->name('subscriptions.plans');
Route::get('/plans/json', [SubscriptionController::class, 'plansJson'])->name('subscriptions.plans.json');

Route::middleware('auth')->group(function () {
    Route::post('/subscriptions/notify-payment', [SubscriptionController::class, 'notifyPayment'])
        ->name('subscriptions.notify-payment');
    Route::post('/subscriptions/register', [SubscriptionController::class, 'register'])
        ->name('subscriptions.register');
});

// Free/guest usage is not gated by email verification (D4): the Breeze
// dashboard stays reachable for unverified users. Verification remains
// available but never blocks the app.
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
