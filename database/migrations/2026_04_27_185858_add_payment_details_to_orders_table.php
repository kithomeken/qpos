<?php

use App\Models\PaymentMethods;
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
            // Foreign key to the payment method
            $table->foreignIdFor(PaymentMethods::class)->nullable()->constrained()->nullOnDelete();

            // Snapshot of the surcharge details at the time of sale
            $table->string('surcharge_type')->nullable();
            $table->decimal('surcharge_value', 12, 2)->default(0.00);
            $table->decimal('surcharge_amount', 12, 2)->default(0.00);
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
                    if (Schema::hasColumn('orders', 'payment_method_id', 'surcharge_amount', 'surcharge_type', 'surcharge_value', 'payment_method_code')) {
                        $table->dropForeign(['payment_method_id']);
                        
                        $table->dropColumn([
                            'payment_method_id',
                            'surcharge_amount',
                            'surcharge_type',
                            'surcharge_value',
                            'payment_method_code'
                        ]);
                    }
                });
            }
        });
    }
};
