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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., BCA, GoPay, OVO
            $table->string('type'); // bank_transfer, ewallet, qris
            $table->string('code')->unique(); // e.g., bca, gopay
            $table->string('icon')->nullable(); // Logo payment
            $table->string('account_number')->nullable(); // Untuk bank & e-wallet
            $table->string('account_holder')->nullable(); // Untuk bank
            $table->string('qris_image')->nullable(); // Khusus QRIS
            $table->decimal('fee', 15, 2)->default(0); // Biaya admin jika ada
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
