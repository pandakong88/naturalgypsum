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
        Schema::create('retur_pelanggan', function (Blueprint $table) {
            $table->id();
            $table->string('no_retur', 50)->unique();
            $table->date('tanggal');
            $table->foreignId('pelanggan_id')->constrained('pelanggan')->restrictOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $table->foreignId('gudang_id')->constrained('gudang')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('penjualan_id')->nullable()->constrained('penjualan')->nullOnDelete();
            $table->decimal('jumlah', 15, 3);
            $table->decimal('harga_retur', 15, 2)->default(0.00);
            $table->text('alasan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('no_retur');
            $table->index('tanggal');
            $table->index('pelanggan_id');
            $table->index('produk_id');
            $table->index('gudang_id');
            $table->index('penjualan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_pelanggan');
    }
};
