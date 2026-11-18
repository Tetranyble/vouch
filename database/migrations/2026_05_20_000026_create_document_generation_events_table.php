<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_generation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitter_id')->constrained('submitters');
            $table->string('event_name');
            $table->timestamps();

            $table->unique(['submitter_id', 'event_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_generation_events');
    }
};
