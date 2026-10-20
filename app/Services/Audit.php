<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class Audit
{
    /**
     * Write one audit entry. Never put secrets (passwords, tokens, file contents) in $properties.
     * A failure to write the log must not break the request, so it is only reported.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function record(
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null,
    ): void {
        try {
            $actor ??= Auth::user();
            $request = app()->runningInConsole() ? null : request();

            AuditLog::create([
                'user_id' => $actor?->id,
                'actor_name' => $actor?->name,
                'event' => $event,
                'description' => Str::limit($description, 250, ''),
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'properties' => $properties ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
            ]);
        } catch (Throwable $e) {
            Log::error('Audit log write failed: '.$e->getMessage());
        }
    }
}
