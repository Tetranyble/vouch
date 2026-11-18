<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\SendTestWebhookJob;
use App\Models\Submitter;
use App\Models\WebhookUrl;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WebhooksController extends Controller
{
    public const AVAILABLE_EVENTS = [
        'form.viewed',
        'form.started',
        'form.completed',
        'form.declined',
        'submission.created',
        'submission.completed',
        'submission.expired',
        'submission.archived',
        'template.created',
        'template.updated',
        'template.archived',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $webhooks = WebhookUrl::where('account_id', $user->account_id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($w) => $this->serializeWebhook($w));

        return Inertia::render('Admin/Settings/Webhooks', [
            'webhooks' => $webhooks,
            'available_events' => self::AVAILABLE_EVENTS,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url|max:1000',
            'events' => 'nullable|array',
            'events.*' => 'string|in:' . implode(',', self::AVAILABLE_EVENTS),
        ]);

        $user = $request->user();
        WebhookUrl::create([
            'account_id' => $user->account_id,
            'url' => $request->input('url'),
            'events' => $request->input('events', []),
            'secret' => Str::random(32),
        ]);

        return redirect()->route('settings.webhooks.index')
            ->with('success', 'Webhook URL saved.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'url' => 'required|url|max:1000',
            'events' => 'nullable|array',
            'events.*' => 'string|in:' . implode(',', self::AVAILABLE_EVENTS),
        ]);

        $user = $request->user();
        $webhook = WebhookUrl::where('account_id', $user->account_id)->findOrFail($id);
        $webhook->update([
            'url' => $request->input('url'),
            'events' => $request->input('events', []),
        ]);

        return redirect()->route('settings.webhooks.index')
            ->with('success', 'Webhook URL updated.');
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $webhook = WebhookUrl::where('account_id', $user->account_id)->findOrFail($id);
        $webhook->delete();

        return redirect()->route('settings.webhooks.index')
            ->with('success', 'Webhook URL deleted.');
    }

    public function test(Request $request, string $id)
    {
        $user = $request->user();
        $webhook = WebhookUrl::where('account_id', $user->account_id)->findOrFail($id);

        $submitter = Submitter::where('account_id', $user->account_id)
            ->whereNotNull('completed_at')
            ->orderByDesc('id')
            ->first();

        if (!$submitter) {
            return redirect()->back()->with('error', 'Complete at least one submission first to generate a test payload.');
        }

        SendTestWebhookJob::dispatch($webhook->id, $submitter->id);

        return redirect()->back()->with('success', 'Test webhook request queued.');
    }

    public function events(Request $request, string $id)
    {
        $user = $request->user();
        $webhook = WebhookUrl::where('account_id', $user->account_id)->findOrFail($id);

        $eventsQuery = WebhookEvent::where('webhook_url_id', $webhook->id)->orderByDesc('id');

        if ($status = $request->get('status')) {
            $eventsQuery->where('status', $status);
        }

        $events = $eventsQuery->paginate(20)->through(fn ($e) => [
            'id' => $e->id,
            'event_type' => $e->event_type,
            'status' => $e->status,
            'response_code' => $e->response_code,
            'created_at' => $e->created_at,
        ]);

        return Inertia::render('Admin/Settings/WebhookEvents', [
            'webhook' => $this->serializeWebhook($webhook),
            'events' => $events,
            'filters' => ['status' => $request->get('status', '')],
        ]);
    }

    private function serializeWebhook(WebhookUrl $w): array
    {
        return [
            'id' => $w->id,
            'url' => $w->url,
            'events' => $w->events ?? [],
            'secret' => $w->secret,
            'created_at' => $w->created_at,
        ];
    }
}
