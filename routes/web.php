<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\ShoppingController;
use App\Http\Controllers\SnapController;
use App\Http\Controllers\WishController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar', [CalendarController::class, 'store'])->name('calendar.store');
    Route::patch('/calendar/{event}', [CalendarController::class, 'update'])->name('calendar.update');
    Route::delete('/calendar/{event}', [CalendarController::class, 'destroy'])->name('calendar.destroy');

    // Shopping list ("checked" must come before "{item}")
    Route::get('/shopping', [ShoppingController::class, 'index'])->name('shopping.index');
    Route::post('/shopping', [ShoppingController::class, 'store'])->name('shopping.store');
    Route::delete('/shopping/checked', [ShoppingController::class, 'clear'])->name('shopping.clear');
    Route::patch('/shopping/{item}/toggle', [ShoppingController::class, 'toggle'])->name('shopping.toggle');
    Route::delete('/shopping/{item}', [ShoppingController::class, 'destroy'])->name('shopping.destroy');

    // Wishlist
    Route::get('/wishes', [WishController::class, 'index'])->name('wishes.index');
    Route::post('/wishes', [WishController::class, 'store'])->name('wishes.store');
    Route::patch('/wishes/{wish}', [WishController::class, 'update'])->name('wishes.update');
    Route::patch('/wishes/{wish}/reaction', [WishController::class, 'react'])->name('wishes.react');
    Route::delete('/wishes/{wish}', [WishController::class, 'destroy'])->name('wishes.destroy');

    // Snaps (encrypted photos)
    Route::get('/snaps', [SnapController::class, 'index'])->name('snaps.index');
    Route::post('/snaps', [SnapController::class, 'store'])->name('snaps.store');
    Route::get('/snaps/{snap}/image', [SnapController::class, 'image'])->name('snaps.image');
    Route::post('/snaps/{snap}/seen', [SnapController::class, 'seen'])->name('snaps.seen');
    Route::delete('/snaps/{snap}', [SnapController::class, 'destroy'])->name('snaps.destroy');

    // Web Push
    Route::post('/push/subscribe', [PushController::class, 'subscribe'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe'])->name('push.unsubscribe');
    Route::post('/push/test', [PushController::class, 'test'])->name('push.test');
});

require __DIR__.'/auth.php';