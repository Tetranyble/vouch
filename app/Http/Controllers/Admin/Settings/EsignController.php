<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\AccountConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EsignController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $configs = AccountConfig::where('account_id', $user->account_id)
            ->whereIn('key', ['esign_certificate', 'esign_certificate_name', 'esign_timestamp_server'])
            ->pluck('value', 'key');

        return Inertia::render('Admin/Settings/Esign', [
            'settings' => [
                'certificate_name' => $configs->get('esign_certificate_name'),
                'has_certificate' => (bool) $configs->get('esign_certificate'),
                'timestamp_server' => $configs->get('esign_timestamp_server', 'http://timestamp.sectigo.com'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'certificate' => 'required|file|mimes:p12,pfx',
            'certificate_name' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $content = base64_encode(file_get_contents($request->file('certificate')->getRealPath()));

        AccountConfig::updateOrCreate(['account_id' => $user->account_id, 'key' => 'esign_certificate'], ['value' => $content]);
        AccountConfig::updateOrCreate(['account_id' => $user->account_id, 'key' => 'esign_certificate_name'], ['value' => $request->input('certificate_name', $request->file('certificate')->getClientOriginalName())]);

        if ($request->filled('timestamp_server')) {
            AccountConfig::updateOrCreate(['account_id' => $user->account_id, 'key' => 'esign_timestamp_server'], ['value' => $request->input('timestamp_server')]);
        }

        return back()->with('success', 'Certificate uploaded successfully.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        AccountConfig::where('account_id', $user->account_id)
            ->whereIn('key', ['esign_certificate', 'esign_certificate_name'])
            ->delete();

        return back()->with('success', 'Certificate removed.');
    }
}
