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
        Schema::create('topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('pending'); // pending, success, failed, cancelled
            $table->string('payment_url')->nullable();
            $table->string('snap_token')->nullable();
            $table->string('payment_proof')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topups');
    }
};
