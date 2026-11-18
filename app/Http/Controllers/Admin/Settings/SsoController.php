<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\AccountConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SsoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $configs = AccountConfig::where('account_id', $user->account_id)
            ->whereIn('key', ['sso_provider', 'sso_idp_entity_id', 'sso_idp_sso_url', 'sso_idp_cert', 'sso_force'])
            ->pluck('value', 'key');

        return Inertia::render('Admin/Settings/Sso', [
            'settings' => [
                'provider' => $configs->get('sso_provider', 'saml'),
                'idp_entity_id' => $configs->get('sso_idp_entity_id'),
                'idp_sso_url' => $configs->get('sso_idp_sso_url'),
                'idp_cert' => $configs->get('sso_idp_cert') ? '••••••••' : '',
                'force' => (bool) $configs->get('sso_force', false),
                'configured' => (bool) $configs->get('sso_idp_entity_id'),
                'acs_url' => url('/sso/acs'),
                'entity_id' => url('/sso/metadata'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'idp_entity_id' => 'required|url',
            'idp_sso_url' => 'required|url',
            'idp_cert' => 'required|string',
            'force' => 'boolean',
        ]);

        $user = $request->user();

        $map = [
            'sso_provider' => 'saml',
            'sso_idp_entity_id' => $request->idp_entity_id,
            'sso_idp_sso_url' => $request->idp_sso_url,
            'sso_idp_cert' => $request->idp_cert,
            'sso_force' => $request->boolean('force'),
        ];

        foreach ($map as $key => $value) {
            AccountConfig::updateOrCreate(
                ['account_id' => $user->account_id, 'key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'SSO configuration saved.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        AccountConfig::where('account_id', $user->account_id)
            ->whereIn('key', ['sso_provider', 'sso_idp_entity_id', 'sso_idp_sso_url', 'sso_idp_cert', 'sso_force'])
            ->delete();

        return back()->with('success', 'SSO configuration removed.');
    }
}
