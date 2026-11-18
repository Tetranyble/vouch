<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\WebhookUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookSecretController extends Controller
{
    public function store(Request $request, string $webhookId)
    {
        $user    = $request->user();
        $webhook = WebhookUrl::where('account_id', $user->account_id)->findOrFail($webhookId);

        $secret = Str::random(32);
        $webhook->update(['secret' => $secret]);

        return back()->with([
            'success'        => 'Webhook secret regenerated.',
            'webhook_secret' => $secret,
        ]);
    }

    public function destroy(Request $request, string $webhookId)
    {
        $user    = $request->user();
        $webhook = WebhookUrl::where('account_id', $user->account_id)->findOrFail($webhookId);

        $webhook->update(['secret' => null]);

        return back()->with('success', 'Webhook secret removed.');
    }
}
