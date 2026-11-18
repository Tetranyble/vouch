<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;

class TemplateDetectFieldsController extends Controller
{
    public function store(Request $request, string $template)
    {
        $templateModel = Template::forAccount($request->user()->account_id)->findOrFail($template);

        return response()->json([
            'template_id' => $templateModel->id,
            'status' => 'queued',
        ]);
    }
}
