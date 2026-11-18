<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplatePreferencesController extends Controller
{
    private const BOOLEAN_KEYS = [
        'bcc_completed', 'require_phone_2fa', 'require_email_2fa',
        'validate_unique_submitters', 'link_form_fields',
    ];

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($id);

        return Inertia::render('Admin/Templates/Preferences', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'submitters' => $template->submitters ?? [],
                'preferences' => $template->preferences ?? [],
            ],
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'preferences' => 'nullable|array',
            'preferences.submitters_order' => 'nullable|string|in:preserved,random',
            'preferences.default_expire_at_duration' => 'nullable|integer|min:1',
            'preferences.completed_redirect_url' => 'nullable|url|max:1000',
            'preferences.completed_message' => 'nullable|string|max:5000',
            'preferences.bcc_completed' => 'nullable|boolean',
            'preferences.require_email_2fa' => 'nullable|boolean',
            'preferences.require_phone_2fa' => 'nullable|boolean',
            'submitters' => 'nullable|array',
            'submitters.*.uuid' => 'required|string',
            'submitters.*.name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($id);

        $prefs = array_filter(
            $request->input('preferences', []),
            fn ($v) => $v !== null && $v !== ''
        );

        $updates = ['preferences' => array_merge($template->preferences ?? [], $prefs)];

        if ($request->has('submitters')) {
            $existing = collect($template->submitters ?? [])->keyBy('uuid');
            $updated = collect($request->input('submitters'))->map(function ($s) use ($existing) {
                $base = $existing[$s['uuid']] ?? [];

                return array_merge($base, ['name' => $s['name']]);
            })->toArray();
            $updates['submitters'] = $updated;
        }

        $template->update($updates);

        return redirect()->back()->with('success', 'Preferences saved.');
    }
}
