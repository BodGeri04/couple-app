<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\ShoppingItem;
use App\Models\Wish;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $me = $request->user()->id;

        return Inertia::render('Dashboard', [
            'nextEvent' => CalendarEvent::upcoming(1)->first()?->toPayload(),
            'shoppingOpen' => ShoppingItem::where('is_checked', false)->count(),
            'wishesPending' => Wish::where('user_id', '!=', $me)->whereNull('reaction')->count(),
        ]);
    }
}