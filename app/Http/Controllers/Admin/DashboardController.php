<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $accountId = $user->account_id;

        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => [
                'templates' => Template::forAccount($accountId)->active()->count(),
                'submissions' => Submission::forAccount($accountId)->active()->count(),
                'pending' => Submission::forAccount($accountId)->active()->pending()->count(),
                'completed' => Submission::forAccount($accountId)->active()->completed()->count(),
            ],
        ]);
    }
}
