<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->unsignedBigInteger('storage_quota_bytes')->default(10737418240);
            $table->unsignedBigInteger('storage_used_bytes')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('accounts', fn (Blueprint $table) => $table->dropColumn(['storage_quota_bytes', 'storage_used_bytes']));
    }
};
