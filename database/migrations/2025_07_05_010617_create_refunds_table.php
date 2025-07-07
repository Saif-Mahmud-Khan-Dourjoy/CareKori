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
        Schema::create('refunds', function (Blueprint $table) {
            $table->bigIncrements('id'); 
            $table->unsignedBigInteger('order_id'); 
            $table->string('status')->nullable(); 
            $table->decimal('refund_amount', 10, 2);
            $table->string('refund_ref_id')->nullable(); 
            $table->string('refund_remark')->nullable(); 
            $table->timestamps(); 

       
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};