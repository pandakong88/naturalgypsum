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
        Schema::table('produk', function (Blueprint $table) {
            $table->decimal('panjang_meter', 8, 2)->nullable()->after('ukuran');
            $table->string('kemasan', 50)->nullable()->after('satuan');
            $table->decimal('isi_per_kemasan', 10, 2)->nullable()->after('kemasan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropColumn(['panjang_meter', 'kemasan', 'isi_per_kemasan']);
        });
    }
};
