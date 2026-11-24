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
        Schema::table('detail_penjualan', function (Blueprint $table) {
            $table->foreignId('gudang_id')->nullable()->after('produk_id')->constrained('gudang')->restrictOnDelete();
            $table->index('gudang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_penjualan', function (Blueprint $table) {
            $table->dropForeign(['gudang_id']);
            $table->dropIndex(['gudang_id']);
            $table->dropColumn('gudang_id');
        });
    }
};
