<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;
use OTPHP\TOTP;

class MfaController extends Controller
{
    public function show(Request $request)
    {
        $user    = $request->user();
        $enabled = (bool) UserConfig::where('user_id', $user->id)
            ->where('key', 'totp_secret')
            ->value('value');

        $qrCodeUrl = null;
        $secret    = null;

        if (!$enabled) {
            $pending = UserConfig::where('user_id', $user->id)
                ->where('key', 'totp_secret_pending')
                ->value('value');

            if (!$pending) {
                $totp = TOTP::generate();
                $totp->setLabel($user->email);
                $totp->setIssuer(config('app.name', 'Vouch'));

                $pending = $totp->getSecret();

                UserConfig::updateOrCreate(
                    ['user_id' => $user->id, 'key' => 'totp_secret_pending'],
                    ['value' => $pending]
                );
            } else {
                $totp = TOTP::createFromSecret($pending);
                $totp->setLabel($user->email);
                $totp->setIssuer(config('app.name', 'Vouch'));
            }

            $secret    = $pending;
            $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($totp->getProvisioningUri());
        }

        return Inertia::render('Admin/Settings/Mfa', [
            'mfa'       => ['enabled' => $enabled],
            'qrCodeUrl' => $qrCodeUrl,
            'secret'    => $secret,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $user    = $request->user();
        $pending = UserConfig::where('user_id', $user->id)
            ->where('key', 'totp_secret_pending')
            ->first();

        if (!$pending) {
            return back()->withErrors(['code' => 'No pending MFA setup found. Please reload the page.']);
        }

        $totp = TOTP::createFromSecret($pending->value);

        if (!$totp->verify($request->input('code'), null, 1)) {
            return back()->withErrors(['code' => 'Invalid code. Check your authenticator app and try again.']);
        }

        UserConfig::updateOrCreate(
            ['user_id' => $user->id, 'key' => 'totp_secret'],
            ['value' => $pending->value]
        );

        $pending->delete();

        return redirect()->route('settings.mfa.show')->with('success', 'Two-factor authentication enabled.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        UserConfig::where('user_id', $user->id)
            ->whereIn('key', ['totp_secret', 'totp_secret_pending'])
            ->delete();

        return redirect()->route('settings.mfa.show')->with('success', 'Two-factor authentication disabled.');
    }
}
