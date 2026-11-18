<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserConfig;
use Illuminate\Http\Request;

class UserSignaturesController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'signature' => 'nullable|string',
            'initials' => 'nullable|string',
        ]);

        $user = $request->user();

        if ($request->has('signature')) {
            UserConfig::updateOrCreate(
                ['user_id' => $user->id, 'key' => 'signature'],
                ['value' => $request->input('signature')]
            );
        }

        if ($request->has('initials')) {
            UserConfig::updateOrCreate(
                ['user_id' => $user->id, 'key' => 'initials'],
                ['value' => $request->input('initials')]
            );
        }

        return back()->with('success', 'Signature saved.');
    }

    public function destroy(Request $request, string $type)
    {
        $user = $request->user();
        UserConfig::where('user_id', $user->id)->where('key', $type)->delete();

        return back()->with('success', ucfirst($type).' removed.');
    }
}
