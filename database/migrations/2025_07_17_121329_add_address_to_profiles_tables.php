<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**P
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('doctor_profiles', function (Blueprint $table) {
            $table->string('address')->nullable()->after('active_status'); // Replace with actual column
        });

        Schema::table('lawyer_profiles', function (Blueprint $table) {
            $table->string('address')->nullable()->after('active_status');
        });

        Schema::table('common_profiles', function (Blueprint $table) {
            $table->string('address')->nullable()->after('active_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_profiles', function (Blueprint $table) {
            $table->dropColumn('address');
        });

        Schema::table('lawyer_profiles', function (Blueprint $table) {
            $table->dropColumn('address');
        });

        Schema::table('common_profiles', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};