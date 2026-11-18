<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->longText('schema')->nullable();
            $table->longText('fields')->nullable();
            $table->longText('submitters')->nullable();
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('account_id')->constrained('accounts');
            $table->timestamp('archived_at')->nullable();
            $table->string('source')->default('native');
            $table->foreignId('folder_id')->constrained('template_folders');
            $table->string('external_id')->nullable()->index();
            $table->longText('preferences')->nullable();
            $table->boolean('shared_link')->default(false);
            $table->longText('variables_schema')->nullable();
            $table->longText('documents')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
