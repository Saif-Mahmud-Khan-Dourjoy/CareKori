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
      
            Schema::table('doctor_profiles', function (Blueprint $table) {
                // Drop foreign key constraints first
               
                $table->dropForeign(['doctor_type_id']);
                $table->dropForeign(['doctor_speciality_id']);
                $table->dropForeign(['doctor_title_id']);
            });

            Schema::table('doctor_profiles', function (Blueprint $table) {
                // Alter columns to be nullable
               
                $table->unsignedBigInteger('doctor_type_id')->nullable()->change();
                $table->unsignedBigInteger('doctor_speciality_id')->nullable()->change();
                $table->unsignedBigInteger('doctor_title_id')->nullable()->change();
            });

            Schema::table('doctor_profiles', function (Blueprint $table) {
                // Re-add foreign key constraints
                
                $table->foreign('doctor_type_id')->references('id')->on('doctor_types')->onDelete('cascade');
                $table->foreign('doctor_speciality_id')->references('id')->on('doctor_specialities')->onDelete('cascade');
                $table->foreign('doctor_title_id')->references('id')->on('doctor_titles')->onDelete('cascade');
            });
       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       
            Schema::table('doctor_profiles', function (Blueprint $table) {
              
                $table->dropForeign(['doctor_type_id']);
                $table->dropForeign(['doctor_speciality_id']);
                $table->dropForeign(['doctor_title_id']);
            });

            Schema::table('doctor_profiles', function (Blueprint $table) {
       
                $table->unsignedBigInteger('doctor_type_id')->nullable(false)->change();
                $table->unsignedBigInteger('doctor_speciality_id')->nullable(false)->change();
                $table->unsignedBigInteger('doctor_title_id')->nullable(false)->change();
            });

            Schema::table('doctor_profiles', function (Blueprint $table) {
              
                $table->foreign('doctor_type_id')->references('id')->on('doctor_types')->onDelete('cascade');
                $table->foreign('doctor_speciality_id')->references('id')->on('doctor_specialities')->onDelete('cascade');
                $table->foreign('doctor_title_id')->references('id')->on('doctor_titles')->onDelete('cascade');
            });
       
    }
};