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

// The real freemium app lives under /backoffice. The legacy Breeze /dashboard
// stub just bounces there so no authenticated user ever lands on the empty
// scaffold (kept named 'dashboard' for the email-verification redirect, etc.).
// Not email-verification gated (D4): free/guest usage never requires verifying.
Route::get('/dashboard', function () {
    return redirect()->route('backoffice.dashboard.index');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
