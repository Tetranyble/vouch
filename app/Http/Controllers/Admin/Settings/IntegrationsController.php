<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\AccountConfig;
use App\Models\WebhookUrl;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IntegrationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $accountId = $user->account_id;

        // Single query for all account-level config keys we care about
        $configs = AccountConfig::where('account_id', $accountId)
            ->whereIn('key', [
                'email_smtp',
                'twilio_account_sid',
                'storage_driver',
                'esign_certificate',
                'esign_certificate_name',
            ])
            ->pluck('value', 'key');

        // SMTP — stored as array with 'host', 'port', etc.
        $smtp = $configs->get('email_smtp');
        $smtpHost = is_array($smtp) ? ($smtp['host'] ?? null) : null;

        // Twilio — configured when account SID is present
        $twilioSid = $configs->get('twilio_account_sid');

        // Storage — 'local' is the unconfigured default
        $storageDriver = config('tetranyble-storage.default_disk', 'local');

        // E-Sign — configured when a certificate exists
        $esignName = $configs->get('esign_certificate_name');
        $hasEsign = (bool) $configs->get('esign_certificate');

        // Webhooks — count of active endpoints
        $webhookCount = WebhookUrl::where('account_id', $accountId)->count();

        // API token — per-user
        $hasApiToken = $user->tokens()->where('name', 'vouch-api')->exists();

        return Inertia::render('Admin/Settings/Integrations', [
            'integrations' => [
                'api' => [
                    'configured' => $hasApiToken,
                ],
                'webhooks' => [
                    'count' => $webhookCount,
                    'configured' => $webhookCount > 0,
                ],
                'smtp' => [
                    'configured' => (bool) $smtpHost,
                    'host' => $smtpHost,
                ],
                'sms' => [
                    'configured' => (bool) $twilioSid,
                ],
                'storage' => [
                    'driver' => $storageDriver,
                    'configured' => $storageDriver !== 'local',
                ],
                'esign' => [
                    'configured' => $hasEsign,
                    'name' => $esignName,
                ],
            ],
        ]);
    }
}
