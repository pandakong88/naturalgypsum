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
        Schema::table('penjualan', function (Blueprint $table) {
            $table->string('nama_sopir', 100)->nullable()->after('keterangan');
            $table->string('plat_nomor', 50)->nullable()->after('nama_sopir');
            $table->text('alamat_pengiriman')->nullable()->after('plat_nomor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->dropColumn(['nama_sopir', 'plat_nomor', 'alamat_pengiriman']);
        });
    }
};
