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
        Schema::table('detail_pembelian', function (Blueprint $table) {
            $table->decimal('qty_lembar', 15, 3)->nullable()->after('gudang_id');
            $table->decimal('panjang_meter', 10, 2)->nullable()->after('qty_lembar');
            $table->decimal('harga_bruto', 15, 2)->nullable()->after('jumlah');
            $table->decimal('diskon_satuan', 15, 2)->default(0.00)->after('harga_bruto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_pembelian', function (Blueprint $table) {
            $table->dropColumn([
                'qty_lembar',
                'panjang_meter',
                'harga_bruto',
                'diskon_satuan',
            ]);
        });
    }
};
