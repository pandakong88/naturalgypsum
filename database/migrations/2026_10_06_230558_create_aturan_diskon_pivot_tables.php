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
        Schema::create('aturan_diskon_kategori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aturan_diskon_id')->constrained('aturan_diskon')->cascadeOnDelete();
            $table->foreignId('kategori_id')->constrained('kategori')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['aturan_diskon_id', 'kategori_id'], 'ad_kategori_unique');
        });

        Schema::create('aturan_diskon_merek', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aturan_diskon_id')->constrained('aturan_diskon')->cascadeOnDelete();
            $table->foreignId('merek_id')->constrained('merek')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['aturan_diskon_id', 'merek_id'], 'ad_merek_unique');
        });

        Schema::create('aturan_diskon_produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aturan_diskon_id')->constrained('aturan_diskon')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['aturan_diskon_id', 'produk_id'], 'ad_produk_unique');
        });

        // Backfill data lama jika ada
        if (Schema::hasTable('aturan_diskon')) {
            $existingRules = DB::table('aturan_diskon')->get();
            foreach ($existingRules as $rule) {
                if ($rule->target_produk === 'kategori' && $rule->kategori_id) {
                    DB::table('aturan_diskon_kategori')->insertOrIgnore([
                        'aturan_diskon_id' => $rule->id,
                        'kategori_id' => $rule->kategori_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } elseif ($rule->target_produk === 'merek' && $rule->merek_id) {
                    DB::table('aturan_diskon_merek')->insertOrIgnore([
                        'aturan_diskon_id' => $rule->id,
                        'merek_id' => $rule->merek_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } elseif ($rule->target_produk === 'spesifik' && $rule->produk_id) {
                    DB::table('aturan_diskon_produk')->insertOrIgnore([
                        'aturan_diskon_id' => $rule->id,
                        'produk_id' => $rule->produk_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aturan_diskon_produk');
        Schema::dropIfExists('aturan_diskon_merek');
        Schema::dropIfExists('aturan_diskon_kategori');
    }
};
