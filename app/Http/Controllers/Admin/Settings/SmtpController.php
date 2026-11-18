<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\AccountConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmtpController extends Controller
{
    private const SMTP_KEY = 'email_smtp';

    public function index(Request $request)
    {
        $user = $request->user();
        $config = AccountConfig::where('account_id', $user->account_id)
            ->where('key', self::SMTP_KEY)
            ->first();

        $settings = $config ? ($config->value ?? []) : [];

        return Inertia::render('Admin/Settings/Smtp', [
            'settings' => [
                'host' => $settings['host'] ?? '',
                'port' => $settings['port'] ?? 587,
                'username' => $settings['username'] ?? '',
                'password' => $settings['password'] ?? '',
                'from_email' => $settings['from_email'] ?? '',
                'from_name' => $settings['from_name'] ?? '',
                'encryption' => $settings['encryption'] ?? 'tls',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'from_email' => 'required|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'encryption' => 'required|in:tls,ssl,none',
        ]);

        $user = $request->user();
        AccountConfig::updateOrCreate(
            ['account_id' => $user->account_id, 'key' => self::SMTP_KEY],
            ['value' => $request->only('host', 'port', 'username', 'password', 'from_email', 'from_name', 'encryption')]
        );

        return redirect()->route('settings.smtp.index')
            ->with('success', 'SMTP settings saved.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        AccountConfig::where('account_id', $user->account_id)
            ->where('key', self::SMTP_KEY)
            ->delete();

        return redirect()->route('settings.smtp.index')
            ->with('success', 'SMTP settings cleared. Default mail driver will be used.');
    }
}
