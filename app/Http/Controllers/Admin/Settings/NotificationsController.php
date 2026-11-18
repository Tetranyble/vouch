<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\AccountConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationsController extends Controller
{
    private const KEYS = [
        'send_completion_email', 'send_reminder_email', 'reminder_after_days',
        'bcc_emails', 'completion_redirect_url', 'allow_decline',
    ];

    public function index(Request $request)
    {
        $user     = $request->user();
        $configs  = AccountConfig::where('account_id', $user->account_id)
            ->whereIn('key', self::KEYS)
            ->pluck('value', 'key');

        return Inertia::render('Admin/Settings/Notifications', [
            'settings' => $configs->toArray(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'send_completion_email'  => 'boolean',
            'send_reminder_email'    => 'boolean',
            'reminder_after_days'    => 'nullable|integer|min:1|max:30',
            'bcc_emails'             => 'nullable|string|max:1000',
            'completion_redirect_url'=> 'nullable|url',
            'allow_decline'          => 'boolean',
        ]);

        foreach (self::KEYS as $key) {
            if ($request->has($key)) {
                AccountConfig::updateOrCreate(
                    ['account_id' => $user->account_id, 'key' => $key],
                    ['value' => $request->input($key)]
                );
            }
        }

        return back()->with('success', 'Notification settings saved.');
    }
}
