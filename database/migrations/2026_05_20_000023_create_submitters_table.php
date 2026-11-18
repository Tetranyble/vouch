<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submitters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions');
            $table->string('uuid');
            $table->string('email')->nullable()->index();
            $table->string('slug')->unique();
            $table->longText('values')->nullable();
            $table->string('ua')->nullable();
            $table->string('ip')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->longText('preferences')->nullable();
            $table->longText('metadata')->nullable();
            $table->foreignId('account_id')->constrained('accounts');
            $table->timestamp('declined_at')->nullable();
            $table->string('timezone')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->index(['account_id', 'id']);
            $table->index(['completed_at', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submitters');
    }
};
