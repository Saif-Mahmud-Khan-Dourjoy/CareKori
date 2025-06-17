<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('add_banners', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()
                ->constrained('banner_categories')
                ->cascadeOnDelete()
                ->after('add_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('add_banners', function (Blueprint $table) {
            $table->dropForeign(['category_id']); // drops the ForeignKey first
            $table->dropColumn('category_id'); // then drops the column
        });
    }
};