<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Stock Adjustments (Headernya Adjustment Manual)
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('reason'); // 'damaged', 'lost', 'correction'
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 2. Stock Movements (Kartu Stok - Log Pergerakan)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Jenis Pergerakan
            $table->string('type'); // 'purchase', 'sale', 'adjustment', 'sale_cancel'

            // Reference (Polymorphic manual)
            // Bisa merefer ke Sale ID, PurchaseOrder ID, atau StockAdjustment ID
            $table->string('reference_type')->nullable(); // 'App\Modules\Sales\Models\Sale', etc.
            $table->unsignedBigInteger('reference_id')->nullable();

            // Quantity Change
            $table->integer('quantity'); // Positif (Masuk), Negatif (Keluar)

            // Snapshot Stok (Audit Trail)
            $table->integer('stock_before');
            $table->integer('stock_after');

            // Opsional: Snapshot Cost Price saat kejadian (untuk valuasi inventory - FIFO/Average)
            // $table->decimal('cost_price', 15, 2)->nullable(); 

            $table->foreignId('user_id')->constrained('users'); // Siapa yang bikin movement (Kasir/Admin)
            $table->timestamps();

            $table->index(['product_id', 'created_at']); // Index buat filtering history cepat
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_adjustments');
    }
};
