<?php

namespace App\Domains\Inventory\Enums;

enum JenisMutasiStok: string
{
    case PembelianMasuk = 'pembelian_masuk';
    case ProduksiMasuk = 'produksi_masuk';
    case PenjualanKeluar = 'penjualan_keluar';
    case Transfer = 'transfer';
    case ReturKeSupplier = 'retur_ke_supplier';
    case ReturDariPelanggan = 'retur_dari_pelanggan';
    case PemakaianInternal = 'pemakaian_internal';
    case PenyesuaianOpname = 'penyesuaian_opname';

    /**
     * Get user-friendly display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PembelianMasuk => 'Pembelian Masuk',
            self::ProduksiMasuk => 'Produksi Masuk',
            self::PenjualanKeluar => 'Penjualan Keluar',
            self::Transfer => 'Transfer Gudang',
            self::ReturKeSupplier => 'Retur ke Supplier',
            self::ReturDariPelanggan => 'Retur dari Pelanggan',
            self::PemakaianInternal => 'Pemakaian Internal',
            self::PenyesuaianOpname => 'Penyesuaian Opname',
        };
    }

    /**
     * Determine whether the typical mutation type adds stock.
     */
    public function isAddition(): bool
    {
        return match ($this) {
            self::PembelianMasuk, self::ProduksiMasuk, self::ReturDariPelanggan => true,
            self::PenjualanKeluar, self::ReturKeSupplier, self::PemakaianInternal => false,
            self::Transfer, self::PenyesuaianOpname => false, // context dependent
        };
    }

    /**
     * Get Bootstrap badge CSS class for display.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PembelianMasuk => 'bg-success-subtle text-success border border-success-subtle',
            self::ProduksiMasuk => 'bg-info-subtle text-info border border-info-subtle',
            self::PenjualanKeluar => 'bg-danger-subtle text-danger border border-danger-subtle',
            self::Transfer => 'bg-primary-subtle text-primary border border-primary-subtle',
            self::ReturKeSupplier => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            self::ReturDariPelanggan => 'bg-teal-subtle text-teal border border-teal-subtle',
            self::PemakaianInternal => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            self::PenyesuaianOpname => 'bg-dark-subtle text-dark border border-dark-subtle',
        };
    }
}
