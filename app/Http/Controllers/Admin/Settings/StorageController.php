<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StorageController extends Controller
{
    public function index(Request $request)
    {
        $account = $request->user()->account;

        return Inertia::render('Admin/Settings/Storage', [
            'settings' => [
                'driver' => config('tetranyble-storage.default_disk', 'local'),
                'quota_bytes' => (int) $account->storage_quota_bytes,
                'used_bytes' => (int) $account->storage_used_bytes,
                'configured_centrally' => true,
            ],
        ]);
    }

    public function store(Request $request)
    {
        // Provider credentials are infrastructure configuration, not unencrypted account configs.
        // Do not claim saving a per-account driver when the package cannot apply it.
        return back()->with('error', 'Storage providers are configured centrally by an administrator.');
    }
}
