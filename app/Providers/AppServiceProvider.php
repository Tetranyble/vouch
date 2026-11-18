<?php

namespace App\Providers;

use App\Models\Submission;
use App\Models\Submitter;
use App\Models\Template;
use App\Models\TemplateFolder;
use App\Models\User;
use App\Models\WebhookUrl;
use App\Policies\SubmissionPolicy;
use App\Policies\SubmitterPolicy;
use App\Policies\TemplatePolicy;
use App\Policies\TemplateFolderPolicy;
use App\Policies\UserPolicy;
use App\Policies\WebhookUrlPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Laravel 12 defers MailServiceProvider, so the 'mail' view namespace is
        // never registered at boot. Register it eagerly so Blade can compile mail templates.
        $mailViewPaths = array_filter([
            resource_path('views/vendor/mail/html'),
            base_path('vendor/laravel/framework/src/Illuminate/Mail/resources/views/html'),
        ], 'is_dir');
        if ($mailViewPaths) {
            View::addNamespace('mail', array_values($mailViewPaths));
        }

        Gate::policy(Template::class, TemplatePolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);
        Gate::policy(Submitter::class, SubmitterPolicy::class);
        Gate::policy(TemplateFolder::class, TemplateFolderPolicy::class);
        Gate::policy(WebhookUrl::class, WebhookUrlPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
