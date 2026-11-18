<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->index();
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('account_id')->constrained('accounts');
            $table->longText('subject');
            $table->longText('body');
            $table->string('sha1')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
