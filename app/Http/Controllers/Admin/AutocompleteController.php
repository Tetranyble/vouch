<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submitter;
use App\Models\TemplateFolder;
use Illuminate\Http\Request;

class AutocompleteController extends Controller
{
    public function submitters(Request $request)
    {
        $user  = $request->user();
        $q     = $request->query('q', '');
        $limit = min((int) $request->query('limit', 10), 50);

        $results = Submitter::where('account_id', $user->account_id)
            ->where(function ($query) use ($q) {
                $query->where('email', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            })
            ->whereNotNull('email')
            ->select('id', 'name', 'email')
            ->distinct('email')
            ->orderBy('email')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'id'    => $s->id,
                'name'  => $s->name,
                'email' => $s->email,
            ]);

        return response()->json($results);
    }

    public function folders(Request $request)
    {
        $user  = $request->user();
        $q     = $request->query('q', '');
        $limit = min((int) $request->query('limit', 20), 50);

        $results = TemplateFolder::where('account_id', $user->account_id)
            ->where('name', 'like', "%{$q}%")
            ->select('id', 'name')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn ($f) => ['id' => $f->id, 'name' => $f->name]);

        return response()->json($results);
    }
}
