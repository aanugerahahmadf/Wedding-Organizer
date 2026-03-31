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
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('topups', function (Blueprint $table) {
            $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_id');
        });

        Schema::table('topups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_id');
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_id');
        });
    }
};
