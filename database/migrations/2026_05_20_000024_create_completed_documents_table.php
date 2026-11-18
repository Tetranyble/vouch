<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('completed_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitter_id')->constrained('submitters');
            $table->string('sha256')->index();
            $table->string('filename')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('attachment_uuid')->nullable();
            $table->unsignedBigInteger('filesize')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('completed_documents');
    }
};
