<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Templates\CloneTemplateAction;
use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;

class TemplateCloneController extends Controller
{
    public function store(Request $request, string $id, CloneTemplateAction $action)
    {
        $source = Template::forAccount($request->user()->account_id)->findOrFail($id);
        $clone  = $action->execute($source, $request->user());

        return redirect()->route('templates.builder.show', $clone->id)
            ->with('success', 'Template cloned successfully.');
    }
}
