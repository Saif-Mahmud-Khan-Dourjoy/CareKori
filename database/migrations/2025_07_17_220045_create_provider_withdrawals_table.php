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
        Schema::create('provider_withdrawals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('provider_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('method')->nullable(); // e.g. bkash, bank, etc.
            $table->string('transaction_id')->nullable();
            $table->string('account_details')->nullable();
            $table->timestamp('withdrawn_at')->useCurrent();
            $table->enum('status', ['requested', 'success', 'failed']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_withdrawals');
    }
};