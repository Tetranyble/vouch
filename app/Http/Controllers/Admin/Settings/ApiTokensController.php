<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiTokensController extends Controller
{
    public function index(Request $request)
    {
        $token = $request->user()->tokens()->where('name', 'vouch-api')->latest('id')->first();

        return Inertia::render('Admin/Settings/ApiTokens', [
            'token' => $token ? [
                'id' => $token->id,
                'masked' => '••••••••••••••••••••••••••••••••',
                'created_at' => $token->created_at,
                'updated_at' => $token->updated_at,
            ] : null,
            'newToken' => $request->session()->pull('new_api_token'),
        ]);
    }

    public function reveal()
    {
        return response()->json(['message' => 'Tokens are encrypted one-way and cannot be revealed. Regenerate to see a new token.'], 410);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $user->tokens()->where('name', 'vouch-api')->delete();
        $raw = $user->createToken('vouch-api')->plainTextToken;

        // The plaintext token exists only for this immediate one-time display.
        return redirect()->route('settings.api-tokens.index')
            ->with('new_api_token', $raw)
            ->with('success', 'Copy your new API token now; it will not be shown again.');
    }

    public function destroy(Request $request)
    {
        $request->user()->tokens()->where('name', 'vouch-api')->delete();

        return redirect()->route('settings.api-tokens.index')->with('success', 'API token revoked.');
    }
}
