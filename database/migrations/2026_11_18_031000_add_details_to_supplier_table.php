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
        Schema::table('supplier', function (Blueprint $table) {
            $table->text('alamat')->nullable()->after('kontak');
            $table->string('email')->nullable()->after('alamat');
            $table->string('nama_bank')->nullable()->after('email');
            $table->string('no_rekening')->nullable()->after('nama_bank');
            $table->string('atas_nama_rekening')->nullable()->after('no_rekening');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier', function (Blueprint $table) {
            $table->dropColumn([
                'alamat',
                'email',
                'nama_bank',
                'no_rekening',
                'atas_nama_rekening',
            ]);
        });
    }
};
