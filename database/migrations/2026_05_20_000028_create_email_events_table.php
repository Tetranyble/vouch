<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('emailable_type');
            $table->unsignedBigInteger('emailable_id');
            $table->string('message_id')->index();
            $table->string('tag');
            $table->string('event_type');
            $table->string('email')->index();
            $table->longText('data');
            $table->timestamp('event_datetime');
            $table->timestamp('created_at');

            $table->index(['account_id', 'event_datetime']);
            $table->index(['emailable_type', 'emailable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_events');
    }
};
