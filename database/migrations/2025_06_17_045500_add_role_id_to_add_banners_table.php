<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('add_banners', function (Blueprint $table) {
            // Add role_id normally
            $table->foreignId('role_id')->nullable()
                ->constrained('roles')
                ->cascadeOnDelete();
        });

        // Move it to correct position using raw SQL
        DB::statement('ALTER TABLE add_banners MODIFY role_id BIGINT UNSIGNED NULL AFTER id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('add_banners', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};