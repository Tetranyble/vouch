<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_sharings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('templates');
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('ability');
            $table->timestamps();

            $table->unique(['account_id', 'template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_sharings');
    }
};
