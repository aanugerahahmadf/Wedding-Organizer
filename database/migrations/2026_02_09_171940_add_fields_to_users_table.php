<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->unique()->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->date('wedding_date')->nullable();
            $table->string('theme_preference')->nullable();
            $table->string('color_preference')->nullable();
            $table->string('event_concept')->nullable();
            $table->string('dream_venue')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
