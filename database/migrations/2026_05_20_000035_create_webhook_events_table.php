<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->foreignId('webhook_url_id')->constrained('webhook_urls');
            $table->foreignId('account_id')->constrained('accounts');
            $table->unsignedBigInteger('record_id');
            $table->string('record_type');
            $table->string('event_type');
            $table->string('status');
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->timestamps();

            $table->unique(['uuid', 'webhook_url_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
