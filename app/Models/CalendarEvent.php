<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CalendarEvent extends Model
{
    /** "created_by" is set explicitly in the controller, so it is not mass-assignable. */
    protected $fillable = [
        'title',
        'description',
        'event_date',
        'event_time',
        'category',
        'repeats_yearly',
        'emoji',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'repeats_yearly' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** The date (00:00) of the next time this happens, or null when a one-off event is over. */
    public function nextOccurrence(): ?Carbon
    {
        $today = Carbon::today();

        if (! $this->repeats_yearly) {
            $date = Carbon::parse($this->event_date->toDateString());

            return $date->gte($today) ? $date : null;
        }

        $next = $this->occurrenceInYear($today->year);

        return $next->lt($today) ? $this->occurrenceInYear($today->year + 1) : $next;
    }

    /** Same as nextOccurrence(), with the time of day applied when the event has one. */
    public function nextAt(): ?Carbon
    {
        $date = $this->nextOccurrence();

        if (! $date) {
            return null;
        }

        return $this->event_time ? $date->copy()->setTimeFromTimeString($this->event_time) : $date;
    }

    /** Handles 29 February in non-leap years. */
    protected function occurrenceInYear(int $year): Carbon
    {
        $month = $this->event_date->month;
        $day = min($this->event_date->day, Carbon::create($year, $month, 1)->daysInMonth);

        return Carbon::create($year, $month, $day, 0, 0, 0);
    }

    public function toPayload(): array
    {
        $next = $this->nextAt();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'event_date' => $this->event_date->toDateString(),
            'event_time' => $this->event_time ? substr($this->event_time, 0, 5) : null,
            'category' => $this->category,
            'repeats_yearly' => $this->repeats_yearly,
            'emoji' => $this->emoji,
            'creator' => $this->creator?->name,
            'next_at' => $next?->toIso8601String(),
            'years' => ($next && $this->repeats_yearly) ? $next->year - $this->event_date->year : null,
        ];
    }

    /** Events that still lie ahead (or are today), soonest first. */
    public static function upcoming(?int $limit = null): Collection
    {
        $events = static::with('creator:id,name')->get()
            ->filter(fn (self $event) => $event->nextOccurrence() !== null)
            ->sortBy(fn (self $event) => $event->nextAt()->getTimestamp())
            ->values();

        return $limit ? $events->take($limit) : $events;
    }

    /** One-off events that are over, newest first. */
    public static function past(): Collection
    {
        return static::with('creator:id,name')->get()
            ->filter(fn (self $event) => $event->nextOccurrence() === null)
            ->sortByDesc(fn (self $event) => $event->event_date->getTimestamp())
            ->values();
    }
}