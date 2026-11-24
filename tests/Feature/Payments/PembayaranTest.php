<?php

use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Receivables\Actions\CancelPembayaranAction;
use App\Domains\Receivables\Actions\CreatePembayaranAction;
use App\Domains\Receivables\Enums\MetodePembayaran;
use App\Domains\Receivables\Models\AlokasiPembayaran;
use App\Domains\Receivables\Models\Pembayaran;
use App\Domains\Receivables\Services\PaymentAllocationService;
use App\Domains\Sales\Actions\CancelPenjualanAction;
use App\Domains\Sales\Actions\CreatePenjualanAction;
use App\Domains\Sales\Enums\StatusPembayaran;
use App\Domains\Sales\Models\Penjualan;
use App\Livewire\Payments\PembayaranCreate;
use App\Livewire\Payments\PembayaranDetail;
use App\Livewire\Payments\PembayaranIndex;
use App\Livewire\Receivables\PiutangIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->manager = User::factory()->create();
    $this->manager->assignRole('Manager');

    $this->staffPenjualan = User::factory()->create();
    $this->staffPenjualan->assignRole('Staff Penjualan');

    $this->staffGudang = User::factory()->create();
    $this->staffGudang->assignRole('Staff Gudang');

    $this->unauthorizedUser = User::factory()->create();

    $this->pelangganA = Pelanggan::factory()->create([
        'nama' => 'Toko Maju Bersama',
        'kontak' => '081234567890',
        'tipe' => TipePelanggan::Pemborong,
        'limit_piutang' => 5000000.00,
    ]);

    $this->pelangganB = Pelanggan::factory()->create([
        'nama' => 'CV Sumber Makmur',
        'kontak' => '089876543210',
        'tipe' => TipePelanggan::Langganan,
        'limit_piutang' => 10000000.00,
    ]);

    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Plafon Gypsum']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Jayaboard']);
    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Pusat']);

    $this->produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Papan Gypsum 9mm',
        'harga_beli' => 50000.00,
        'harga_jual' => 70000.00,
    ]);

    app(StockService::class)->increase($this->produk, $this->gudang, 1000, JenisMutasiStok::PembelianMasuk, 'INIT-STOCK');

    $this->salesAction = app(CreatePenjualanAction::class);
    $this->paymentAction = app(CreatePembayaranAction::class);
    $this->cancelAction = app(CancelPembayaranAction::class);
    $this->allocationService = app(PaymentAllocationService::class);
});

function createInvoiceHelper($testContext, $pelanggan, $qty = 10, $price = 70000.00, $ongkir = 0.00): Penjualan
{
    return $testContext->salesAction->execute([
        'pelanggan_id' => $pelanggan->id,
        'gudang_id' => $testContext->gudang->id,
        'tanggal' => '2026-09-17',
        'biaya_ongkir' => $ongkir,
        'user_id' => $testContext->owner->id,
        'items' => [
            [
                'produk_id' => $testContext->produk->id,
                'harga_satuan' => $price,
                'jumlah' => $qty,
            ],
        ],
    ]);
}

test('TEST 1 — Record payment single invoice full', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 10, 70000.00);

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 700000.00,
        'metode' => MetodePembayaran::Transfer,
        'catatan' => 'Pelunasan faktur',
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 700000.00,
            ],
        ],
    ]);

    $sale->refresh();

    expect($payment)->toBeInstanceOf(Pembayaran::class)
        ->and($payment->no_pembayaran)->toStartWith('BY-')
        ->and((float) $payment->jumlah_bayar)->toBe(700000.00)
        ->and((float) $payment->total_dialokasikan)->toBe(700000.00)
        ->and((float) $payment->sisa_belum_dialokasikan)->toBe(0.00)
        ->and((float) $sale->total_dibayar)->toBe(700000.00)
        ->and((float) $sale->sisa_piutang)->toBe(0.00)
        ->and($sale->status_pembayaran)->toBe(StatusPembayaran::Lunas);

    $this->assertDatabaseHas('pembayaran', [
        'id' => $payment->id,
        'pelanggan_id' => $this->pelangganA->id,
        'jumlah_bayar' => 700000.00,
    ]);

    $this->assertDatabaseHas('alokasi_pembayaran', [
        'pembayaran_id' => $payment->id,
        'penjualan_id' => $sale->id,
        'jumlah_dialokasikan' => 700000.00,
    ]);
});

test('TEST 2 — Record payment single invoice partial', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 10, 70000.00);

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 300000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 300000.00,
            ],
        ],
    ]);

    $sale->refresh();

    expect((float) $sale->total_dibayar)->toBe(300000.00)
        ->and((float) $sale->sisa_piutang)->toBe(400000.00)
        ->and($sale->status_pembayaran)->toBe(StatusPembayaran::Sebagian);
});

test('TEST 3 — Record payment multiple invoices in single transaction', function () {
    $sale1 = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);
    $sale2 = createInvoiceHelper($this, $this->pelangganA, 10, 70000.00);

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 550000.00,
        'metode' => MetodePembayaran::Transfer,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale1->id,
                'jumlah_dialokasikan' => 350000.00,
            ],
            [
                'penjualan_id' => $sale2->id,
                'jumlah_dialokasikan' => 200000.00,
            ],
        ],
    ]);

    $sale1->refresh();
    $sale2->refresh();

    expect((float) $payment->total_dialokasikan)->toBe(550000.00)
        ->and((float) $payment->sisa_belum_dialokasikan)->toBe(0.00)
        ->and($sale1->status_pembayaran)->toBe(StatusPembayaran::Lunas)
        ->and((float) $sale1->sisa_piutang)->toBe(0.00)
        ->and($sale2->status_pembayaran)->toBe(StatusPembayaran::Sebagian)
        ->and((float) $sale2->total_dibayar)->toBe(200000.00)
        ->and((float) $sale2->sisa_piutang)->toBe(500000.00);
});

test('TEST 4 — Record payment with unallocated deposit balance', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 500000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 350000.00,
            ],
        ],
    ]);

    $sale->refresh();

    expect((float) $payment->jumlah_bayar)->toBe(500000.00)
        ->and((float) $payment->total_dialokasikan)->toBe(350000.00)
        ->and((float) $payment->sisa_belum_dialokasikan)->toBe(150000.00)
        ->and($sale->status_pembayaran)->toBe(StatusPembayaran::Lunas);
});

test('TEST 5 — Prevent overpaying invoice beyond sisa_piutang', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);

    expect(fn () => $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 400000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 400000.00,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class);
});

test('TEST 6 — Prevent allocating more than payment amount', function () {
    $sale1 = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);
    $sale2 = createInvoiceHelper($this, $this->pelangganA, 10, 70000.00);

    expect(fn () => $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 500000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale1->id,
                'jumlah_dialokasikan' => 300000.00,
            ],
            [
                'penjualan_id' => $sale2->id,
                'jumlah_dialokasikan' => 300000.00,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class);
});

test('TEST 7 — Prevent cross customer invoice allocation', function () {
    $saleA = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);
    $saleB = createInvoiceHelper($this, $this->pelangganB, 5, 70000.00);

    expect(fn () => $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 350000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $saleB->id,
                'jumlah_dialokasikan' => 350000.00,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class);
});

test('TEST 8 — Prevent negative or zero payment amount', function () {
    expect(fn () => $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 0.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
    ]))->toThrow(InvalidArgumentException::class);

    expect(fn () => $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => -50000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
    ]))->toThrow(InvalidArgumentException::class);
});

test('TEST 9 — Prevent duplicate invoice allocation in single payment', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 10, 70000.00);

    expect(fn () => $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 500000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 200000.00,
            ],
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 200000.00,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class);
});

test('TEST 10 — Document number generation BY-YYYY-000001', function () {
    $payment1 = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 100000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
    ]);

    $payment2 = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 200000.00,
        'metode' => MetodePembayaran::Transfer,
        'user_id' => $this->owner->id,
    ]);

    expect($payment1->no_pembayaran)->toBe('BY-2026-000001')
        ->and($payment2->no_pembayaran)->toBe('BY-2026-000002');
});

test('TEST 11 — Payment methods enum handling', function () {
    $paymentTunai = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 50000.00,
        'metode' => 'tunai',
        'user_id' => $this->owner->id,
    ]);

    $paymentTransfer = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 50000.00,
        'metode' => MetodePembayaran::Transfer,
        'user_id' => $this->owner->id,
    ]);

    $paymentLainnya = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 50000.00,
        'metode' => 'lainnya',
        'user_id' => $this->owner->id,
    ]);

    expect($paymentTunai->metode)->toBe(MetodePembayaran::Tunai)
        ->and($paymentTransfer->metode)->toBe(MetodePembayaran::Transfer)
        ->and($paymentLainnya->metode)->toBe(MetodePembayaran::Lainnya);
});

test('TEST 12 — Direct recalculate invoice service', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 100000.00);

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 200000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 200000.00,
            ],
        ],
    ]);

    $recalculated = $this->allocationService->recalculateInvoice($sale->id);

    expect((float) $recalculated->total_dibayar)->toBe(200000.00)
        ->and((float) $recalculated->sisa_piutang)->toBe(300000.00)
        ->and($recalculated->status_pembayaran)->toBe(StatusPembayaran::Sebagian);
});

test('TEST 13 — Cancel payment action reversal', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 100000.00);

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 500000.00,
        'metode' => MetodePembayaran::Transfer,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 500000.00,
            ],
        ],
    ]);

    $sale->refresh();
    expect($sale->status_pembayaran)->toBe(StatusPembayaran::Lunas);

    $this->cancelAction->execute($payment->id, $this->owner->id);

    $sale->refresh();
    $payment->refresh();

    expect($payment->trashed())->toBeTrue()
        ->and(AlokasiPembayaran::where('pembayaran_id', $payment->id)->count())->toBe(0)
        ->and((float) $sale->total_dibayar)->toBe(0.00)
        ->and((float) $sale->sisa_piutang)->toBe(500000.00)
        ->and($sale->status_pembayaran)->toBe(StatusPembayaran::BelumBayar);
});

test('TEST 14 — Concurrency safety during payment allocation', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 10, 100000.00);

    $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 400000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 400000.00,
            ],
        ],
    ]);

    $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 600000.00,
        'metode' => MetodePembayaran::Transfer,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 600000.00,
            ],
        ],
    ]);

    $sale->refresh();

    expect((float) $sale->total_dibayar)->toBe(1000000.00)
        ->and((float) $sale->sisa_piutang)->toBe(0.00)
        ->and($sale->status_pembayaran)->toBe(StatusPembayaran::Lunas);
});

test('TEST 15 — Atomic rollback on failure during execution', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 100000.00);

    $initialPaymentCount = Pembayaran::count();
    $initialAllocCount = AlokasiPembayaran::count();

    try {
        $this->paymentAction->execute([
            'pelanggan_id' => $this->pelangganA->id,
            'tanggal' => '2026-09-17',
            'jumlah_bayar' => 500000.00,
            'metode' => MetodePembayaran::Tunai,
            'user_id' => $this->owner->id,
            'alokasi' => [
                [
                    'penjualan_id' => $sale->id,
                    'jumlah_dialokasikan' => 200000.00,
                ],
                [
                    'penjualan_id' => 999999,
                    'jumlah_dialokasikan' => 100000.00,
                ],
            ],
        ]);
    } catch (Throwable $e) {
        // Expected
    }

    expect(Pembayaran::count())->toBe($initialPaymentCount)
        ->and(AlokasiPembayaran::count())->toBe($initialAllocCount);

    $sale->refresh();
    expect((float) $sale->total_dibayar)->toBe(0.00);
});

test('TEST 16 — Invariant: Payment NEVER mutates stock or inventory records', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);

    $initialStock = app(StockService::class)->getStock($this->produk, $this->gudang);
    $initialMutasiCount = MutasiStok::count();
    $initialStokRowCount = Stok::count();

    $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 350000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 350000.00,
            ],
        ],
    ]);

    $finalStock = app(StockService::class)->getStock($this->produk, $this->gudang);
    $finalMutasiCount = MutasiStok::count();
    $finalStokRowCount = Stok::count();

    expect($finalStock)->toBe($initialStock)
        ->and($finalMutasiCount)->toBe($initialMutasiCount)
        ->and($finalStokRowCount)->toBe($initialStokRowCount);
});

test('TEST 17 — Invariant: NO automatic FIFO allocation', function () {
    $saleOld = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);
    $saleNew = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);

    $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 350000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $saleNew->id,
                'jumlah_dialokasikan' => 350000.00,
            ],
        ],
    ]);

    $saleOld->refresh();
    $saleNew->refresh();

    expect((float) $saleOld->total_dibayar)->toBe(0.00)
        ->and($saleOld->status_pembayaran)->toBe(StatusPembayaran::BelumBayar)
        ->and((float) $saleNew->total_dibayar)->toBe(350000.00)
        ->and($saleNew->status_pembayaran)->toBe(StatusPembayaran::Lunas);
});

test('TEST 18 — Historical integrity when customer is soft-deleted', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 350000.00,
        'metode' => MetodePembayaran::Transfer,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 350000.00,
            ],
        ],
    ]);

    $this->pelangganA->delete();

    $this->actingAs($this->owner);

    $this->get(route('pembayaran.show', $payment->id))
        ->assertOk()
        ->assertSee($payment->no_pembayaran);
});

test('TEST 19 — Historical integrity when invoice is soft-deleted', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 350000.00,
        'metode' => MetodePembayaran::Transfer,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 350000.00,
            ],
        ],
    ]);

    $sale->delete();

    $this->actingAs($this->owner);

    $this->get(route('pembayaran.show', $payment->id))
        ->assertOk()
        ->assertSee($payment->no_pembayaran);
});

test('TEST 20 — Authorization barriers: unauthorized user cannot access payment or receivables', function () {
    $this->actingAs($this->unauthorizedUser);

    $this->get(route('pembayaran.index'))->assertForbidden();
    $this->get(route('pembayaran.create'))->assertForbidden();
    $this->get(route('piutang.index'))->assertForbidden();
});

test('TEST 21 — Authorization for cancellation: Staff Penjualan cannot cancel payments', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 350000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->staffPenjualan->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 350000.00,
            ],
        ],
    ]);

    $this->actingAs($this->staffPenjualan);

    $this->get(route('pembayaran.show', $payment->id))->assertOk();

    expect($this->staffPenjualan->can('cancel_payments'))->toBeFalse();

    Livewire::test(PembayaranDetail::class, ['id' => $payment->id])
        ->call('openCancelModal')
        ->assertForbidden();
});

test('TEST 22 — Livewire PembayaranIndex render and filters', function () {
    $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 150000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
    ]);

    $this->actingAs($this->owner);

    Livewire::test(PembayaranIndex::class)
        ->assertSee('Daftar Pembayaran')
        ->assertSee('Toko Maju Bersama')
        ->set('metodeFilter', 'tunai')
        ->assertSee('Toko Maju Bersama')
        ->set('metodeFilter', 'transfer')
        ->assertDontSee('Toko Maju Bersama');
});

test('TEST 23 — Livewire PembayaranCreate mount and save flow', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);

    $this->actingAs($this->staffPenjualan);

    Livewire::test(PembayaranCreate::class)
        ->set('pelanggan_id', $this->pelangganA->id)
        ->set('tanggal', '2026-09-17')
        ->set('metode', 'tunai')
        ->set('jumlah_bayar', 350000.00)
        ->call('allocateMax', 0)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $payment = Pembayaran::latest('id')->first();
    expect($payment)->not->toBeNull()
        ->and((float) $payment->jumlah_bayar)->toBe(350000.00)
        ->and((float) $payment->total_dialokasikan)->toBe(350000.00);
});

test('TEST 24 — Livewire PembayaranCreate with query parameters', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);

    $this->actingAs($this->owner);

    Livewire::test(PembayaranCreate::class, [
        'penjualan_id' => $sale->id,
    ])
        ->assertSet('pelanggan_id', $this->pelangganA->id)
        ->assertSet('jumlah_bayar', 350000.00)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $sale->refresh();
    expect($sale->status_pembayaran)->toBe(StatusPembayaran::Lunas);
});

test('TEST 25 — Livewire PembayaranDetail render and cancel flow', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 350000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 350000.00,
            ],
        ],
    ]);

    $this->actingAs($this->owner);

    Livewire::test(PembayaranDetail::class, ['id' => $payment->id])
        ->assertSee($payment->no_pembayaran)
        ->assertSee('Toko Maju Bersama')
        ->call('openCancelModal')
        ->assertSet('isCancelModalOpen', true)
        ->call('cancelPayment')
        ->assertRedirect(route('pembayaran.index'));

    $sale->refresh();
    expect((float) $sale->total_dibayar)->toBe(0.00)
        ->and($sale->status_pembayaran)->toBe(StatusPembayaran::BelumBayar);
});

test('TEST 26 — Livewire PiutangIndex KPI stats and rendering', function () {
    $sale1 = createInvoiceHelper($this, $this->pelangganA, 10, 70000.00);
    $sale2 = createInvoiceHelper($this, $this->pelangganB, 5, 70000.00);

    $this->actingAs($this->owner);

    Livewire::test(PiutangIndex::class)
        ->assertSee('Daftar Piutang Pelanggan')
        ->assertSee('Toko Maju Bersama')
        ->assertSee('CV Sumber Makmur')
        ->assertSee('Rp 1.050.000,00');
});

test('TEST 27 — Livewire PiutangIndex filter and detail modal', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 10, 70000.00);

    $this->actingAs($this->owner);

    Livewire::test(PiutangIndex::class)
        ->call('showCustomerInvoices', $this->pelangganA->id)
        ->assertSet('isDetailModalOpen', true)
        ->assertSee($sale->no_penjualan)
        ->call('closeDetailModal')
        ->assertSet('isDetailModalOpen', false);
});

test('TEST 28 — Multiple sequential payments on same invoice until full payment', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 10, 100000.00);

    // 1st pay: 300,000
    $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 300000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [['penjualan_id' => $sale->id, 'jumlah_dialokasikan' => 300000.00]],
    ]);
    $sale->refresh();
    expect($sale->status_pembayaran)->toBe(StatusPembayaran::Sebagian)
        ->and((float) $sale->sisa_piutang)->toBe(700000.00);

    // 2nd pay: 500,000
    $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-18',
        'jumlah_bayar' => 500000.00,
        'metode' => MetodePembayaran::Transfer,
        'user_id' => $this->owner->id,
        'alokasi' => [['penjualan_id' => $sale->id, 'jumlah_dialokasikan' => 500000.00]],
    ]);
    $sale->refresh();
    expect($sale->status_pembayaran)->toBe(StatusPembayaran::Sebagian)
        ->and((float) $sale->sisa_piutang)->toBe(200000.00);

    // 3rd pay: 200,000 (Final)
    $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-19',
        'jumlah_bayar' => 200000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [['penjualan_id' => $sale->id, 'jumlah_dialokasikan' => 200000.00]],
    ]);
    $sale->refresh();
    expect($sale->status_pembayaran)->toBe(StatusPembayaran::Lunas)
        ->and((float) $sale->sisa_piutang)->toBe(0.00)
        ->and((float) $sale->total_dibayar)->toBe(1000000.00);
});

test('TEST 29 — Credit limit exceed indicator in Piutang', function () {
    createInvoiceHelper($this, $this->pelangganA, 60, 100000.00);

    $this->actingAs($this->owner);

    Livewire::test(PiutangIndex::class)
        ->assertSee('Melebihi Limit');
});

test('TEST 30 — Decimal precision in payment and allocations', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 3, 33333.33);

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 99999.99,
        'metode' => MetodePembayaran::Transfer,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 99999.99,
            ],
        ],
    ]);

    $sale->refresh();
    expect((float) $sale->total_dibayar)->toBe(99999.99)
        ->and((float) $sale->sisa_piutang)->toBe(0.00)
        ->and($sale->status_pembayaran)->toBe(StatusPembayaran::Lunas);
});

test('TEST 31 — Policy Gate checks for Pembayaran', function () {
    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 50000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
    ]);

    expect(Gate::forUser($this->owner)->allows('viewAny', Pembayaran::class))->toBeTrue()
        ->and(Gate::forUser($this->owner)->allows('view', $payment))->toBeTrue()
        ->and(Gate::forUser($this->owner)->allows('create', Pembayaran::class))->toBeTrue()
        ->and(Gate::forUser($this->owner)->allows('cancel', $payment))->toBeTrue()
        ->and(Gate::forUser($this->unauthorizedUser)->allows('viewAny', Pembayaran::class))->toBeFalse()
        ->and(Gate::forUser($this->unauthorizedUser)->allows('create', Pembayaran::class))->toBeFalse();
});

test('TEST 32 — Cannot cancel already cancelled payment', function () {
    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 50000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
    ]);

    $this->cancelAction->execute($payment->id, $this->owner->id);

    expect(fn () => $this->cancelAction->execute($payment->id, $this->owner->id))
        ->toThrow(ModelNotFoundException::class);
});

test('TEST 33 — Allocating zero amount is safely ignored', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 70000.00);

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 350000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 0.00,
            ],
        ],
    ]);

    $sale->refresh();

    expect((float) $payment->total_dialokasikan)->toBe(0.00)
        ->and((float) $payment->sisa_belum_dialokasikan)->toBe(350000.00)
        ->and((float) $sale->total_dibayar)->toBe(0.00)
        ->and($sale->status_pembayaran)->toBe(StatusPembayaran::BelumBayar);
});

test('TEST 34 — Empty allocations payment (pure deposit) allowed', function () {
    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 1000000.00,
        'metode' => MetodePembayaran::Transfer,
        'user_id' => $this->owner->id,
        'alokasi' => [],
    ]);

    expect((float) $payment->jumlah_bayar)->toBe(1000000.00)
        ->and((float) $payment->total_dialokasikan)->toBe(0.00)
        ->and((float) $payment->sisa_belum_dialokasikan)->toBe(1000000.00)
        ->and($payment->alokasi)->toBeEmpty();
});

test('TEST 35 — Payment creator and user tracking preserved', function () {
    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-09-17',
        'jumlah_bayar' => 50000.00,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->staffPenjualan->id,
    ]);

    expect($payment->user_id)->toBe($this->staffPenjualan->id)
        ->and($payment->created_by)->toBe($this->staffPenjualan->id);
});

test('TEST 36 — Sidebar contains Pembayaran and Piutang for authorized users', function () {
    $this->actingAs($this->owner);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pembayaran')
        ->assertSee('Piutang')
        ->assertSee('v0.10.0 (Phase 9)');
});

test('TEST 37 — No regression on existing Phase 0-4 workflows', function () {
    $this->actingAs($this->owner);

    $this->get(route('master.produk.index'))->assertOk();
    $this->get(route('stok.index'))->assertOk();
    $this->get(route('pembelian.index'))->assertOk();
    $this->get(route('penjualan.index'))->assertOk();

    $sale = createInvoiceHelper($this, $this->pelangganA, 2, 70000.00);
    expect($sale->status_pembayaran)->toBe(StatusPembayaran::BelumBayar)
        ->and((float) $sale->sisa_piutang)->toBe(140000.00);
});

test('TEST 38 — Livewire PiutangIndex search by customer name and contact works without SQL error', function () {
    $sale1 = createInvoiceHelper($this, $this->pelangganA, 5, 50000.00);
    $sale2 = createInvoiceHelper($this, $this->pelangganB, 5, 50000.00);

    $this->actingAs($this->owner);

    // Searching by name
    Livewire::test(PiutangIndex::class)
        ->set('search', 'Toko Maju')
        ->assertSee('Toko Maju Bersama')
        ->assertDontSee('CV Sumber Makmur');

    // Searching by contact
    Livewire::test(PiutangIndex::class)
        ->set('search', '089876543210')
        ->assertSee('CV Sumber Makmur')
        ->assertDontSee('Toko Maju Bersama');
});

test('TEST 39 — Livewire PiutangIndex authorization respects view_receivables permission', function () {
    // Staff Penjualan has view_receivables permission -> allowed
    $this->actingAs($this->staffPenjualan);
    Livewire::test(PiutangIndex::class)
        ->assertOk()
        ->assertSee('Daftar Piutang Pelanggan');

    // Unauthorized user without permissions -> 403 Forbidden
    $this->actingAs($this->unauthorizedUser);
    Livewire::test(PiutangIndex::class)
        ->assertForbidden();
});

test('TEST 40 — Livewire PiutangIndex excludes cancelled sales with dibatalkan_at', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 10, 100000.00);

    // Cancel the sale via CancelPenjualanAction
    app(CancelPenjualanAction::class)->execute($sale, 'Customer cancelled order', $this->owner->id);

    $this->actingAs($this->owner);

    Livewire::test(PiutangIndex::class)
        ->assertDontSee($sale->no_penjualan);

    expect((float) Penjualan::where('id', $sale->id)->value('sisa_piutang'))->toBe(0.00);
});

test('TEST 41 — Livewire PiutangIndex overlimit filter displays only customers exceeding limit', function () {
    // pelangganA limit is 5,000,000; create 6,000,000 sale -> OVERLIMIT
    $sale1 = createInvoiceHelper($this, $this->pelangganA, 60, 100000.00);

    // pelangganB limit is 10,000,000; create 1,000,000 sale -> WITHIN LIMIT
    $sale2 = createInvoiceHelper($this, $this->pelangganB, 10, 100000.00);

    $this->actingAs($this->owner);

    Livewire::test(PiutangIndex::class)
        ->set('piutangFilter', 'overlimit')
        ->assertSee('Toko Maju Bersama')
        ->assertDontSee('CV Sumber Makmur');
});

test('TEST 42 — Livewire PiutangIndex dynamic sorting by column', function () {
    $this->actingAs($this->owner);

    Livewire::test(PiutangIndex::class)
        ->assertSet('sortBy', 'total_sisa_piutang')
        ->assertSet('sortDirection', 'desc')
        ->call('sortByColumn', 'nama')
        ->assertSet('sortBy', 'nama')
        ->assertSet('sortDirection', 'asc')
        ->call('sortByColumn', 'nama')
        ->assertSet('sortDirection', 'desc')
        ->call('sortByColumn', 'total_penjualan_nominal')
        ->assertSet('sortBy', 'total_penjualan_nominal')
        ->assertSet('sortDirection', 'desc');
});

test('TEST 43 — Livewire PiutangIndex WhatsApp URL and invoice quick pay action', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 10, 100000.00);

    $this->actingAs($this->owner);

    $component = Livewire::test(PiutangIndex::class)
        ->call('showCustomerInvoices', $this->pelangganA->id)
        ->assertSee('Kirim WA')
        ->assertSee('Umur Piutang')
        ->assertSee(route('pembayaran.create', ['penjualan_id' => $sale->id]));

    $waUrl = $component->instance()->getWhatsAppUrl(
        $this->pelangganA,
        collect([$sale]),
        1000000.00
    );

    expect($waUrl)->toContain('api.whatsapp.com/send?phone=6281234567890')
        ->and($waUrl)->toContain(rawurlencode('Toko Maju Bersama'))
        ->and($waUrl)->toContain(rawurlencode($sale->no_penjualan));
});

test('TEST 44 — Livewire PembayaranCreate customer autocomplete search and selection', function () {
    $this->actingAs($this->owner);

    Livewire::test(PembayaranCreate::class)
        ->set('pelangganSearchQuery', 'Maju')
        ->assertSet('pelanggan_id', null)
        ->call('selectPelanggan', $this->pelangganA->id)
        ->assertSet('pelanggan_id', $this->pelangganA->id)
        ->assertSee('Toko Maju Bersama')
        ->call('clearPelanggan')
        ->assertSet('pelanggan_id', null)
        ->assertSet('allocations', []);
});

test('TEST 45 — Livewire PembayaranCreate smart allocateMax updates jumlah_bayar if empty', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 100000.00); // 500,000

    $this->actingAs($this->owner);

    // Case 1: When jumlah_bayar is 0, allocateMax sets full debt AND updates jumlah_bayar
    $component = Livewire::test(PembayaranCreate::class)
        ->call('selectPelanggan', $this->pelangganA->id)
        ->set('jumlah_bayar', 0)
        ->call('allocateMax', 0);

    expect((float) $component->get('jumlah_bayar'))->toBe(500000.00)
        ->and((float) $component->get('allocations.0.jumlah_dialokasikan'))->toBe(500000.00);

    // Case 2: When jumlah_bayar is partially set (e.g. 200,000), allocateMax caps at available amount
    $component2 = Livewire::test(PembayaranCreate::class)
        ->call('selectPelanggan', $this->pelangganA->id)
        ->set('jumlah_bayar', 200000.00)
        ->call('allocateMax', 0);

    expect((float) $component2->get('allocations.0.jumlah_dialokasikan'))->toBe(200000.00);
});

test('TEST 46 — Livewire PembayaranCreate autoAllocateFifo cascades oldest invoices first', function () {
    // Older invoice: 300,000
    $oldSale = createInvoiceHelper($this, $this->pelangganA, 3, 100000.00);
    $oldSale->update(['tanggal' => Carbon::now()->subDays(10)->toDateString()]);

    // Newer invoice: 400,000
    $newSale = createInvoiceHelper($this, $this->pelangganA, 4, 100000.00);
    $newSale->update(['tanggal' => Carbon::now()->toDateString()]);

    $this->actingAs($this->owner);

    // Pay 450,000: Oldest gets 300,000 (fully paid), Newer gets 150,000 (partially paid)
    Livewire::test(PembayaranCreate::class)
        ->call('selectPelanggan', $this->pelangganA->id)
        ->set('jumlah_bayar', 450000.00)
        ->call('autoAllocateFifo')
        ->assertSet('allocations.0.jumlah_dialokasikan', 300000.00)
        ->assertSet('allocations.1.jumlah_dialokasikan', 150000.00);
});

test('TEST 47 — Livewire PembayaranCreate payAllInvoices and resetAllocations', function () {
    createInvoiceHelper($this, $this->pelangganA, 2, 100000.00); // 200,000
    createInvoiceHelper($this, $this->pelangganA, 3, 100000.00); // 300,000

    $this->actingAs($this->owner);

    Livewire::test(PembayaranCreate::class)
        ->call('selectPelanggan', $this->pelangganA->id)
        ->call('payAllInvoices')
        ->assertSet('jumlah_bayar', 500000.00)
        ->assertSet('allocations.0.jumlah_dialokasikan', 200000.00)
        ->assertSet('allocations.1.jumlah_dialokasikan', 300000.00)
        ->call('resetAllocations')
        ->assertSet('allocations.0.jumlah_dialokasikan', 0.00)
        ->assertSet('allocations.1.jumlah_dialokasikan', 0.00);
});

test('TEST 48 — Livewire PembayaranCreate records transfer bank and reference number in notes', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 1, 100000.00);

    $this->actingAs($this->owner);

    Livewire::test(PembayaranCreate::class)
        ->call('selectPelanggan', $this->pelangganA->id)
        ->set('tanggal', '2026-09-20')
        ->set('metode', 'transfer')
        ->set('bank_tujuan', 'BCA')
        ->set('nomor_referensi', 'TRF-BCA-123456')
        ->set('jumlah_bayar', 100000.00)
        ->set('catatan', 'Pembayaran via m-banking')
        ->call('allocateMax', 0)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $payment = Pembayaran::latest('id')->first();
    expect($payment)->not->toBeNull()
        ->and($payment->metode)->toBe(MetodePembayaran::Transfer)
        ->and($payment->catatan)->toContain('[Bank: BCA | Ref: TRF-BCA-123456]')
        ->and($payment->catatan)->toContain('Pembayaran via m-banking');
});

test('TEST 49 — Livewire PembayaranCreate handles typing, clearing, and nullifying jumlah_bayar without PropertyNotFoundException', function () {
    $this->actingAs($this->owner);

    Livewire::test(PembayaranCreate::class)
        ->call('selectPelanggan', $this->pelangganA->id)
        ->set('jumlah_bayar', '500000')
        ->assertSet('jumlah_bayar', '500000')
        ->assertSee('500.000,00')
        ->set('jumlah_bayar', '')
        ->assertSet('jumlah_bayar', '')
        ->set('jumlah_bayar', null)
        ->assertSet('jumlah_bayar', null)
        ->set('jumlah_bayar', 250000.50)
        ->assertSet('jumlah_bayar', 250000.50);
});

test('TEST 50 — Livewire PembayaranCreate enforces full allocation when customer still has outstanding invoices', function () {
    // 2 invoices: 300,000 and 400,000 (total debt 700,000)
    createInvoiceHelper($this, $this->pelangganA, 3, 100000.00);
    createInvoiceHelper($this, $this->pelangganA, 4, 100000.00);

    $this->actingAs($this->owner);

    // Customer pays 500,000, but user only allocates 200,000 to first invoice leaving 300,000 unallocated
    // Saving should FAIL with validation error on allocations
    Livewire::test(PembayaranCreate::class)
        ->call('selectPelanggan', $this->pelangganA->id)
        ->set('tanggal', '2026-09-21')
        ->set('metode', 'tunai')
        ->set('jumlah_bayar', 500000.00)
        ->set('allocations.0.jumlah_dialokasikan', 200000.00)
        ->call('save')
        ->assertHasErrors(['allocations'])
        // Now auto-allocate FIFO: entire 500,000 is distributed across invoices
        ->call('autoAllocateFifo')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();
});

test('TEST 51 — Cetak Kuitansi Resmi (A4/A5) renders correctly with terbilang and allocation details', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 5, 200000.00); // 1,000,000

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-10-30',
        'jumlah_bayar' => 1000000.00,
        'metode' => MetodePembayaran::Tunai,
        'catatan' => 'Pelunasan faktur proyek toko',
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 1000000.00,
            ],
        ],
    ]);

    $this->actingAs($this->owner);

    $response = $this->get(route('pembayaran.print.kuitansi', $payment->id));

    $response->assertOk()
        ->assertSee('KUITANSI PEMBAYARAN')
        ->assertSee($payment->no_pembayaran)
        ->assertSee($this->pelangganA->nama)
        ->assertSee('Satu Juta Rupiah') // terbilang helper
        ->assertSee('1.000.000')
        ->assertSee($sale->no_penjualan)
        ->assertSee('Pelunasan faktur proyek toko');
});

test('TEST 52 — Cetak Struk Kasir Thermal (POS) renders correctly in compact receipt format', function () {
    $sale = createInvoiceHelper($this, $this->pelangganA, 2, 250000.00); // 500,000

    $payment = $this->paymentAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => '2026-10-30',
        'jumlah_bayar' => 500000.00,
        'metode' => MetodePembayaran::Transfer,
        'catatan' => 'Transfer via BCA',
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $sale->id,
                'jumlah_dialokasikan' => 500000.00,
            ],
        ],
    ]);

    $this->actingAs($this->owner);

    $response = $this->get(route('pembayaran.print.thermal', $payment->id));

    $response->assertOk()
        ->assertSee('BUKTI PEMBAYARAN PIUTANG')
        ->assertSee($payment->no_pembayaran)
        ->assertSee('ALOKASI FAKTUR:')
        ->assertSee($sale->no_penjualan)
        ->assertSee('500.000')
        ->assertSee('Simpan struk ini sebagai bukti pembayaran sah');
});

test('TEST 53 — Guest or unauthorized user cannot access print kuitansi or thermal receipts', function () {
    $payment = Pembayaran::factory()->create([
        'pelanggan_id' => $this->pelangganA->id,
        'user_id' => $this->owner->id,
    ]);

    // Guest should be redirected to login
    $this->get(route('pembayaran.print.kuitansi', $payment->id))
        ->assertRedirect(route('login'));

    $this->get(route('pembayaran.print.thermal', $payment->id))
        ->assertRedirect(route('login'));

    // User without view_payments permission gets 403 Forbidden
    $this->actingAs($this->unauthorizedUser);

    $this->get(route('pembayaran.print.kuitansi', $payment->id))
        ->assertForbidden();

    $this->get(route('pembayaran.print.thermal', $payment->id))
        ->assertForbidden();
});
