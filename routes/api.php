<?php

use App\Http\Controllers\Api\AttachmentsController;
use App\Http\Controllers\Api\FormEventsController;
use App\Http\Controllers\Api\SubmissionDocumentsController;
use App\Http\Controllers\Api\SubmissionsController;
use App\Http\Controllers\Api\SubmittersController;
use App\Http\Controllers\Api\TemplatesController;
use App\Http\Controllers\Api\ToolsController;
use App\Http\Controllers\Api\UsersController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->name('api.')->group(function () {
    // Templates
    Route::apiResource('templates', TemplatesController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::post('templates/{template}/clone', [TemplatesController::class, 'clone'])->name('templates.clone');
    Route::post('templates/{template}/submissions', [SubmissionsController::class, 'storeForTemplate'])
        ->name('templates.submissions.store');

    // Submissions
    Route::apiResource('submissions', SubmissionsController::class)->only(['index', 'show', 'store', 'destroy']);
    Route::get('submissions/{submission}/documents', [SubmissionDocumentsController::class, 'index'])
        ->name('submissions.documents.index');

    // Attachments (file uploads during signing)
    Route::post('attachments', [AttachmentsController::class, 'store'])->name('attachments.store');

    // Submitters
    Route::apiResource('submitters', SubmittersController::class)->only(['index', 'show', 'update']);

    // Form events
    Route::get('form_events', [FormEventsController::class, 'index'])->name('form_events.index');

    // Users
    Route::get('users/me', [UsersController::class, 'me'])->name('users.me');
    Route::get('users', [UsersController::class, 'index'])->name('users.index');

    // Tools
    Route::post('tools/merge', [ToolsController::class, 'merge'])->name('tools.merge');
    Route::post('tools/verify', [ToolsController::class, 'verify'])->name('tools.verify');
});
