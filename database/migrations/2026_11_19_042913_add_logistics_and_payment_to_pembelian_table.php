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
        Schema::table('pembelian', function (Blueprint $table) {
            $table->string('no_faktur_supplier', 100)->nullable()->after('no_pembelian');
            $table->string('no_surat_jalan', 100)->nullable()->after('no_faktur_supplier');
            $table->string('no_polisi', 50)->nullable()->after('no_kontainer');
            $table->string('status', 30)->default('received')->after('total');
            $table->string('status_pembayaran', 30)->default('lunas')->after('status');
            $table->date('tanggal_jatuh_tempo')->nullable()->after('status_pembayaran');
            $table->decimal('nominal_bayar', 15, 2)->default(0.00)->after('tanggal_jatuh_tempo');
            $table->decimal('sisa_hutang', 15, 2)->default(0.00)->after('nominal_bayar');
            $table->date('tanggal_diterima')->nullable()->after('sisa_hutang');
            $table->foreignId('diterima_by')->nullable()->after('tanggal_diterima')->constrained('users')->nullOnDelete();

            $table->index('no_faktur_supplier');
            $table->index('no_surat_jalan');
            $table->index('status');
            $table->index('status_pembayaran');
            $table->index('tanggal_jatuh_tempo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembelian', function (Blueprint $table) {
            $table->dropForeign(['diterima_by']);
            $table->dropIndex(['no_faktur_supplier']);
            $table->dropIndex(['no_surat_jalan']);
            $table->dropIndex(['status']);
            $table->dropIndex(['status_pembayaran']);
            $table->dropIndex(['tanggal_jatuh_tempo']);

            $table->dropColumn([
                'no_faktur_supplier',
                'no_surat_jalan',
                'no_polisi',
                'status',
                'status_pembayaran',
                'tanggal_jatuh_tempo',
                'nominal_bayar',
                'sisa_hutang',
                'tanggal_diterima',
                'diterima_by',
            ]);
        });
    }
};
