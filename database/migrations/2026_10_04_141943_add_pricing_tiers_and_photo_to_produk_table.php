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
            $table->decimal('harga_aplikator', 15, 2)->nullable()->after('harga_jual');
            $table->decimal('harga_grosir', 15, 2)->nullable()->after('harga_aplikator');
            $table->string('foto')->nullable()->after('panjang_per_roll');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropColumn(['harga_aplikator', 'harga_grosir', 'foto']);
        });
    }
};
