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
        Schema::table('common_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('common_speciality_id');
            $table->foreign('common_speciality_id')->references('id')->on('common_provider_specialities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('common_profiles', function (Blueprint $table) {
            $table->dropColumn('common_speciality_id');
        });
    }
};