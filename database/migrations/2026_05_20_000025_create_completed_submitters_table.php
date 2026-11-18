<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('completed_submitters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitter_id')->unique()->constrained('submitters');
            $table->foreignId('submission_id')->constrained('submissions');
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('template_id')->nullable()->constrained('templates');
            $table->string('source');
            $table->integer('sms_count');
            $table->timestamp('completed_at');
            $table->string('verification_method')->nullable();
            $table->boolean('is_first')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('completed_submitters');
    }
};
