<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_entries', function (Blueprint $table) {
            $table->id();
            $table->string('record_type');
            $table->unsignedBigInteger('record_id');
            $table->foreignId('account_id')->constrained('accounts');
            $table->longText('tsvector');
            $table->longText('ngram')->nullable();
            $table->timestamps();

            $table->unique(['record_id', 'record_type']);

            // SQLite (used in tests) does not support fulltext indexes
            if (config('database.default') !== 'sqlite') {
                $table->fullText('tsvector');
                $table->fullText('ngram');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_entries');
    }
};
