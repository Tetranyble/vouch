<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplateShareController extends Controller
{
    public function show(Request $request, string $template)
    {
        $templateModel = Template::forAccount($request->user()->account_id)->findOrFail($template);

        return Inertia::render('Admin/Templates/Share', [
            'templateId' => $templateModel->id,
            'template' => $templateModel,
        ]);
    }

    public function store(Request $request, string $template)
    {
        $templateModel = Template::forAccount($request->user()->account_id)->findOrFail($template);
        $templateModel->shared_link = true;
        $templateModel->save();

        return redirect()->route('templates.share.show', $template);
    }
}
