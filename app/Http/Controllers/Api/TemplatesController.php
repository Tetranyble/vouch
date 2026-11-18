<?php

namespace App\Http\Controllers\Api;

use App\Actions\Templates\CloneTemplateAction;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateFolder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TemplatesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Template::forAccount($user->account_id)->active()->latest();

        if ($q = $request->get('q')) {
            $query->search($q);
        }
        if ($folder = $request->get('folder')) {
            $query->whereHas('folder', fn ($q) => $q->where('name', $folder));
        }

        $templates = $query->paginate(min(100, max(1, $request->integer('per_page', 10))));

        return response()->json([
            'data' => $templates->map(fn ($t) => $this->serialize($t)),
            'pagination' => [
                'count' => $templates->total(),
                'next' => $templates->nextPageUrl(),
                'prev' => $templates->previousPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->with('author')->findOrFail($id);

        return response()->json($this->serialize($template, detailed: true));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'folder_name' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $folder = TemplateFolder::firstOrCreate(
            ['account_id' => $user->account_id, 'name' => $request->input('folder_name', 'Default')],
            ['author_id' => $user->id]
        );

        $template = Template::create([
            'name' => $request->input('name'),
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'folder_id' => $folder->id,
            'slug' => Str::random(14),
            'source' => 'api',
            'fields' => [],
            'schema' => [],
            'submitters' => [['name' => 'First Party', 'uuid' => (string) Str::uuid()]],
            'preferences' => [],
            'shared_link' => false,
        ]);

        return response()->json($this->serialize($template), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'fields' => ['sometimes', 'array'],
            'schema' => ['sometimes', 'array'],
            'submitters' => ['sometimes', 'array'],
            'preferences' => ['sometimes', 'array'],
        ]);
        $template->update($validated);

        return response()->json($this->serialize($template));
    }

    public function clone(Request $request, string $id, CloneTemplateAction $action): JsonResponse
    {
        $user = $request->user();
        $source = Template::forAccount($user->account_id)->findOrFail($id);
        $clone = $action->execute($source, $user);

        return response()->json($this->serialize($clone, detailed: true), 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($id);

        if ($request->boolean('permanently')) {
            $template->delete();
        } else {
            $template->update(['archived_at' => now()]);
        }

        return response()->json(['id' => (int) $id]);
    }

    private function serialize(Template $template, bool $detailed = false): array
    {
        $data = [
            'id' => $template->id,
            'slug' => $template->slug,
            'name' => $template->name,
            'source' => $template->source,
            'created_at' => $template->created_at,
            'updated_at' => $template->updated_at,
            'author' => $template->author ? [
                'id' => $template->author->id,
                'email' => $template->author->email,
                'name' => $template->author->full_name,
            ] : null,
        ];

        if ($detailed) {
            $data['submitters'] = $template->submitters;
            $data['fields'] = $template->fields;
            $data['schema'] = $template->schema;
            $data['preferences'] = $template->preferences;
        }

        return $data;
    }
}
