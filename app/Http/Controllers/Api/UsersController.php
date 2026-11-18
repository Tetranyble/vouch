<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsersController extends ApiBaseController
{
    public function me(Request $request): JsonResponse
    {
        $user    = $request->user();
        $account = $user->account;

        return response()->json([
            'id'         => $user->id,
            'email'      => $user->email,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'name'       => trim($user->first_name . ' ' . $user->last_name) ?: $user->email,
            'created_at' => $user->created_at,
            'account'    => $account ? [
                'id'   => $account->id,
                'name' => $account->name,
            ] : null,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $users = $user->account->users()->get()->map(fn ($u) => [
            'id'         => $u->id,
            'email'      => $u->email,
            'first_name' => $u->first_name,
            'last_name'  => $u->last_name,
            'name'       => trim($u->first_name . ' ' . $u->last_name) ?: $u->email,
            'created_at' => $u->created_at,
        ]);

        return response()->json(['data' => $users]);
    }
}
