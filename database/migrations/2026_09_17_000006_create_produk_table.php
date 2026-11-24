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
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori')->restrictOnDelete();
            $table->foreignId('merek_id')->nullable()->constrained('merek')->nullOnDelete();
            $table->string('kode_katalog')->unique();
            $table->string('nama');
            $table->string('ukuran')->nullable();
            $table->string('satuan'); // meter, lembar, batang
            $table->decimal('harga_beli', 15, 2)->default(0.00);
            $table->decimal('harga_jual', 15, 2)->default(0.00);
            $table->decimal('stok_minimum', 15, 3)->default(0.000);
            $table->decimal('panjang_per_roll', 10, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('nama');
            $table->index('satuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
