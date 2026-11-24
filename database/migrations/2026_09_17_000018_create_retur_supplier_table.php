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
        Schema::create('retur_supplier', function (Blueprint $table) {
            $table->id();
            $table->string('no_retur', 50)->unique();
            $table->date('tanggal');
            $table->foreignId('supplier_id')->constrained('supplier')->restrictOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $table->foreignId('gudang_id')->constrained('gudang')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pembelian_id')->nullable()->constrained('pembelian')->nullOnDelete();
            $table->decimal('jumlah', 15, 3);
            $table->text('alasan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('no_retur');
            $table->index('tanggal');
            $table->index('supplier_id');
            $table->index('produk_id');
            $table->index('gudang_id');
            $table->index('pembelian_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_supplier');
    }
};
