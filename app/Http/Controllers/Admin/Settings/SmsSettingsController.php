<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\AccountConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmsSettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $configs = AccountConfig::where('account_id', $user->account_id)
            ->whereIn('key', ['twilio_account_sid', 'twilio_auth_token', 'twilio_from_number'])
            ->pluck('value', 'key');

        return Inertia::render('Admin/Settings/Sms', [
            'settings' => [
                'account_sid' => $configs->get('twilio_account_sid'),
                'auth_token' => $configs->get('twilio_auth_token') ? '••••••••' : '',
                'from_number' => $configs->get('twilio_from_number'),
                'configured' => (bool) $configs->get('twilio_account_sid'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'account_sid' => 'required|string',
            'auth_token' => 'required|string',
            'from_number' => 'required|string',
        ]);

        $user = $request->user();

        AccountConfig::updateOrCreate(['account_id' => $user->account_id, 'key' => 'twilio_account_sid'], ['value' => $request->account_sid]);
        AccountConfig::updateOrCreate(['account_id' => $user->account_id, 'key' => 'twilio_auth_token'], ['value' => $request->auth_token]);
        AccountConfig::updateOrCreate(['account_id' => $user->account_id, 'key' => 'twilio_from_number'], ['value' => $request->from_number]);

        return back()->with('success', 'SMS settings saved.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        AccountConfig::where('account_id', $user->account_id)
            ->whereIn('key', ['twilio_account_sid', 'twilio_auth_token', 'twilio_from_number'])
            ->delete();

        return back()->with('success', 'SMS settings removed.');
    }
}
