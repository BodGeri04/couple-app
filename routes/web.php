<?php

use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\CmsController as AdminCmsController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaintenanceController;
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
    Route::middleware('feature:calendar')->group(function () {
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::post('/calendar', [CalendarController::class, 'store'])->name('calendar.store');
        Route::patch('/calendar/{event}', [CalendarController::class, 'update'])->name('calendar.update');
        Route::delete('/calendar/{event}', [CalendarController::class, 'destroy'])->name('calendar.destroy');
    });

    // Shopping list ("checked" must come before "{item}")
    Route::middleware('feature:shopping')->group(function () {
        Route::get('/shopping', [ShoppingController::class, 'index'])->name('shopping.index');
        Route::post('/shopping', [ShoppingController::class, 'store'])->name('shopping.store');
        Route::delete('/shopping/checked', [ShoppingController::class, 'clear'])->name('shopping.clear');
        Route::patch('/shopping/{item}/toggle', [ShoppingController::class, 'toggle'])->name('shopping.toggle');
        Route::delete('/shopping/{item}', [ShoppingController::class, 'destroy'])->name('shopping.destroy');
    });

    // Wishlist
    Route::middleware('feature:wishes')->group(function () {
        Route::get('/wishes', [WishController::class, 'index'])->name('wishes.index');
        Route::post('/wishes', [WishController::class, 'store'])->name('wishes.store');
        Route::patch('/wishes/{wish}', [WishController::class, 'update'])->name('wishes.update');
        Route::patch('/wishes/{wish}/reaction', [WishController::class, 'react'])->name('wishes.react');
        Route::delete('/wishes/{wish}', [WishController::class, 'destroy'])->name('wishes.destroy');
    });

    // Snaps (encrypted photos)
    Route::middleware('feature:snaps')->group(function () {
        Route::get('/snaps', [SnapController::class, 'index'])->name('snaps.index');
        Route::post('/snaps', [SnapController::class, 'store'])->name('snaps.store');
        Route::get('/snaps/{snap}/image', [SnapController::class, 'image'])->name('snaps.image');
        Route::post('/snaps/{snap}/seen', [SnapController::class, 'seen'])->name('snaps.seen');
        Route::delete('/snaps/{snap}', [SnapController::class, 'destroy'])->name('snaps.destroy');
    });

    // Web Push
    Route::post('/push/subscribe', [PushController::class, 'subscribe'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe'])->name('push.unsubscribe');
    Route::post('/push/test', [PushController::class, 'test'])->name('push.test');

    Route::get('/maintenance', MaintenanceController::class)->name('maintenance');
});

/*
 * Admin panel: signed in AND role "admin". Every other role gets a 403
 * (and the attempt is written to the audit log).
 */
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::patch('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::post('/notifications/test', [AdminNotificationController::class, 'test'])->name('notifications.test');

    // Credentials are managed here, so the admin must have confirmed their password recently
    // (asked up front on the list, and enforced again on the update itself).
    Route::middleware('password.confirm')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/force-logout', [AdminUserController::class, 'forceLogout'])->name('users.force-logout');
    });

    Route::get('/cms', [AdminCmsController::class, 'index'])->name('cms.index');
    Route::put('/cms/menu', [AdminCmsController::class, 'updateMenu'])->name('cms.menu');

    Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
    Route::get('/media/{snap}/image', [AdminMediaController::class, 'image'])->name('media.image');
    Route::patch('/media/{snap}/moderation', [AdminMediaController::class, 'moderate'])->name('media.moderate');
    Route::delete('/media/{snap}', [AdminMediaController::class, 'destroy'])->name('media.destroy');

    Route::get('/logs', [AdminAuditLogController::class, 'index'])->name('logs.index');
});

require __DIR__.'/auth.php';