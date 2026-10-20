<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailySnap;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $usesDatabaseSessions = config('session.driver') === 'database';

        $lastSeen = $usesDatabaseSessions
            ? DB::table('sessions')->whereNotNull('user_id')->groupBy('user_id')->selectRaw('user_id, max(last_activity) as seen')->pluck('seen', 'user_id')
            : collect();

        return Inertia::render('Admin/Users', [
            'users' => User::orderBy('id')->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at?->toIso8601String(),
                'last_seen' => isset($lastSeen[$user->id]) ? Carbon::createFromTimestamp($lastSeen[$user->id])->toIso8601String() : null,
                'snaps_sent' => DailySnap::where('sender_id', $user->id)->count(),
                'devices' => PushSubscription::where('user_id', $user->id)->count(),
            ]),
        ]);
    }

    /** Name, e-mail and (optionally) password. The role can never be changed from here. */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validateWithBag('user-'.$user->id, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->fill(['name' => $data['name'], 'email' => $data['email']]);

        $changed = array_keys($user->getDirty());
        $passwordChanged = filled($data['password'] ?? null);

        if ($passwordChanged) {
            $user->password = $data['password'];
            $user->setRememberToken(Str::random(60));
            $changed[] = 'password';
        }

        if (! $changed) {
            return back()->with('flash', ['type' => 'success', 'message' => 'Nem történt változás.']);
        }

        $before = $user->getOriginal();
        $user->save();

        if ($passwordChanged && config('session.driver') === 'database') {
            // The old password must stop working everywhere; keep only the admin's own current session.
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        Audit::record(
            'admin.user.updated',
            "Felhasználó módosítva: {$user->name} (".implode(', ', $changed).')',
            $user,
            array_filter([
                'fields' => $changed,
                'name' => in_array('name', $changed, true) ? ['from' => $before['name'], 'to' => $user->name] : null,
                'email' => in_array('email', $changed, true) ? ['from' => $before['email'], 'to' => $user->email] : null,
            ]),
        );

        return back()->with('flash', ['type' => 'success', 'message' => "{$user->name} adatai mentve."]);
    }
}
