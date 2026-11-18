<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplateEmbedController extends Controller
{
    public function show(Request $request, string $id)
    {
        $user     = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($id);

        $shareUrl = route('start-form.show', $template->slug);

        return Inertia::render('Admin/Templates/Embed', [
            'template' => [
                'id'   => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
            ],
            'shareUrl' => $shareUrl,
        ]);
    }
}
