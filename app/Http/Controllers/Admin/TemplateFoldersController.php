<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateFolder;
use Illuminate\Http\Request;

class TemplateFoldersController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $user = $request->user();

        $folder = TemplateFolder::create([
            'name'       => $request->input('name'),
            'account_id' => $user->account_id,
            'author_id'  => $user->id,
        ]);

        return back()->with('success', 'Folder created.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $user = $request->user();

        $folder = TemplateFolder::where('account_id', $user->account_id)
            ->where('name', '!=', 'Default')
            ->findOrFail($id);

        $folder->update(['name' => $request->input('name')]);

        return back()->with('success', 'Folder renamed.');
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $folder = TemplateFolder::where('account_id', $user->account_id)
            ->where('name', '!=', 'Default')
            ->findOrFail($id);

        // Move templates to Default folder
        $default = TemplateFolder::firstOrCreate(
            ['account_id' => $user->account_id, 'name' => 'Default'],
            ['author_id' => $user->id]
        );
        Template::where('folder_id', $folder->id)->update(['folder_id' => $default->id]);

        $folder->delete();

        return back()->with('success', 'Folder deleted. Templates moved to Default.');
    }

    public function move(Request $request, string $templateId)
    {
        $request->validate(['folder_id' => 'required|integer']);
        $user = $request->user();

        $template = Template::forAccount($user->account_id)->findOrFail($templateId);
        $folder = TemplateFolder::where('account_id', $user->account_id)
            ->findOrFail($request->input('folder_id'));

        $template->update(['folder_id' => $folder->id]);

        return back()->with('success', 'Template moved.');
    }
}
