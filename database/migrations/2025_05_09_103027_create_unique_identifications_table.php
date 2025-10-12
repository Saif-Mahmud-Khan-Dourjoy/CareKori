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
        Schema::create('unique_identifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('common_profile_id');
            $table->string('unique_identification_no');
            $table->string('other_data',1000)->nullable()->comment('Json Data');


            // Foreign key constraint linking to the  table
            $table->foreign('common_profile_id')->references('id')->on('common_profiles')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unique_identifications');
    }
};