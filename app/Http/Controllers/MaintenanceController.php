<?php

namespace App\Http\Controllers;

use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public function __invoke(): Response|RedirectResponse
    {
        if (! Settings::bool('maintenance') || auth()->user()->isAdmin()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Maintenance', ['message' => Settings::get('maintenance_message')]);
    }
}
