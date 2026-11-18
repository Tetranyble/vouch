<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Composite indexes for the scoping and sorting patterns used by admin lists.
        Schema::table('templates', function (Blueprint $table): void {
            $table->index(['account_id', 'archived_at', 'updated_at'], 'templates_account_archive_recent_idx');
            $table->index(['account_id', 'folder_id', 'archived_at'], 'templates_account_folder_active_idx');
        });
        Schema::table('submissions', function (Blueprint $table): void {
            $table->index(['account_id', 'archived_at', 'created_at'], 'submissions_account_archive_recent_idx');
            $table->index(['expire_at', 'archived_at'], 'submissions_expiry_idx');
        });
        Schema::table('submitters', function (Blueprint $table): void {
            $table->index(['account_id', 'submission_id', 'completed_at'], 'submitters_account_submission_completion_idx');
            $table->index(['submission_id', 'order'], 'submitters_submission_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('submitters', fn (Blueprint $table) => $table->dropIndex('submitters_account_submission_completion_idx'));
        Schema::table('submitters', fn (Blueprint $table) => $table->dropIndex('submitters_submission_order_idx'));
        Schema::table('submissions', fn (Blueprint $table) => $table->dropIndex('submissions_account_archive_recent_idx'));
        Schema::table('submissions', fn (Blueprint $table) => $table->dropIndex('submissions_expiry_idx'));
        Schema::table('templates', fn (Blueprint $table) => $table->dropIndex('templates_account_archive_recent_idx'));
        Schema::table('templates', fn (Blueprint $table) => $table->dropIndex('templates_account_folder_active_idx'));
    }
};
