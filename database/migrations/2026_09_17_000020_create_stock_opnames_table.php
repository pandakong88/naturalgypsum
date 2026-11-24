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
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('no_stock_opname', 50)->unique();
            $table->date('tanggal');
            $table->foreignId('gudang_id')->constrained('gudang')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('selesai');
            $table->text('catatan')->nullable();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->foreignId('dibatalkan_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan_batal')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('no_stock_opname');
            $table->index('tanggal');
            $table->index('gudang_id');
            $table->index('status');
        });

        Schema::create('detail_stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $table->decimal('stok_sistem', 15, 3);
            $table->decimal('stok_fisik', 15, 3);
            $table->decimal('selisih', 15, 3);
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->index('stock_opname_id');
            $table->index('produk_id');
            $table->unique(['stock_opname_id', 'produk_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_stock_opnames');
        Schema::dropIfExists('stock_opnames');
    }
};
