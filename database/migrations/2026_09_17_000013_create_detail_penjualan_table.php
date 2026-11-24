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
        Schema::create('detail_penjualan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->constrained('penjualan')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $table->decimal('harga_satuan', 15, 2);
            $table->string('jenis_diskon', 20)->nullable();
            $table->decimal('nilai_diskon', 15, 2)->default(0.00);
            $table->decimal('jumlah', 15, 3);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->index('penjualan_id');
            $table->index('produk_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_penjualan');
    }
};
