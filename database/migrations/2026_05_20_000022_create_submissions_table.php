<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->nullable()->constrained('templates');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users');
            $table->timestamp('archived_at')->nullable();
            $table->longText('template_fields')->nullable();
            $table->longText('template_schema')->nullable();
            $table->longText('template_submitters')->nullable();
            $table->string('source')->default('api');
            $table->string('submitters_order')->default('random');
            $table->string('slug')->unique();
            $table->longText('preferences')->nullable();
            $table->foreignId('account_id')->constrained('accounts');
            $table->timestamp('expire_at')->nullable();
            $table->longText('name')->nullable();
            $table->longText('variables_schema')->nullable();
            $table->longText('variables')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'id']);
            $table->index(['account_id', 'template_id', 'id'], 'index_submissions_account_template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
