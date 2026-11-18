<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        $email = $googleUser->getEmail();
        $providerId = $googleUser->getId();
        $attributes = $googleUser->user ?? [];
        // Google asserts a verified email via its userinfo endpoint. Never link an
        // unverified third-party email to an existing local account.
        $verified = ($attributes['verified_email'] ?? $attributes['email_verified'] ?? false) === true;
        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! is_string($providerId) || $providerId === '' || ! $verified) {
            return redirect()->route('login')->withErrors(['email' => 'Google did not provide a verified email address.']);
        }

        $user = User::query()->where('google_id', $providerId)->first();
        if ($user && strcasecmp($user->email, $email) !== 0) {
            return redirect()->route('login')->withErrors(['email' => 'Google account email no longer matches. Contact support.']);
        }
        if (! $user) {
            $user = User::query()->where('email', $email)->first();
            if ($user && $user->google_id !== null && $user->google_id !== $providerId) {
                return redirect()->route('login')->withErrors(['email' => 'This email is linked to another Google account.']);
            }
        }

        if ($user) {
            $user->forceFill(['google_id' => $providerId, 'email_verified_at' => $user->email_verified_at ?? now()])->save();
        } else {
            $user = DB::transaction(function () use ($googleUser, $providerId, $email): User {
                $name = trim((string) ($googleUser->getName() ?: 'New User'));
                [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, '');
                $account = Account::create([
                    'name' => $name."'s Account", 'locale' => 'en', 'timezone' => 'UTC',
                ]);
                $user = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'google_id' => $providerId,
                    'password' => null,
                    'email_verified_at' => now(),
                    'role' => 'admin',
                    'account_id' => $account->id,
                ]);
                TemplateFolder::firstOrCreate(
                    ['account_id' => $account->id, 'name' => 'Default'],
                    ['author_id' => $user->id]
                );

                return $user;
            });
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('templates.index'));
    }
}
