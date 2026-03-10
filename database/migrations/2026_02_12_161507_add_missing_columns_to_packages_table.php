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
        Schema::table('packages', function (Blueprint $table) {
            if (! Schema::hasColumn('packages', 'discount_price')) {
                $table->decimal('discount_price', 15, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('packages', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('discount_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['discount_price', 'is_featured']);
        });
    }
};
