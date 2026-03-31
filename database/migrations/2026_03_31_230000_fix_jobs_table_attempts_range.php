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
        Schema::table('jobs', function (Blueprint $table) {
            // Making attempts column Integer instead of TinyInteger to avoid 
            // "Numeric value out of range" (1264) in high-frequency workers or proxy environments.
            // Also adding default(0) to ensure consistent initialization.
            $table->unsignedInteger('attempts')->default(0)->change();
        });
        
        Schema::table('job_batches', function (Blueprint $table) {
            // Ensuring these are standard integers to handle larger export datasets if needed.
            $table->integer('total_jobs')->default(0)->change();
            $table->integer('pending_jobs')->default(0)->change();
            $table->integer('failed_jobs')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->change();
        });
    }
};
