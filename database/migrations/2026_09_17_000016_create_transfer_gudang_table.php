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
        Schema::create('transfer_gudang', function (Blueprint $table) {
            $table->id();
            $table->string('no_transfer', 50)->unique();
            $table->date('tanggal');
            $table->foreignId('gudang_asal_id')->constrained('gudang')->restrictOnDelete();
            $table->foreignId('gudang_tujuan_id')->constrained('gudang')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('no_transfer');
            $table->index('tanggal');
            $table->index('gudang_asal_id');
            $table->index('gudang_tujuan_id');
        });

        Schema::create('detail_transfer_gudang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_gudang_id')->constrained('transfer_gudang')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $table->decimal('jumlah', 15, 3);
            $table->timestamps();

            $table->index('transfer_gudang_id');
            $table->index('produk_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_transfer_gudang');
        Schema::dropIfExists('transfer_gudang');
    }
};
