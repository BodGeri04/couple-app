<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public const CATEGORIES = [
        'auth' => 'Belépés',
        'access' => 'Hozzáférés',
        'activity' => 'Tevékenység',
        'admin' => 'Admin',
    ];

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'category' => ['nullable', 'in:'.implode(',', array_keys(self::CATEGORIES))],
            'user' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $logs = AuditLog::query()
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('event', 'like', $category.'.%'))
            ->when($filters['user'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $q->where(fn ($q) => $q
                    ->whereLike('description', $like)
                    ->orWhereLike('event', $like)
                    ->orWhereLike('ip_address', $like)
                    ->orWhereLike('actor_name', $like));
            })
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', $to.' 23:59:59'))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Logs', [
            'logs' => $logs->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'event' => $log->event,
                'category' => $log->category,
                'description' => $log->description,
                'actor' => $log->actor_name,
                'ip' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'properties' => $log->properties,
                'created_at' => $log->created_at->toIso8601String(),
            ]),
            'filters' => [
                'category' => $filters['category'] ?? '',
                'user' => $filters['user'] ?? '',
                'q' => $filters['q'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
            'categories' => self::CATEGORIES,
            'users' => User::orderBy('id')->get(['id', 'name']),
        ]);
    }
}
