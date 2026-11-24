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
            $table->text('alasan_batal')->nullable()->after('keterangan');
            $table->foreignId('dibatalkan_by')->nullable()->after('alasan_batal')->constrained('users')->nullOnDelete();
            $table->timestamp('dibatalkan_at')->nullable()->after('dibatalkan_by');

            $table->index('dibatalkan_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->dropForeign(['dibatalkan_by']);
            $table->dropIndex(['dibatalkan_at']);
            $table->dropColumn(['alasan_batal', 'dibatalkan_by', 'dibatalkan_at']);
        });
    }
};
