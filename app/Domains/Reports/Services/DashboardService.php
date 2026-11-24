<?php

namespace App\Domains\Reports\Services;

use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Production\Models\DetailProduksi;
use App\Domains\Production\Models\Produksi;
use App\Domains\Purchasing\Models\Pembelian;
use App\Domains\Receivables\Models\Pembayaran;
use App\Domains\Returns\Models\ReturPelanggan;
use App\Domains\Returns\Models\ReturSupplier;
use App\Domains\Sales\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Parse date range from period name or custom start/end.
     *
     * @param  array<string, mixed>  $filters
     * @return array{start: ?Carbon, end: ?Carbon}
     */
    protected function parseDateRange(array $filters): array
    {
        $period = $filters['period'] ?? 'this_month';

        if ($period === 'today') {
            return [
                'start' => Carbon::today()->startOfDay(),
                'end' => Carbon::today()->endOfDay(),
            ];
        }

        if ($period === 'last_7_days') {
            return [
                'start' => Carbon::today()->subDays(6)->startOfDay(),
                'end' => Carbon::today()->endOfDay(),
            ];
        }

        if ($period === 'this_month') {
            return [
                'start' => Carbon::now()->startOfMonth()->startOfDay(),
                'end' => Carbon::now()->endOfMonth()->endOfDay(),
            ];
        }

        if ($period === 'last_month') {
            return [
                'start' => Carbon::now()->subMonth()->startOfMonth()->startOfDay(),
                'end' => Carbon::now()->subMonth()->endOfMonth()->endOfDay(),
            ];
        }

        if ($period === 'custom' && ! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            return [
                'start' => Carbon::parse($filters['start_date'])->startOfDay(),
                'end' => Carbon::parse($filters['end_date'])->endOfDay(),
            ];
        }

        return ['start' => null, 'end' => null];
    }

    /**
     * Get stock overview KPI cards.
     *
     * @return array<string, mixed>
     */
    public function getStockSummary(): array
    {
        $totalItems = Produk::count();
        $totalPhysicalStock = (float) Stok::sum('jumlah');

        // Low stock products: where sum of stocks across warehouses <= stok_minimum
        $lowStockCount = Produk::where('stok_minimum', '>', 0)
            ->whereHas('stok', function ($q) {
                // Products that have stock records
            })
            ->withSum('stok as total_stok', 'jumlah')
            ->get()
            ->filter(fn ($p) => ($p->total_stok ?? 0) <= $p->stok_minimum)
            ->count();

        // Estimated stock value: sum(stok.jumlah * produk.harga_beli)
        $estimatedValue = (float) Stok::join('produk', 'stok.produk_id', '=', 'produk.id')
            ->selectRaw('SUM(stok.jumlah * produk.harga_beli) as total_val')
            ->value('total_val') ?? 0.0;

        return [
            'total_items' => $totalItems,
            'total_physical_stock' => $totalPhysicalStock,
            'low_stock_count' => $lowStockCount,
            'estimated_stock_value' => $estimatedValue,
        ];
    }

    /**
     * Get sales KPI cards for a given period.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getSalesSummary(array $filters = []): array
    {
        $dates = $this->parseDateRange($filters);
        $query = Penjualan::query();

        if ($dates['start'] && $dates['end']) {
            $query->whereBetween('tanggal', [$dates['start'], $dates['end']]);
        }

        $totalSales = (float) (clone $query)->sum('total_akhir');
        $transactionCount = (clone $query)->count();
        $totalPaid = (float) (clone $query)->sum('total_dibayar');
        $totalReceivable = (float) (clone $query)->sum('sisa_piutang');

        return [
            'total_sales' => $totalSales,
            'transaction_count' => $transactionCount,
            'total_paid' => $totalPaid,
            'total_receivable' => $totalReceivable,
        ];
    }

    /**
     * Get purchase KPI cards for a given period.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getPurchaseSummary(array $filters = []): array
    {
        $dates = $this->parseDateRange($filters);
        $query = Pembelian::query();

        if ($dates['start'] && $dates['end']) {
            $query->whereBetween('tanggal', [$dates['start'], $dates['end']]);
        }

        $totalPurchases = (float) (clone $query)->sum('total');
        $transactionCount = (clone $query)->count();

        return [
            'total_purchases' => $totalPurchases,
            'transaction_count' => $transactionCount,
        ];
    }

    /**
     * Get receivables KPI cards (all active unpaid balances).
     *
     * @return array<string, mixed>
     */
    public function getReceivablesSummary(): array
    {
        $unpaidInvoices = Penjualan::where('sisa_piutang', '>', 0);

        $totalOutstanding = (float) (clone $unpaidInvoices)->sum('sisa_piutang');
        $customerCount = (clone $unpaidInvoices)->distinct('pelanggan_id')->count('pelanggan_id');
        $invoiceCount = (clone $unpaidInvoices)->count();

        return [
            'total_outstanding' => $totalOutstanding,
            'customer_count' => $customerCount,
            'unpaid_invoice_count' => $invoiceCount,
        ];
    }

    /**
     * Get payment collection KPI cards.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getPaymentSummary(array $filters = []): array
    {
        $dates = $this->parseDateRange($filters);
        $query = Pembayaran::query();

        if ($dates['start'] && $dates['end']) {
            $query->whereBetween('tanggal', [$dates['start'], $dates['end']]);
        }

        $totalCollected = (float) (clone $query)->sum('jumlah_bayar');
        $paymentCount = (clone $query)->count();

        return [
            'total_collected' => $totalCollected,
            'payment_count' => $paymentCount,
        ];
    }

    /**
     * Get production operational summary.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getProductionSummary(array $filters = []): array
    {
        $dates = $this->parseDateRange($filters);
        $query = Produksi::query();

        if ($dates['start'] && $dates['end']) {
            $query->whereBetween('tanggal', [$dates['start'], $dates['end']]);
        }

        $totalBatches = (clone $query)->count();
        $totalQty = (float) DetailProduksi::whereIn('produksi_id', (clone $query)->select('id'))->sum('jumlah');

        return [
            'total_batches' => $totalBatches,
            'total_qty_produced' => $totalQty,
        ];
    }

    /**
     * Get return summaries (supplier & customer).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReturnsSummary(array $filters = []): array
    {
        $dates = $this->parseDateRange($filters);

        $supQuery = ReturSupplier::query();
        $custQuery = ReturPelanggan::query();

        if ($dates['start'] && $dates['end']) {
            $supQuery->whereBetween('tanggal', [$dates['start'], $dates['end']]);
            $custQuery->whereBetween('tanggal', [$dates['start'], $dates['end']]);
        }

        $supDocs = (clone $supQuery)->count();
        $supQty = (float) (clone $supQuery)->sum('jumlah');

        $custDocs = (clone $custQuery)->count();
        $custQty = (float) (clone $custQuery)->sum('jumlah');

        return [
            'supplier_returns_count' => $supDocs,
            'supplier_returns_qty' => $supQty,
            'customer_returns_count' => $custDocs,
            'customer_returns_qty' => $custQty,
        ];
    }

    /**
     * Get recent transactions for activity stream.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getRecentTransactions(int $limit = 5): Collection
    {
        return Penjualan::with('pelanggan')
            ->latest('tanggal')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'type' => 'penjualan',
                'doc_no' => $s->no_penjualan,
                'party' => $s->pelanggan->nama ?? '-',
                'date' => $s->tanggal,
                'amount' => (float) $s->total_akhir,
                'status' => $s->status_pembayaran->value ?? 'belum_bayar',
            ]);
    }
}
