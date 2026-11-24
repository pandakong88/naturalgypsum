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
        Schema::create('mutasi_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $table->foreignId('gudang_id')->constrained('gudang')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('jenis', 50);
            $table->decimal('jumlah', 15, 3);
            $table->string('referensi', 100);
            $table->string('referensi_type', 100)->nullable();
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->dateTime('tanggal');
            $table->timestamps();

            $table->index('produk_id');
            $table->index('gudang_id');
            $table->index('user_id');
            $table->index('jenis');
            $table->index('tanggal');
            $table->index(['referensi_type', 'referensi_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_stok');
    }
};
