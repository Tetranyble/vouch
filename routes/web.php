<?php

use App\Http\Controllers\Admin\AutocompleteController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Settings\AccountController as SettingsAccountController;
use App\Http\Controllers\Admin\Settings\ApiTokensController;
use App\Http\Controllers\Admin\Settings\EsignController;
use App\Http\Controllers\Admin\Settings\IntegrationsController;
use App\Http\Controllers\Admin\Settings\MfaController;
use App\Http\Controllers\Admin\Settings\NotificationsController;
use App\Http\Controllers\Admin\Settings\PersonalizationController;
use App\Http\Controllers\Admin\Settings\SendPasswordResetController;
use App\Http\Controllers\Admin\Settings\SmsSettingsController;
use App\Http\Controllers\Admin\Settings\SmtpController;
use App\Http\Controllers\Admin\Settings\SsoController;
use App\Http\Controllers\Admin\Settings\StorageController;
use App\Http\Controllers\Admin\Settings\TeamsController;
use App\Http\Controllers\Admin\Settings\WebhooksController;
use App\Http\Controllers\Admin\Settings\WebhookSecretController;
use App\Http\Controllers\Admin\SubmissionsBulkController;
use App\Http\Controllers\Admin\SubmissionsController;
use App\Http\Controllers\Admin\SubmissionsExportController;
use App\Http\Controllers\Admin\SubmissionsPerTemplateExportController;
use App\Http\Controllers\Admin\SubmissionsPreviewController;
use App\Http\Controllers\Admin\SubmittersResubmitController;
use App\Http\Controllers\Admin\SubmittersSendEmailController;
use App\Http\Controllers\Admin\TemplateArchivedSubmissionsController;
use App\Http\Controllers\Admin\TemplateBuilderController;
use App\Http\Controllers\Admin\TemplateCloneController;
use App\Http\Controllers\Admin\TemplateDetectFieldsController;
use App\Http\Controllers\Admin\TemplateDocumentsController;
use App\Http\Controllers\Admin\TemplateEmbedController;
use App\Http\Controllers\Admin\TemplateFoldersController;
use App\Http\Controllers\Admin\TemplateFormPreviewController;
use App\Http\Controllers\Admin\TemplatePreferencesController;
use App\Http\Controllers\Admin\TemplatePreviewController;
use App\Http\Controllers\Admin\TemplatesController;
use App\Http\Controllers\Admin\TemplateShareController;
use App\Http\Controllers\Admin\UserSignaturesController;
use App\Http\Controllers\Admin\VerifyPdfSignatureController;
use App\Http\Controllers\Media\SignedMediaDownloadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\StartFormController;
use App\Http\Controllers\Public\SubmissionPreviewController;
use App\Http\Controllers\Public\SubmitFormAttachmentsController;
use App\Http\Controllers\Public\SubmitFormController;
use App\Http\Controllers\Public\SubmitFormDeclineController;
use App\Http\Controllers\Public\SubmitFormDownloadController;
use App\Http\Controllers\Public\SubmitFormDrawSignatureController;
use App\Http\Controllers\Public\SubmitFormEmail2faController;
use App\Http\Controllers\Public\SubmitFormInviteController;
use App\Http\Controllers\Public\SubmitFormKbaController;
use App\Http\Controllers\Public\SubmitFormSms2faController;
use App\Http\Controllers\Public\SubmitFormStripeController;
use App\Http\Controllers\Public\SubmitFormValuesController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('templates.index');
    }

    return Inertia::render('Welcome', [
        'auth' => ['user' => null],
    ]);
})->name('home');

Route::get('/privacy', fn () => Inertia::render('Legal/Privacy', ['auth' => ['user' => auth()->user()]]))->name('privacy');
Route::get('/terms', fn () => Inertia::render('Legal/Terms', ['auth' => ['user' => auth()->user()]]))->name('terms');
Route::get('/security', fn () => Inertia::render('Legal/Security', ['auth' => ['user' => auth()->user()]]))->name('security-page');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Templates
    Route::resource('templates', TemplatesController::class);
    Route::put('templates/{template}/restore', [TemplatesController::class, 'restore'])->name('templates.restore');
    Route::get('templates-archived', [TemplatesController::class, 'archived'])->name('templates.archived');
    Route::get('templates/{template}/archived-submissions', [TemplateArchivedSubmissionsController::class, 'index'])->name('templates.archived-submissions.index');
    Route::put('templates/{template}/archived-submissions/{submission}/restore', [TemplateArchivedSubmissionsController::class, 'restore'])->name('templates.archived-submissions.restore');
    Route::delete('templates/{template}/archived-submissions/{submission}', [TemplateArchivedSubmissionsController::class, 'destroy'])->name('templates.archived-submissions.destroy');

    // Submissions — static/named routes MUST come before the resource (which registers /submissions/{submission})
    Route::get('submissions-archived', [SubmissionsController::class, 'archived'])->name('submissions.archived');
    Route::get('submissions/export', [SubmissionsExportController::class, 'index'])->name('submissions.export');
    Route::get('submissions/filters', [\App\Http\Controllers\Admin\SubmissionsFiltersController::class, 'index'])->name('submissions.filters.index');
    Route::post('submissions/filters', [\App\Http\Controllers\Admin\SubmissionsFiltersController::class, 'store'])->name('submissions.filters.store');
    Route::resource('submissions', SubmissionsController::class)->only(['index', 'show', 'destroy']);
    Route::put('submissions/{submission}/restore', [SubmissionsController::class, 'restore'])->name('submissions.restore');
    Route::get('submissions/{submission}/preview', [SubmissionsPreviewController::class, 'show'])->name('submissions.preview.show');
    Route::post('templates/{template}/submissions', [SubmissionsController::class, 'store'])->name('templates.submissions.store');

    // Submitter actions
    Route::post('submitters/{submitter}/send-email', [SubmittersSendEmailController::class, 'store'])->name('submitters.send-email.store');
    Route::post('submitters/{submitter}/resubmit', [SubmittersResubmitController::class, 'store'])->name('submitters.resubmit.store');

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('integrations', [IntegrationsController::class, 'index'])->name('integrations.index');
        Route::get('account', [SettingsAccountController::class, 'index'])->name('account.index');
        Route::patch('account', [SettingsAccountController::class, 'update'])->name('account.update');
        Route::patch('account/preferences', [SettingsAccountController::class, 'updatePreferences'])->name('account.preferences.update');
        Route::post('account/users', [SettingsAccountController::class, 'inviteUser'])->name('account.users.store');
        Route::delete('account/users/{user}', [SettingsAccountController::class, 'removeUser'])->name('account.users.destroy');

        Route::get('teams', [TeamsController::class, 'index'])->name('teams.index');
        Route::post('teams/invite', [TeamsController::class, 'invite'])->name('teams.invite');
        Route::patch('teams/{user}', [TeamsController::class, 'update'])->name('teams.update');
        Route::patch('teams/{user}/role', [TeamsController::class, 'updateRole'])->name('teams.role.update');
        Route::post('teams/{user}/resend', [TeamsController::class, 'resendInvite'])->name('teams.resend');
        Route::delete('teams/{user}', [TeamsController::class, 'destroy'])->name('teams.destroy');
        Route::put('teams/{user}/unarchive', [TeamsController::class, 'unarchive'])->name('teams.unarchive');

        Route::get('api-tokens', [ApiTokensController::class, 'index'])->name('api-tokens.index');
        Route::post('api-tokens', [ApiTokensController::class, 'store'])->name('api-tokens.store');
        Route::post('api-tokens/reveal', [ApiTokensController::class, 'reveal'])->name('api-tokens.reveal');
        Route::delete('api-tokens', [ApiTokensController::class, 'destroy'])->name('api-tokens.destroy');

        Route::get('webhooks', [WebhooksController::class, 'index'])->name('webhooks.index');
        Route::post('webhooks', [WebhooksController::class, 'store'])->name('webhooks.store');
        Route::patch('webhooks/{webhook}', [WebhooksController::class, 'update'])->name('webhooks.update');
        Route::delete('webhooks/{webhook}', [WebhooksController::class, 'destroy'])->name('webhooks.destroy');
        Route::get('webhooks/{webhook}/events', [WebhooksController::class, 'events'])->name('webhooks.events');
        Route::post('webhooks/{webhook}/test', [WebhooksController::class, 'test'])->name('webhooks.test');

        Route::get('smtp', [SmtpController::class, 'index'])->name('smtp.index');
        Route::post('smtp', [SmtpController::class, 'store'])->name('smtp.store');
        Route::delete('smtp', [SmtpController::class, 'destroy'])->name('smtp.destroy');

        Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
        Route::patch('notifications', [NotificationsController::class, 'update'])->name('notifications.update');

        Route::get('personalization', [PersonalizationController::class, 'index'])->name('personalization.index');
        Route::patch('personalization', [PersonalizationController::class, 'update'])->name('personalization.update');

        Route::get('sms', [SmsSettingsController::class, 'index'])->name('sms.index');
        Route::post('sms', [SmsSettingsController::class, 'store'])->name('sms.store');
        Route::delete('sms', [SmsSettingsController::class, 'destroy'])->name('sms.destroy');

        Route::get('esign', [EsignController::class, 'index'])->name('esign.index');
        Route::post('esign', [EsignController::class, 'store'])->name('esign.store');
        Route::delete('esign', [EsignController::class, 'destroy'])->name('esign.destroy');

        Route::get('storage', [StorageController::class, 'index'])->name('storage.index');
        Route::post('storage', [StorageController::class, 'store'])->name('storage.store');

        Route::get('sso', [SsoController::class, 'index'])->name('sso.index');
        Route::post('sso', [SsoController::class, 'store'])->name('sso.store');
        Route::delete('sso', [SsoController::class, 'destroy'])->name('sso.destroy');

        Route::post('webhooks/{webhook}/secret', [WebhookSecretController::class, 'store'])->name('webhooks.secret.store');
        Route::delete('webhooks/{webhook}/secret', [WebhookSecretController::class, 'destroy'])->name('webhooks.secret.destroy');

        Route::get('mfa', [MfaController::class, 'show'])->name('mfa.show');
        Route::post('mfa', [MfaController::class, 'store'])->name('mfa.store');
        Route::delete('mfa', [MfaController::class, 'destroy'])->name('mfa.destroy');
    });

    // Template clone + embed + bulk submissions
    Route::post('templates/{template}/clone', [TemplateCloneController::class, 'store'])->name('templates.clone.store');
    Route::get('templates/{template}/embed', [TemplateEmbedController::class, 'show'])->name('templates.embed.show');
    Route::post('templates/{template}/bulk-submissions', [SubmissionsBulkController::class, 'store'])->name('templates.bulk-submissions.store');

    // Template preferences
    Route::get('templates/{template}/preferences', [TemplatePreferencesController::class, 'show'])->name('templates.preferences.show');
    Route::patch('templates/{template}/preferences', [TemplatePreferencesController::class, 'update'])->name('templates.preferences.update');

    // Per-template submissions export
    Route::get('templates/{template}/submissions/export', [SubmissionsPerTemplateExportController::class, 'index'])->name('templates.submissions.export');

    // Autocomplete
    Route::get('autocomplete/submitters', [AutocompleteController::class, 'submitters'])->name('autocomplete.submitters');
    Route::get('autocomplete/folders', [AutocompleteController::class, 'folders'])->name('autocomplete.folders');

    // Admin: send password reset to a user
    Route::post('settings/account/users/{user}/password-reset', [SendPasswordResetController::class, 'store'])->name('settings.account.users.password-reset');

    // Template folders
    Route::post('template-folders', [TemplateFoldersController::class, 'store'])->name('template-folders.store');
    Route::patch('template-folders/{folder}', [TemplateFoldersController::class, 'update'])->name('template-folders.update');
    Route::delete('template-folders/{folder}', [TemplateFoldersController::class, 'destroy'])->name('template-folders.destroy');
    Route::put('templates/{template}/move', [TemplateFoldersController::class, 'move'])->name('templates.move');

    // User signatures
    Route::post('user/signatures', [UserSignaturesController::class, 'store'])->name('user.signatures.store');
    Route::delete('user/signatures/{type}', [UserSignaturesController::class, 'destroy'])->name('user.signatures.destroy');

    // Template documents
    Route::post('templates/{template}/documents', [TemplateDocumentsController::class, 'store'])->name('templates.documents.store');
    Route::get('templates/{template}/documents/{uuid}/preview', [TemplateDocumentsController::class, 'preview'])->name('templates.documents.preview');
    Route::get('templates/{template}/documents/{uuid}/thumbnail', [TemplateDocumentsController::class, 'thumbnail'])->name('templates.documents.thumbnail');
    Route::post('templates/{template}/documents/{uuid}/thumbnail', [TemplateDocumentsController::class, 'storeThumbnail'])->name('templates.documents.thumbnail.store');
    Route::delete('templates/{template}/documents/{uuid}', [TemplateDocumentsController::class, 'destroy'])->name('templates.documents.destroy');

    // Template builder / tools
    Route::get('templates/{template}/builder', [TemplateBuilderController::class, 'show'])->name('templates.builder.show');
    Route::put('templates/{template}/builder', [TemplateBuilderController::class, 'update'])->name('templates.builder.update');
    Route::get('templates/{template}/preview', [TemplatePreviewController::class, 'show'])->name('templates.preview.show');
    Route::get('templates/{template}/form-preview', [TemplateFormPreviewController::class, 'show'])->name('templates.form-preview.show');
    Route::get('templates/{template}/share', [TemplateShareController::class, 'show'])->name('templates.share.show');
    Route::post('templates/{template}/share', [TemplateShareController::class, 'store'])->name('templates.share.store');
    Route::post('templates/{template}/detect-fields', [TemplateDetectFieldsController::class, 'store'])->name('templates.detect-fields.store');
});

Route::get('/d/{slug}', [StartFormController::class, 'show'])->name('start-form.show');
Route::put('/d/{slug}', [StartFormController::class, 'update'])->middleware('throttle:15,1')->name('start-form.update');
Route::get('/s/{slug}', [SubmitFormController::class, 'show'])->name('submit-form.show');
Route::put('/s/{slug}', [SubmitFormController::class, 'update'])->middleware('throttle:30,1')->name('submit-form.update');
Route::get('/s/{slug}/document/{attachmentUuid}', [SubmitFormController::class, 'document'])->name('submit-form.document');
Route::post('/s/{slug}/attachments', [SubmitFormAttachmentsController::class, 'store'])->middleware('throttle:30,1')->name('submit-form.attachments.store');
Route::get('/s/{slug}/attachments/{uuid}', [SubmitFormAttachmentsController::class, 'show'])->name('submit-form.attachments.show');
Route::get('/s/{slug}/values', [SubmitFormValuesController::class, 'index'])->name('submit-form.values.index');
Route::get('/s/{slug}/download', [SubmitFormDownloadController::class, 'index'])->middleware('throttle:20,1')->name('submit-form.download.index');
Route::post('/s/{slug}/decline', [SubmitFormDeclineController::class, 'store'])->middleware('throttle:10,1')->name('submit-form.decline.store');
Route::post('/s/{slug}/invite', [SubmitFormInviteController::class, 'store'])->name('submit-form.invite.store');
Route::post('/s/{slug}/email-2fa', [SubmitFormEmail2faController::class, 'store'])->middleware('throttle:5,1')->name('submit-form.email-2fa.store');
Route::put('/s/{slug}/email-2fa', [SubmitFormEmail2faController::class, 'update'])->middleware('throttle:10,1')->name('submit-form.email-2fa.update');
Route::get('/p/{slug}', [SubmitFormDrawSignatureController::class, 'show'])->name('submit-form.draw-signature.show');

// SMS 2FA
Route::post('/s/{slug}/sms-2fa', [SubmitFormSms2faController::class, 'store'])->middleware('throttle:5,1')->name('submit-form.sms-2fa.store');
Route::put('/s/{slug}/sms-2fa', [SubmitFormSms2faController::class, 'update'])->middleware('throttle:10,1')->name('submit-form.sms-2fa.update');

// KBA
Route::get('/s/{slug}/kba', [SubmitFormKbaController::class, 'show'])->name('submit-form.kba.show');
Route::post('/s/{slug}/kba', [SubmitFormKbaController::class, 'store'])->name('submit-form.kba.store');

// Stripe payment
Route::post('/s/{slug}/stripe-session', [SubmitFormStripeController::class, 'store'])->middleware('throttle:10,1')->name('submit-form.stripe.store');
Route::post('/stripe/webhook', [SubmitFormStripeController::class, 'webhook'])->name('stripe.webhook');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/manifest.json', [\App\Http\Controllers\PwaController::class, 'manifest'])->name('pwa.manifest');

// Public submission preview (completed documents)
Route::get('/e/{slug}', [SubmissionPreviewController::class, 'show'])->name('submission-preview.show');
Route::get('/e/{slug}/download/{doc_id}', [SubmissionPreviewController::class, 'download'])->name('submission-preview.download');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/verify-pdf-signature', [VerifyPdfSignatureController::class, 'show'])->name('verify-pdf-signature.show');
    Route::post('/verify-pdf-signature', [VerifyPdfSignatureController::class, 'store'])->name('verify-pdf-signature.store');
});

require __DIR__.'/auth.php';

// Time-limited bearer links for private package-managed documents.
Route::get('/media/signed/{media}', SignedMediaDownloadController::class)->whereNumber('media')->name('media.signed-download');
