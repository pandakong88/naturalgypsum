<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Purchasing\Actions\CancelPembelianAction;
use App\Domains\Purchasing\Actions\CreatePembelianAction;
use App\Domains\Purchasing\Actions\DeletePembelianPOAction;
use App\Domains\Purchasing\Actions\ReceivePembelianAction;
use App\Domains\Purchasing\Actions\RecordPembayaranPembelianAction;
use App\Domains\Purchasing\Actions\UpdatePembelianPOAction;
use App\Domains\Purchasing\Actions\UpdateShippingDocsAction;
use App\Domains\Purchasing\Enums\StatusPembayaranPembelian;
use App\Domains\Purchasing\Enums\StatusPembelian;
use App\Domains\Purchasing\Models\Pembelian;
use App\Livewire\Purchasing\PembelianDetail;
use App\Livewire\Purchasing\PembelianEdit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->supplier = Supplier::factory()->create(['nama_supplier' => 'PT Vifon Gemafon Indonesia']);
    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Vifon']);

    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);

    $this->plafonProduk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Vifon 01 Doff 4 Meter',
        'satuan' => SatuanProduk::Meter,
        'harga_beli' => 14000.00,
        'harga_jual' => 17000.00,
    ]);

    $this->createAction = app(CreatePembelianAction::class);
    $this->receiveAction = app(ReceivePembelianAction::class);
    $this->paymentAction = app(RecordPembayaranPembelianAction::class);
});

test('WORKFLOW 1: creating PO in ordered status does not increase warehouse stock or mutasi', function () {
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Ordered,
        'status_pembayaran' => StatusPembayaranPembelian::Tempo,
        'tanggal_jatuh_tempo' => '2026-12-19',
        'no_faktur_supplier' => 'IT124280-130726-3',
        'no_surat_jalan' => 'SJ128402-031126-1',
        'no_polisi' => 'B 9812 UIK',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 400.0,
                'qty_lembar' => 100,
                'panjang_meter' => 4.0,
                'harga_bruto' => 16000.0,
                'diskon_satuan' => 2000.0,
                'harga_beli_satuan' => 14000.0,
            ],
        ],
    ]);

    expect($pembelian)->toBeInstanceOf(Pembelian::class)
        ->and($pembelian->status)->toBe(StatusPembelian::Ordered)
        ->and($pembelian->isOrdered())->toBeTrue()
        ->and($pembelian->isReceived())->toBeFalse()
        ->and($pembelian->no_faktur_supplier)->toBe('IT124280-130726-3')
        ->and($pembelian->no_surat_jalan)->toBe('SJ128402-031126-1')
        ->and($pembelian->no_polisi)->toBe('B 9812 UIK')
        ->and((float) $pembelian->total)->toBe(5600000.00) // 400m * 14000
        ->and((float) $pembelian->sisa_hutang)->toBe(5600000.00);

    // Assert that stock is NOT increased yet
    $stok = Stok::where('produk_id', $this->plafonProduk->id)
        ->where('gudang_id', $this->gudang->id)
        ->first();

    expect($stok)->toBeNull();

    // Assert that mutasi stok is NOT created yet
    $mutasiCount = MutasiStok::where('referensi', $pembelian->no_pembelian)->count();
    expect($mutasiCount)->toBe(0);
});

test('WORKFLOW 2: receiving PO atomically increases warehouse stock and logs mutasi_stok', function () {
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Ordered,
        'status_pembayaran' => StatusPembayaranPembelian::Tempo,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 200.0,
                'harga_beli_satuan' => 14000.0,
            ],
        ],
    ]);

    expect($pembelian->isOrdered())->toBeTrue();

    // Receive the goods when the delivery arrives
    $receivedPembelian = $this->receiveAction->execute(
        pembelian: $pembelian,
        userId: $this->owner->id,
        noSuratJalan: 'SJ-MASUK-001',
        noFakturSupplier: 'INV-SUP-001',
        noPolisi: 'BK 1234 XY',
        tanggalDiterima: '2026-11-20'
    );

    expect($receivedPembelian->status)->toBe(StatusPembelian::Received)
        ->and($receivedPembelian->isReceived())->toBeTrue()
        ->and($receivedPembelian->isOrdered())->toBeFalse()
        ->and($receivedPembelian->no_surat_jalan)->toBe('SJ-MASUK-001')
        ->and($receivedPembelian->no_faktur_supplier)->toBe('INV-SUP-001')
        ->and($receivedPembelian->no_polisi)->toBe('BK 1234 XY')
        ->and($receivedPembelian->tanggal_diterima->format('Y-m-d'))->toBe('2026-11-20')
        ->and($receivedPembelian->diterima_by)->toBe($this->owner->id);

    // Assert that stock is now in the warehouse
    $stok = Stok::where('produk_id', $this->plafonProduk->id)
        ->where('gudang_id', $this->gudang->id)
        ->first();

    expect($stok)->not->toBeNull()
        ->and((float) $stok->jumlah)->toBe(200.000);

    // Assert that mutasi stok is recorded with PembelianMasuk
    $this->assertDatabaseHas('mutasi_stok', [
        'produk_id' => $this->plafonProduk->id,
        'gudang_id' => $this->gudang->id,
        'jenis' => JenisMutasiStok::PembelianMasuk->value,
        'jumlah' => 200.000,
        'referensi' => $pembelian->no_pembelian,
    ]);
});

test('WORKFLOW 3: cannot receive an already received or cancelled PO', function () {
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Received,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 10.0,
                'harga_beli_satuan' => 14000.0,
            ],
        ],
    ]);

    expect(fn () => $this->receiveAction->execute($pembelian, $this->owner->id))
        ->toThrow(InvalidArgumentException::class);
});

test('WORKFLOW 4: partial down payment (DP) and debt installment tracking', function () {
    // Total order: 100 lembar x 4m = 400m @14.000 = 5.600.000
    // DP paid upfront: 2.000.000 -> Sisa hutang: 3.600.000
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Received,
        'status_pembayaran' => StatusPembayaranPembelian::Sebagian,
        'nominal_bayar' => 2000000.0,
        'metode_pembayaran' => 'transfer',
        'tanggal_jatuh_tempo' => '2026-12-19',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 400.0,
                'harga_beli_satuan' => 14000.0,
            ],
        ],
    ]);

    expect($pembelian->status_pembayaran)->toBe(StatusPembayaranPembelian::Sebagian)
        ->and((float) $pembelian->nominal_bayar)->toBe(2000000.00)
        ->and((float) $pembelian->sisa_hutang)->toBe(3600000.00)
        ->and($pembelian->pembayaran)->toHaveCount(1)
        ->and((float) $pembelian->pembayaran->first()->nominal)->toBe(2000000.00);

    // Record installment 1: 1.600.000 -> Sisa hutang: 2.000.000 (still Sebagian)
    $this->paymentAction->execute(
        pembelian: $pembelian,
        userId: $this->owner->id,
        nominal: 1600000.0,
        tanggalBayar: '2026-11-25',
        metodePembayaran: 'transfer',
        keterangan: 'Cicilan 1 via BCA'
    );

    $pembelian->refresh();
    expect($pembelian->status_pembayaran)->toBe(StatusPembayaranPembelian::Sebagian)
        ->and((float) $pembelian->nominal_bayar)->toBe(3600000.00)
        ->and((float) $pembelian->sisa_hutang)->toBe(2000000.00)
        ->and($pembelian->pembayaran)->toHaveCount(2);

    // Record final installment: 2.000.000 -> Sisa hutang: 0 (Status becomes Lunas)
    $this->paymentAction->execute(
        pembelian: $pembelian,
        userId: $this->owner->id,
        nominal: 2000000.0,
        tanggalBayar: '2026-12-05',
        metodePembayaran: 'cash',
        keterangan: 'Pelunasan sisa hutang'
    );

    $pembelian->refresh();
    expect($pembelian->status_pembayaran)->toBe(StatusPembayaranPembelian::Lunas)
        ->and($pembelian->isLunas())->toBeTrue()
        ->and((float) $pembelian->nominal_bayar)->toBe(5600000.00)
        ->and((float) $pembelian->sisa_hutang)->toBe(0.00)
        ->and($pembelian->pembayaran)->toHaveCount(3);
});

test('WORKFLOW 5: cannot pay more than remaining supplier debt', function () {
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Received,
        'status_pembayaran' => StatusPembayaranPembelian::Tempo,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 10.0,
                'harga_beli_satuan' => 10000.0, // Total 100.000
            ],
        ],
    ]);

    expect((float) $pembelian->sisa_hutang)->toBe(100000.00);

    expect(fn () => $this->paymentAction->execute(
        pembelian: $pembelian,
        userId: $this->owner->id,
        nominal: 150000.0, // Overpaying
        tanggalBayar: '2026-11-20',
        metodePembayaran: 'cash'
    ))->toThrow(InvalidArgumentException::class);
});

test('WORKFLOW 6: Livewire PembelianDetail modal receive goods and record payment', function () {
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Ordered,
        'status_pembayaran' => StatusPembayaranPembelian::Tempo,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 50.0,
                'harga_beli_satuan' => 14000.0,
            ],
        ],
    ]);

    $this->actingAs($this->owner);

    // Test Receive Goods via Livewire
    Livewire::test(PembelianDetail::class, ['id' => $pembelian->id])
        ->call('openReceiveModal')
        ->assertSet('showReceiveModal', true)
        ->set('receive_no_surat_jalan', 'SJ-LIVEWIRE-99')
        ->set('receive_no_polisi', 'BK 9999 AA')
        ->set('receive_tanggal_diterima', '2026-11-20')
        ->call('processReceive')
        ->assertSet('showReceiveModal', false)
        ->assertSee('SJ-LIVEWIRE-99')
        ->assertSee('BK 9999 AA');

    $pembelian->refresh();
    expect($pembelian->isReceived())->toBeTrue()
        ->and($pembelian->no_surat_jalan)->toBe('SJ-LIVEWIRE-99');

    // Test Record Payment via Livewire
    Livewire::test(PembelianDetail::class, ['id' => $pembelian->id])
        ->call('openPaymentModal')
        ->assertSet('showPaymentModal', true)
        ->set('pay_nominal', 700000.0) // Full payment
        ->set('pay_tanggal_bayar', '2026-11-20')
        ->set('pay_metode_pembayaran', 'transfer')
        ->set('pay_keterangan', 'Transfer Bank Mandiri')
        ->call('processPayment')
        ->assertSet('showPaymentModal', false);

    $pembelian->refresh();
    expect($pembelian->isLunas())->toBeTrue()
        ->and((float) $pembelian->sisa_hutang)->toBe(0.00);
});

test('WORKFLOW 7: purchase order (PO) print view renders correctly', function () {
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Ordered,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 100.0,
                'qty_lembar' => 25,
                'panjang_meter' => 4.0,
                'harga_beli_satuan' => 14000.0,
            ],
        ],
    ]);

    $this->actingAs($this->owner);

    $response = $this->get(route('pembelian.print.po', $pembelian->id));

    $response->assertOk()
        ->assertSee('SURAT PESANAN (PURCHASE ORDER)')
        ->assertSee($pembelian->no_pembelian)
        ->assertSee($this->supplier->nama_supplier)
        ->assertSee($this->plafonProduk->nama)
        ->assertSee('Qty Dipesan')
        ->assertSee('Instruksi Pemesanan & Pengiriman:', false)
        ->assertSee('Dibuat Oleh (Purchasing)');
});

test('WORKFLOW 8: updating an ordered PO recalculates total and items without touching stock', function () {
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Ordered,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 100.0,
                'harga_beli_satuan' => 14000.0,
            ],
        ],
    ]);

    expect((float) $pembelian->total)->toBe(1400000.00);

    $updateAction = app(UpdatePembelianPOAction::class);
    $updated = $updateAction->execute($pembelian, [
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-20',
        'keterangan' => 'Revisi jumlah PO ditambah',
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 200.0,
                'harga_beli_satuan' => 13500.0, // Mendapat diskon volume
            ],
        ],
    ]);

    expect((float) $updated->total)->toBe(2700000.00)
        ->and((float) $updated->sisa_hutang)->toBe(2700000.00)
        ->and($updated->keterangan)->toBe('Revisi jumlah PO ditambah')
        ->and($updated->details->count())->toBe(1)
        ->and((float) $updated->details->first()->jumlah)->toBe(200.0);

    // Assert that inventory stock is STILL NOT touched
    $stok = Stok::where('produk_id', $this->plafonProduk->id)->where('gudang_id', $this->gudang->id)->first();
    expect($stok)->toBeNull();
});

test('WORKFLOW 9: editing PO via PembelianEdit Livewire component', function () {
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Ordered,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 50.0,
                'harga_beli_satuan' => 14000.0,
            ],
        ],
    ]);

    $this->actingAs($this->owner);

    Livewire::test(PembelianEdit::class, ['id' => $pembelian->id])
        ->assertSet('supplier_id', $this->supplier->id)
        ->assertSee('Edit Pesanan Pembelian (PO)')
        ->set('items.0.jumlah', 150.0)
        ->call('save')
        ->assertRedirect(route('pembelian.show', $pembelian->id));

    $pembelian->refresh();
    expect((float) $pembelian->total)->toBe(2100000.00) // 150 * 14000
        ->and((float) $pembelian->details->first()->jumlah)->toBe(150.0);
});

test('WORKFLOW 10: cancelling an ordered PO updates status to cancelled and cancels debt', function () {
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Ordered,
        'status_pembayaran' => StatusPembayaranPembelian::Tempo,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 100.0,
                'harga_beli_satuan' => 14000.0,
            ],
        ],
    ]);

    $cancelAction = app(CancelPembelianAction::class);
    $cancelled = $cancelAction->execute($pembelian, 'Supplier kekurangan bahan baku');

    expect($cancelled->status)->toBe(StatusPembelian::Cancelled)
        ->and($cancelled->isCancelled())->toBeTrue()
        ->and((float) $cancelled->sisa_hutang)->toBe(0.00)
        ->and($cancelled->keterangan)->toContain('[Dibatalkan: Supplier kekurangan bahan baku]');

    // Assert that inventory stock is untouched
    $stok = Stok::where('produk_id', $this->plafonProduk->id)->where('gudang_id', $this->gudang->id)->first();
    expect($stok)->toBeNull();
});

test('WORKFLOW 11: cancelling a received purchase verifies stock and reverts inventory via ReturKeSupplier', function () {
    // 1. Create a received purchase
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Received,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 80.0,
                'harga_beli_satuan' => 14000.0,
            ],
        ],
    ]);

    // Stock should be 80.0
    $stok = Stok::where('produk_id', $this->plafonProduk->id)->where('gudang_id', $this->gudang->id)->first();
    expect((float) $stok->jumlah)->toBe(80.0);

    // 2. Cancel the received purchase
    $cancelAction = app(CancelPembelianAction::class);
    $cancelled = $cancelAction->execute($pembelian, 'Kualitas barang tidak sesuai spesifikasi pabrik');

    expect($cancelled->status)->toBe(StatusPembelian::Cancelled)
        ->and((float) $cancelled->sisa_hutang)->toBe(0.00);

    // Stock should now be reverted back to 0.0
    $stok->refresh();
    expect((float) $stok->jumlah)->toBe(0.0);

    // Mutation should record ReturKeSupplier
    $mutation = MutasiStok::where('referensi', "BATAL-{$pembelian->no_pembelian}")->first();
    expect($mutation)->not->toBeNull()
        ->and($mutation->jenis)->toBe(JenisMutasiStok::ReturKeSupplier)
        ->and((float) $mutation->jumlah)->toBe(-80.0);
});

test('WORKFLOW 12: cancelling received purchase fails if warehouse stock is already insufficient', function () {
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Received,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 100.0,
                'harga_beli_satuan' => 14000.0,
            ],
        ],
    ]);

    // Manually reduce stock to simulate goods being sold or used
    $stok = Stok::where('produk_id', $this->plafonProduk->id)->where('gudang_id', $this->gudang->id)->first();
    $stok->update(['jumlah' => 20.0]); // Only 20 left, but 100 needed to return

    $cancelAction = app(CancelPembelianAction::class);

    expect(fn () => $cancelAction->execute($pembelian, 'Retur'))
        ->toThrow(InvalidArgumentException::class, 'tidak mencukupi untuk menarik kembali');
});

test('WORKFLOW 13: deleting an un-received PO permanently removes it when no payment exists', function () {
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Ordered,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 50.0,
                'harga_beli_satuan' => 14000.0,
            ],
        ],
    ]);

    $pembelianId = $pembelian->id;
    $deleteAction = app(DeletePembelianPOAction::class);
    $result = $deleteAction->execute($pembelian, $this->owner->id);

    expect($result)->toBeTrue();
    expect(Pembelian::find($pembelianId))->toBeNull();
});

test('WORKFLOW 14: updating shipping/logistics documents on purchase', function () {
    $pembelian = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-11-19',
        'status' => StatusPembelian::Received,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->plafonProduk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 50.0,
                'harga_beli_satuan' => 14000.0,
            ],
        ],
    ]);

    $shippingAction = app(UpdateShippingDocsAction::class);
    $updated = $shippingAction->execute($pembelian, [
        'no_surat_jalan' => 'SJ-UPDATE-123',
        'no_faktur_supplier' => 'FAK-UPDATE-456',
        'no_polisi' => 'B 1234 XYZ',
        'no_kontainer' => 'CONT-999',
        'tanggal_jatuh_tempo' => '2026-12-25',
    ], $this->owner->id);

    expect($updated->no_surat_jalan)->toBe('SJ-UPDATE-123')
        ->and($updated->no_faktur_supplier)->toBe('FAK-UPDATE-456')
        ->and($updated->no_polisi)->toBe('B 1234 XYZ')
        ->and($updated->no_kontainer)->toBe('CONT-999')
        ->and($updated->tanggal_jatuh_tempo->toDateString())->toBe('2026-12-25');
});
