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
        Schema::create('aturan_diskon', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('keterangan')->nullable();

            // Target Pelanggan: semua, pemborong, langganan, spesifik
            $table->string('target_pelanggan')->default('semua');
            $table->foreignId('pelanggan_id')->nullable()->constrained('pelanggan')->nullOnDelete();

            // Target Produk: semua, kategori, merek, spesifik
            $table->string('target_produk')->default('semua');
            $table->foreignId('kategori_id')->nullable()->constrained('kategori')->nullOnDelete();
            $table->foreignId('merek_id')->nullable()->constrained('merek')->nullOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();

            // Diskon rule
            $table->string('jenis_diskon'); // persentase, nominal
            $table->decimal('nilai_diskon', 15, 2);
            $table->decimal('min_kuantiti', 10, 2)->default(0);

            // Validity period
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('target_pelanggan');
            $table->index('target_produk');
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aturan_diskon');
    }
};
