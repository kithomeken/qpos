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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('reference_no', 20)
                ->unique()
                ->nullable() // Initially nullable so existing rows don't break
                ->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // First check if the table exists
            if (Schema::hasTable('orders')) {
                Schema::table('orders', function (Blueprint $table) {
                    // Then check if the specific column exists before dropping
                    if (Schema::hasColumn('orders', 'reference_no')) {
                        $table->dropColumn('reference_no');
                    }
                });
            }
        });
    }
};
