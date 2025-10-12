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
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');  // Relationship with users table
            $table->unsignedBigInteger('doctor_type_id');
            $table->unsignedBigInteger('doctor_speciality_id');
            $table->unsignedBigInteger('doctor_title_id');
            $table->text('bio')->nullable();  // Doctor's bio
            $table->decimal('pricing', 8, 2)->nullable();  // Consultation fee
            $table->boolean('availability')->default(false);  // Availability status
            $table->string('avatar')->nullable();  // Profile image URL (the image file path will be stored here)
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('district')->nullable();
            $table->string('thana')->nullable();
            $table->string('identification_no')->comment('nid, passport');
            $table->string('registration_no');
            $table->time('active_from')->nullable();  // Active time from
            $table->time('active_to')->nullable();  // Active time to
            $table->boolean('active_status')->default(false);  // Active status
            // Foreign key constraint linking to the  table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('doctor_type_id')->references('id')->on('doctor_types')->onDelete('cascade');
            $table->foreign('doctor_speciality_id')->references('id')->on('doctor_specialities')->onDelete('cascade');
            $table->foreign('doctor_title_id')->references('id')->on('doctor_titles')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_profiles');
    }
};