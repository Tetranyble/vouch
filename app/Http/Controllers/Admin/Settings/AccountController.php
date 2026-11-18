<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\UserConfig;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function profile(Request $request)
    {
        $user    = $request->user();
        $configs = UserConfig::where('user_id', $user->id)
            ->whereIn('key', ['signature', 'initials', 'totp_secret'])
            ->pluck('value', 'key');

        return Inertia::render('Admin/Settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status'          => session('status'),
            'userSignature'   => $configs->get('signature'),
            'userInitials'    => $configs->get('initials'),
            'mfaEnabled'      => (bool) $configs->get('totp_secret'),
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $account = Account::findOrFail($user->account_id);

        $configs = UserConfig::where('user_id', $user->id)
            ->whereIn('key', ['signature', 'initials', 'totp_secret', 'prefill_signature'])
            ->pluck('value', 'key');

        // Default prefill_signature to true when no preference is saved yet
        $prefillSignature = $configs->has('prefill_signature')
            ? (bool) $configs->get('prefill_signature')
            : true;

        return Inertia::render('Admin/Settings/Account', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'locale' => $account->locale,
                'timezone' => $account->timezone,
            ],
            'timezones'        => \DateTimeZone::listIdentifiers(),
            'mustVerifyEmail'  => $user instanceof MustVerifyEmail,
            'status'           => session('status'),
            'userSignature'    => $configs->get('signature'),
            'userInitials'     => $configs->get('initials'),
            'mfaEnabled'       => (bool) $configs->get('totp_secret'),
            'prefillSignature' => $prefillSignature,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $account = Account::findOrFail($user->account_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'locale' => 'required|string|max:10',
            'timezone' => ['required', 'string', Rule::in(\DateTimeZone::listIdentifiers())],
        ]);

        $account->update($request->only('name', 'locale', 'timezone'));

        return redirect()->route('settings.account.index')
            ->with('success', 'Account settings updated.');
    }

    public function updatePreferences(Request $request)
    {
        $request->validate([
            'prefill_signature' => 'required|boolean',
        ]);

        $user = $request->user();

        UserConfig::updateOrCreate(
            ['user_id' => $user->id, 'key' => 'prefill_signature'],
            ['value' => $request->boolean('prefill_signature')]
        );

        return back()->with('success', 'Preferences saved.');
    }

    public function inviteUser(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,user,viewer',
        ]);

        $currentUser = $request->user();

        $newUser = User::create([
            'first_name'         => $request->first_name,
            'last_name'          => $request->last_name,
            'email'              => $request->email,
            'password' => Hash::make(\Illuminate\Support\Str::random(32)),
            'role'               => $request->role,
            'account_id'         => $currentUser->account_id,
            'email_verified_at'       => null,
        ]);

        // Send a password-reset link so the invitee can set their own password
        Password::sendResetLink(['email' => $newUser->email]);

        return redirect()->route('settings.account.index')
            ->with('success', "Invitation sent to {$newUser->email}. They will receive an email to set their password.");
    }

    public function removeUser(Request $request, string $id)
    {
        $currentUser = $request->user();

        if ((int) $id === $currentUser->id) {
            return redirect()->route('settings.account.index')
                ->with('error', 'You cannot remove yourself.');
        }

        $user = User::where('account_id', $currentUser->account_id)->findOrFail($id);
        $user->delete();

        return redirect()->route('settings.account.index')
            ->with('success', 'User removed.');
    }
}
