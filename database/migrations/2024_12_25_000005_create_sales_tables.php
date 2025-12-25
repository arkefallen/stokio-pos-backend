<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique(); // TRX-YYYYMMDD-XXXX

            // Statuses
            $table->string('status')->default('pending'); // pending, completed, cancelled
            $table->string('payment_status')->default('unpaid'); // unpaid, paid, refund
            $table->string('payment_method')->nullable(); // cash, qris, debit, credit

            // Financials
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);

            // Cash specific
            $table->decimal('cash_given', 15, 2)->nullable();
            $table->decimal('change_return', 15, 2)->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();

            $table->index('sale_number');
            $table->index(['created_at', 'status']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot
            $table->string('product_name');
            $table->string('product_sku');

            $table->integer('quantity');
            $table->decimal('price', 15, 2);
            $table->decimal('cost_price', 15, 2);
            $table->decimal('subtotal', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
