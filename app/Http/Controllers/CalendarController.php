<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Calendar/Index', [
            'upcoming' => CalendarEvent::upcoming()->map->toPayload()->values(),
            'past' => CalendarEvent::past()->map->toPayload()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = new CalendarEvent($this->validated($request));
        $event->created_by = $request->user()->id;
        $event->save();

        return back();
    }

    public function update(Request $request, CalendarEvent $event): RedirectResponse
    {
        $event->update($this->validated($request));

        return back();
    }

    public function destroy(CalendarEvent $event): RedirectResponse
    {
        $event->delete();

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'category' => ['required', 'in:anniversary,milestone,date,special'],
            'repeats_yearly' => ['boolean'],
            'emoji' => ['nullable', 'string', 'max:8'],
        ]);
    }
}