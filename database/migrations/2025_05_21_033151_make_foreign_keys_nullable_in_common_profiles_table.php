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
            // Drop foreign key constraints first


            $table->dropForeign(['common_speciality_id']);

        });

        Schema::table('common_profiles', function (Blueprint $table) {
            // Alter columns to be nullable

            $table->unsignedBigInteger('common_speciality_id')->nullable()->change();
          
        });

        Schema::table('common_profiles', function (Blueprint $table) {
            // Re-add foreign key constraints


            $table->foreign('common_speciality_id')->references('id')->on('common_provider_specialities')->onDelete('cascade');
          
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       

        Schema::table('common_profiles', function (Blueprint $table) {


            $table->dropForeign(['common_speciality_id']);
        
        });

        Schema::table('common_profiles', function (Blueprint $table) {

            $table->unsignedBigInteger('common_speciality_id')->nullable(false)->change();
          
        });

        Schema::table('common_profiles', function (Blueprint $table) {


            $table->foreign('common_speciality_id')->references('id')->on('common_provider_specialities')->onDelete('cascade');
           
        });
    }
};