<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TeamsController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $status = $request->query('status', 'active');

        $forAccount = fn ($query) => $query->where('account_id', $currentUser->account_id);

        if ($status === 'archived') {
            $users = $forAccount(User::onlyTrashed())
                ->where('role', '!=', 'integration')
                ->orderBy('first_name')
                ->get();
        } elseif ($status === 'integration') {
            $users = $forAccount(User::query())
                ->where('role', 'integration')
                ->orderBy('first_name')
                ->get();
        } else {
            $users = $forAccount(User::query())
                ->where('role', '!=', 'integration')
                ->orderByRaw('CASE WHEN email_verified_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('first_name')
                ->get();
        }

        $lastActive = DB::table('sessions')
            ->whereIn('user_id', $users->pluck('id'))
            ->selectRaw('user_id, MAX(last_activity) AS latest')
            ->groupBy('user_id')
            ->pluck('latest', 'user_id');

        $archivedCount = $forAccount(User::onlyTrashed())->where('role', '!=', 'integration')->count();
        $integrationCount = $forAccount(User::query())->where('role', 'integration')->count();

        return Inertia::render('Admin/Settings/Teams', [
            'users' => $users->map(fn ($u) => [
                'id' => $u->id,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'email' => $u->email,
                'role' => $u->role,
                'is_pending' => is_null($u->email_verified_at),
                'last_active_at' => isset($lastActive[$u->id])
                    ? \Illuminate\Support\Carbon::createFromTimestamp((int) $lastActive[$u->id])->toIso8601String()
                    : null,
                'created_at' => $u->created_at->toIso8601String(),
            ]),
            'status' => $status,
            'archivedCount' => $archivedCount,
            'integrationCount' => $integrationCount,
        ]);
    }

    public function invite(Request $request)
    {
        $currentUser = $request->user();
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', function (string $attribute, mixed $value, \Closure $fail) use ($currentUser): void {
                $existing = User::withTrashed()->where('email', $value)->first();
                if ($existing && (! $existing->trashed() || $existing->account_id !== $currentUser->account_id)) {
                    $fail('A user with this email address already exists.');
                }
            }],
            'role' => 'required|in:admin,user,viewer',
        ]);

        // Re-activate an archived user in the same account if email matches
        $existing = User::onlyTrashed()
            ->where('account_id', $currentUser->account_id)
            ->where('email', $request->email)
            ->first();

        if ($existing) {
            $existing->restore();
            $existing->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'role' => $request->role,
            ]);
            Password::sendResetLink(['email' => $existing->email]);

            return redirect()->route('settings.teams.index')
                ->with('success', "User {$existing->email} has been re-activated and sent a new invitation.");
        }

        $newUser = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make(Str::random(32)),
            'role' => $request->role,
            'account_id' => $currentUser->account_id,
            'email_verified_at' => null,
        ]);

        Password::sendResetLink(['email' => $newUser->email]);

        return redirect()->route('settings.teams.index')
            ->with('success', "Invitation sent to {$newUser->email}.");
    }

    public function update(Request $request, int $id)
    {
        $currentUser = $request->user();
        $isSelf = $id === $currentUser->id;

        $user = User::where('account_id', $currentUser->account_id)->findOrFail($id);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'role' => $isSelf ? 'sometimes' : 'required|in:admin,user,viewer',
        ]);

        $data = $request->only(['first_name', 'last_name', 'email']);
        if (! $isSelf) {
            $data['role'] = $request->role;
        }

        $user->update($data);

        return redirect()->route('settings.teams.index')
            ->with('success', 'User has been updated.');
    }

    public function updateRole(Request $request, int $id)
    {
        $currentUser = $request->user();

        if ($currentUser->role !== 'admin') {
            return redirect()->route('settings.teams.index')
                ->with('error', 'Only admins can change roles.');
        }

        if ($id === $currentUser->id) {
            return redirect()->route('settings.teams.index')
                ->with('error', 'You cannot change your own role.');
        }

        $request->validate(['role' => 'required|in:admin,user,viewer']);

        User::where('account_id', $currentUser->account_id)
            ->findOrFail($id)
            ->update(['role' => $request->role]);

        return redirect()->route('settings.teams.index')
            ->with('success', 'Role updated.');
    }

    public function resendInvite(Request $request, int $id)
    {
        $currentUser = $request->user();
        $user = User::where('account_id', $currentUser->account_id)->findOrFail($id);

        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('settings.teams.index')
            ->with('success', "Password reset email sent to {$user->email}.");
    }

    public function destroy(Request $request, int $id)
    {
        $currentUser = $request->user();

        if ($id === $currentUser->id) {
            return redirect()->route('settings.teams.index')
                ->with('error', 'You cannot archive yourself.');
        }

        User::where('account_id', $currentUser->account_id)
            ->where('role', '!=', 'integration')
            ->findOrFail($id)
            ->delete();

        return redirect()->route('settings.teams.index')
            ->with('success', 'User has been archived.');
    }

    public function unarchive(Request $request, int $id)
    {
        $currentUser = $request->user();

        User::withTrashed()
            ->where('account_id', $currentUser->account_id)
            ->onlyTrashed()
            ->findOrFail($id)
            ->restore();

        return redirect()->route('settings.teams.index', ['status' => 'archived'])
            ->with('success', 'User has been restored.');
    }
}
