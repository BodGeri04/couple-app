<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Placeholders: each one is replaced by a real module in the next steps
    Route::get('/calendar', fn () => Inertia::render('Placeholder', [
        'title' => 'Naptár',
        'emoji' => '📅',
        'text' => 'Itt lesznek a közös fontos napjaitok és a visszaszámlálók.',
    ]))->name('calendar.index');

    Route::get('/snaps', fn () => Inertia::render('Placeholder', [
        'title' => 'Pillanat',
        'emoji' => '📸',
        'text' => 'Küldj egy pillanatképet a párodnak, hamarosan.',
    ]))->name('snaps.index');

    Route::get('/shopping', fn () => Inertia::render('Placeholder', [
        'title' => 'Bevásárlás',
        'emoji' => '🛒',
        'text' => 'Közös bevásárlólista valós időben, hamarosan.',
    ]))->name('shopping.index');

    Route::get('/wishes', fn () => Inertia::render('Placeholder', [
        'title' => 'Kívánságok',
        'emoji' => '🎁',
        'text' => 'Kívánságlisták egymásnak, hamarosan.',
    ]))->name('wishes.index');
});

// TEMPORARY: role check test, removed when we build the admin panel
Route::middleware(['auth', 'role:admin'])->get('/admin/ping', fn () => response('admin ok'));

require __DIR__.'/auth.php';