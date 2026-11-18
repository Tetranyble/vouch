<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('account_id')->constrained('accounts');
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('parent_folder_id')->nullable()->constrained('template_folders');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_folders');
    }
};
