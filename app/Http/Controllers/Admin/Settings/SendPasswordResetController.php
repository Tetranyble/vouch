<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class SendPasswordResetController extends Controller
{
    public function store(Request $request, int $userId)
    {
        $currentUser = $request->user();

        $target = User::where('account_id', $currentUser->account_id)
            ->findOrFail($userId);

        Password::sendResetLink(['email' => $target->email]);

        return redirect()->back()->with('success', "Password reset email sent to {$target->email}.");
    }
}
