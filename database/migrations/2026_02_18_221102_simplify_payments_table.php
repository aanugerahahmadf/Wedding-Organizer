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
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'account_number',
                'account_holder',
                'payment_gateway',
                'transaction_id',
                'snap_token',
                'payment_url'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('snap_token')->nullable();
            $table->text('payment_url')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder')->nullable();
        });
    }
};
