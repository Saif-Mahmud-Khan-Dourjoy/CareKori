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
        Schema::create('payment_records', function (Blueprint $table) {
            $table->bigIncrements('id');

            
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

         

            
            $table->dateTime('trx_datetime')->nullable();
            $table->string('trx_id', 100)->nullable()->index();

           
            $table->dateTime('amount_received_datetime')->nullable();

           
            $table->decimal('sent_amount', 14, 2)->default(0);
            $table->dateTime('sent_datetime')->nullable();

            
            $table->enum('sent_via', ['MFS', 'BANK'])->nullable()->index();

            
            $table->string('sent_trx_id', 100)->nullable()->index();

         
            $table->string('sent_bank_acc', 191)->nullable();

           
            $table->json('amount_data')->nullable();

            $table->timestamps();

            
            // $table->unique(['trx_id']);

            $table->index(['user_id', 'trx_datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_records');
    }
};
