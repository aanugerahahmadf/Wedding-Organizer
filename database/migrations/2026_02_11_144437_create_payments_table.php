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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('payment_number')->unique();
            $table->string('payment_method');
            $table->enum('status', [
                'pending',
                'processing',
                'success',
                'failed',
                'expired',
                'cancelled',
                'refunded',
            ])->default('pending');
            $table->decimal('amount', 15, 2);
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);

            // Payment Gateway Details
            $table->string('payment_gateway')->nullable(); // midtrans, xendit, etc
            $table->string('transaction_id')->nullable(); // Gateway transaction ID
            $table->string('snap_token')->nullable(); // Midtrans snap token
            $table->text('payment_url')->nullable(); // Payment redirect URL

            // Bank Transfer Details
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder')->nullable();

            // Payment Proof
            $table->string('payment_proof')->nullable(); // File path for manual transfer proof

            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Additional Info
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Store gateway response

            $table->timestamps();

            // Indexes
            $table->index('payment_number');
            $table->index('transaction_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
