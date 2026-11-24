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
        Schema::create('alokasi_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembayaran_id')->constrained('pembayaran')->cascadeOnDelete();
            $table->foreignId('penjualan_id')->constrained('penjualan')->restrictOnDelete();
            $table->decimal('jumlah_dialokasikan', 15, 2);
            $table->timestamps();

            $table->index('pembayaran_id');
            $table->index('penjualan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alokasi_pembayaran');
    }
};
