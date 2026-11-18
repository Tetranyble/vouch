<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

abstract class ApiBaseController extends Controller
{
    protected function currentAccount(Request $request): Account
    {
        return $request->user()->account ?? abort(403, 'Account unavailable.');
    }

    protected function paginate($query, Request $request, int $default = 10): array
    {
        $limit = min((int) ($request->query('limit', $default)), 100);
        $after = $request->query('after');
        $before = $request->query('before');

        if ($after) {
            $query->where('id', '>', (int) $after);
        } elseif ($before) {
            $query->where('id', '<', (int) $before)->orderBy('id', 'desc');
            $items = $query->limit($limit)->get()->reverse()->values();

            return [
                'data' => $items,
                'pagination' => [
                    'count' => $items->count(),
                    'next' => $items->last()?->id,
                    'prev' => $items->first()?->id,
                ],
            ];
        }

        $items = $query->orderBy('id', 'desc')->limit($limit)->get();

        return [
            'data' => $items,
            'pagination' => [
                'count' => $items->count(),
                'next' => $items->last()?->id,
                'prev' => $items->first()?->id,
            ],
        ];
    }
}
