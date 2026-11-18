<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\AccountConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PersonalizationController extends Controller
{
    private const KEYS = [
        'logo', 'primary_color', 'custom_css', 'completed_message',
        'completed_button_label', 'completed_button_url',
    ];

    public function index(Request $request)
    {
        $user    = $request->user();
        $configs = AccountConfig::where('account_id', $user->account_id)
            ->whereIn('key', self::KEYS)
            ->pluck('value', 'key');

        return Inertia::render('Admin/Settings/Personalization', [
            'settings' => $configs->toArray(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'logo'                   => 'nullable|string',
            'primary_color'          => 'nullable|string|max:7',
            'custom_css'             => 'nullable|string|max:10000',
            'completed_message'      => 'nullable|string|max:1000',
            'completed_button_label' => 'nullable|string|max:100',
            'completed_button_url'   => 'nullable|url',
        ]);

        foreach (self::KEYS as $key) {
            if ($request->has($key)) {
                AccountConfig::updateOrCreate(
                    ['account_id' => $user->account_id, 'key' => $key],
                    ['value' => $request->input($key)]
                );
            }
        }

        return back()->with('success', 'Personalization settings saved.');
    }
}
