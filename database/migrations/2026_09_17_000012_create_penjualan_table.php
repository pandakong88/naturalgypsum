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
        Schema::create('penjualan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->constrained('pelanggan')->restrictOnDelete();
            $table->foreignId('gudang_id')->constrained('gudang')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('no_penjualan', 50)->unique();
            $table->date('tanggal');
            $table->decimal('biaya_ongkir', 15, 2)->default(0.00);
            $table->decimal('total_akhir', 15, 2)->default(0.00);
            $table->string('status_pembayaran', 50)->default('belum_bayar');
            $table->decimal('total_dibayar', 15, 2)->default(0.00);
            $table->decimal('sisa_piutang', 15, 2)->default(0.00);
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('pelanggan_id');
            $table->index('gudang_id');
            $table->index('user_id');
            $table->index('tanggal');
            $table->index('no_penjualan');
            $table->index('status_pembayaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan');
    }
};
