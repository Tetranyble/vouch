<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\User;
use App\Models\UserConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionsFiltersController extends Controller
{
    private const CONFIG_KEY = 'submissions_filters';

    private const ALLOWED_FILTERS = ['status', 'template_id', 'author_id', 'q'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Saved filter preferences for this user
        $saved = UserConfig::where('user_id', $user->id)
            ->where('key', self::CONFIG_KEY)
            ->value('value') ?? [];

        // Templates the user can filter by
        $templates = Template::forAccount($user->account_id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name]);

        // Authors/users in the same account
        $authors = User::where('account_id', $user->account_id)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->map(fn ($u) => [
                'id'   => $u->id,
                'name' => trim("{$u->first_name} {$u->last_name}") ?: $u->email,
            ]);

        return response()->json([
            'saved'     => $saved,
            'templates' => $templates,
            'authors'   => $authors,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'filters'            => 'required|array',
            'filters.*'          => 'nullable|string',
        ]);

        $filters = collect($request->input('filters'))
            ->only(self::ALLOWED_FILTERS)
            ->filter()
            ->all();

        $user = $request->user();

        UserConfig::updateOrCreate(
            ['user_id' => $user->id, 'key' => self::CONFIG_KEY],
            ['value' => $filters],
        );

        return response()->json(['saved' => $filters]);
    }
}
