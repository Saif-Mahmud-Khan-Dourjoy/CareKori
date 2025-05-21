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
      
        Schema::table('lawyer_profiles', function (Blueprint $table) {
            // Drop foreign key constraints first

           
            $table->dropForeign(['lawyer_speciality_id']);
            $table->dropForeign(['lawyer_title_id']);
        });

        Schema::table('lawyer_profiles', function (Blueprint $table) {
            // Alter columns to be nullable

            $table->unsignedBigInteger('lawyer_speciality_id')->nullable()->change();
            $table->unsignedBigInteger('lawyer_title_id')->nullable()->change();
        });

        Schema::table('lawyer_profiles', function (Blueprint $table) {
            // Re-add foreign key constraints

         
            $table->foreign('lawyer_speciality_id')->references('id')->on('lawyer_specialities')->onDelete('cascade');
            $table->foreign('lawyer_title_id')->references('id')->on('lawyer_titles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       

        Schema::table('lawyer_profiles', function (Blueprint $table) {

          
            $table->dropForeign(['lawyer_speciality_id']);
            $table->dropForeign(['lawyer_title_id']);
        });

        Schema::table('lawyer_profiles', function (Blueprint $table) {

            $table->unsignedBigInteger('lawyer_speciality_id')->nullable(false)->change();
            $table->unsignedBigInteger('lawyer_title_id')->nullable(false)->change();
        });

        Schema::table('lawyer_profiles', function (Blueprint $table) {

           
            $table->foreign('lawyer_speciality_id')->references('id')->on('lawyer_specialities')->onDelete('cascade');
            $table->foreign('lawyer_title_id')->references('id')->on('lawyer_titles')->onDelete('cascade');
        });
    }
};