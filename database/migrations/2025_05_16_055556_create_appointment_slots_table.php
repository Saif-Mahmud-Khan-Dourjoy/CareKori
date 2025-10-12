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
        Schema::create('appointment_slots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('availability_id'); // links to `service_provider_availability`
            $table->time('slot_time'); // Time for each slot (e.g., 09:00, 09:10, etc.)
            $table->boolean('is_booked')->default(false); // Is this slot booked?
            $table->timestamps();

            $table->foreign('availability_id')->references('id')->on('service_provider_availabilities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_slots');
    }
};