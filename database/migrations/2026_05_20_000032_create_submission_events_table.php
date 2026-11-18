<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions');
            $table->foreignId('submitter_id')->nullable()->constrained('submitters');
            $table->longText('data')->nullable();
            $table->string('event_type');
            $table->timestamp('event_timestamp');
            $table->foreignId('account_id')->nullable()->constrained('accounts');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_events');
    }
};
