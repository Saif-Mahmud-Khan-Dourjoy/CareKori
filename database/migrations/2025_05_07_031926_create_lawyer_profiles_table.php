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
        Schema::create('lawyer_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');  // Relationship with users table
            $table->unsignedBigInteger('lawyer_title_id');
            $table->text('bio')->nullable();  // Lawyer's bio
            $table->decimal('pricing', 8, 2)->nullable();  // Consultation fee
            $table->boolean('availability')->default(false);  // Availability status
            $table->string('avatar')->nullable();  // Profile image URL (the image file path will be stored here)
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('district')->nullable();
            $table->string('thana')->nullable();
            $table->string('practice_area')->nullable();
            $table->string('identification_no')->comment('nid, passport');
            $table->string('bar_registration_no');
            $table->time('active_from')->nullable();  // Active time from
            $table->time('active_to')->nullable();  // Active time to
            $table->boolean('active_status')->default(false);  // Active status
            // Foreign key constraint linking to the  table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('lawyer_title_id')->references('id')->on('lawyer_titles')->onDelete('cascade');
           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lawyer_profiles');
    }
};