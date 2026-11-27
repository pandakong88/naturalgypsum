<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riwayat_harga_produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->string('jenis_harga', 30); // harga_beli, harga_jual, harga_aplikator, harga_grosir
            $table->decimal('harga_lama', 15, 2)->nullable();
            $table->decimal('harga_baru', 15, 2)->nullable();
            $table->decimal('selisih_nominal', 15, 2)->default(0);
            $table->decimal('selisih_persen', 8, 2)->nullable();
            $table->string('sumber', 40)->default('master_update'); // master_create, master_update, pembelian, import_excel
            $table->string('referensi', 100)->nullable();
            $table->string('referensi_type', 50)->nullable();
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('supplier')->nullOnDelete();
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['produk_id', 'created_at']);
            $table->index(['jenis_harga', 'created_at']);
            $table->index(['sumber', 'created_at']);
        });

        // Backfill historical price changes from activity_logs if available
        if (Schema::hasTable('activity_logs') && Schema::hasTable('produk')) {
            try {
                $existingProductIds = DB::table('produk')->pluck('id')->flip();
                $logs = DB::table('activity_logs')
                    ->where('subject_type', 'like', '%Produk')
                    ->where('action', 'update')
                    ->whereNotNull('old_values')
                    ->whereNotNull('new_values')
                    ->orderBy('id')
                    ->get();

                $priceFields = ['harga_beli', 'harga_jual', 'harga_aplikator', 'harga_grosir'];

                foreach ($logs as $log) {
                    $produkId = (int) $log->subject_id;
                    if (! isset($existingProductIds[$produkId])) {
                        continue;
                    }

                    $old = json_decode((string) $log->old_values, true) ?: [];
                    $new = json_decode((string) $log->new_values, true) ?: [];

                    foreach ($priceFields as $field) {
                        if (! array_key_exists($field, $old) && ! array_key_exists($field, $new)) {
                            continue;
                        }

                        $valOld = isset($old[$field]) && $old[$field] !== '' ? (float) $old[$field] : null;
                        $valNew = isset($new[$field]) && $new[$field] !== '' ? (float) $new[$field] : null;

                        if ($valOld === null && $valNew === null) {
                            continue;
                        }

                        if (abs(($valNew ?? 0.0) - ($valOld ?? 0.0)) > 0.001) {
                            $diff = ($valNew ?? 0.0) - ($valOld ?? 0.0);
                            $pct = ($valOld !== null && $valOld > 0)
                                ? round(($diff / $valOld) * 100, 2)
                                : null;

                            DB::table('riwayat_harga_produk')->insert([
                                'produk_id' => $produkId,
                                'jenis_harga' => $field,
                                'harga_lama' => $valOld,
                                'harga_baru' => $valNew,
                                'selisih_nominal' => $diff,
                                'selisih_persen' => $pct,
                                'sumber' => 'master_update',
                                'referensi' => 'Edit Manual Katalog',
                                'referensi_type' => 'produk',
                                'referensi_id' => $produkId,
                                'supplier_id' => null,
                                'keterangan' => $log->description ?: 'Pembaruan harga dari Master Produk',
                                'user_id' => $log->user_id,
                                'created_at' => $log->created_at,
                                'updated_at' => $log->created_at,
                            ]);
                        }
                    }
                }
            } catch (Throwable) {
                // Ignore backfill errors safely
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_harga_produk');
    }
};
