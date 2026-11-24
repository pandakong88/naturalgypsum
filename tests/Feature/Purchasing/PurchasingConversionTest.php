<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Purchasing\Enums\StatusPembelian;
use App\Domains\Purchasing\Models\Pembelian;
use App\Livewire\Purchasing\PembelianCreate;
use App\Livewire\Purchasing\PembelianDetail;
use App\Livewire\Purchasing\PembelianEdit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->supplier = Supplier::factory()->create(['nama_supplier' => 'PT Vifon Indo Prima']);
    $this->kategoriPvc = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);
    $this->kategoriHollow = Kategori::factory()->create(['nama_kategori' => 'Besi Hollow']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'VIFON']);
    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);

    // Standardized Plafon PVC (Satuan = Meter, Panjang = 4m, Kemasan = Dus, Isi = 15 lbr)
    $this->pvcProduk = Produk::factory()->create([
        'kategori_id' => $this->kategoriPvc->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Plafon PVC Motif Kayu 4m',
        'satuan' => SatuanProduk::Meter,
        'panjang_meter' => 4.00,
        'kemasan' => 'Dus',
        'isi_per_kemasan' => 15.00,
        'harga_beli' => 12500.00,
    ]);

    // Standardized Hollow (Satuan = Batang, Panjang = 4m, Kemasan = Ikat, Isi = 20 btg)
    $this->hollowProduk = Produk::factory()->create([
        'kategori_id' => $this->kategoriHollow->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Hollow Galvanis 2x4 4m',
        'satuan' => SatuanProduk::Batang,
        'panjang_meter' => 4.00,
        'kemasan' => 'Ikat',
        'isi_per_kemasan' => 20.00,
        'harga_beli' => 22000.00,
    ]);
});

test('pembelian create initializes PVC with length and packaging attributes and computes physical badge', function () {
    $component = Livewire::actingAs($this->owner)
        ->test(PembelianCreate::class)
        ->call('addItem', $this->pvcProduk->id);

    $items = $component->get('items');
    expect($items)->toHaveCount(1)
        ->and((int) $items[0]['produk_id'])->toBe($this->pvcProduk->id)
        ->and((float) $items[0]['panjang_meter'])->toBe(4.0)
        ->and($items[0]['kemasan'])->toBe('Dus')
        ->and((float) $items[0]['isi_per_kemasan'])->toBe(15.0)
        ->and((float) $items[0]['qty_lembar'])->toBe(1.0)
        ->and((float) $items[0]['jumlah'])->toBe(4.0);

    // Test physical badge computation
    $badge = $component->instance()->getItemPhysicalBadge($items[0]);
    expect($badge)->toBe('± 1 lbr (1 lbr)');
});

test('pembelian create calculator modal supports dus_pvc mode converting dus to lembar and meter', function () {
    $component = Livewire::actingAs($this->owner)
        ->test(PembelianCreate::class)
        ->call('addItem', $this->pvcProduk->id)
        ->call('openCalculator', 0);

    expect($component->get('isCalculatorModalOpen'))->toBeTrue()
        ->and($component->get('calcMode'))->toBe('dus_pvc')
        ->and($component->get('packLabel'))->toBe('Dus')
        ->and((float) $component->get('packSize'))->toBe(15.0)
        ->and((float) $component->get('pvcPanjang'))->toBe(4.0);

    // User wants to order 10 Dus from factory
    $component->set('packCount', 10)
        ->call('applyCalculation');

    $items = $component->get('items');
    // 10 dus * 15 lbr = 150 lbr, 150 lbr * 4m = 600m
    expect((float) $items[0]['qty_lembar'])->toBe(150.0)
        ->and((float) $items[0]['panjang_meter'])->toBe(4.0)
        ->and((float) $items[0]['jumlah'])->toBe(600.0)
        ->and((float) $items[0]['subtotal'])->toBe(7500000.0); // 600m * 12.500

    // Physical badge should show 150 lbr (10 dus pas)
    $badge = $component->instance()->getItemPhysicalBadge($items[0]);
    expect($badge)->toBe('± 150 lbr (10 dus pas)');
});

test('pembelian create calculator modal supports factory packaging mode for non-PVC items', function () {
    $component = Livewire::actingAs($this->owner)
        ->test(PembelianCreate::class)
        ->call('addItem', $this->hollowProduk->id)
        ->call('openCalculator', 0);

    expect($component->get('isCalculatorModalOpen'))->toBeTrue()
        ->and($component->get('calcMode'))->toBe('kemasan')
        ->and($component->get('packLabel'))->toBe('Ikat')
        ->and((float) $component->get('packSize'))->toBe(20.0);

    // User orders 5 ikat
    $component->set('packCount', 5)
        ->call('applyCalculation');

    $items = $component->get('items');
    // 5 ikat * 20 btg = 100 btg
    expect((float) $items[0]['jumlah'])->toBe(100.0)
        ->and((float) $items[0]['subtotal'])->toBe(2200000.0); // 100 * 22.000

    $badge = $component->instance()->getItemPhysicalBadge($items[0]);
    expect($badge)->toBe('± 5 Ikat (@ 20 Batang (btg))');
});

test('pembelian detail and print views display physical conversion and packaging details', function () {
    // Create a PO with 150 lembar (600m) PVC
    $pembelian = Pembelian::factory()->create([
        'supplier_id' => $this->supplier->id,
        'status' => StatusPembelian::Ordered,
        'total' => 7500000.00,
    ]);

    $pembelian->details()->create([
        'produk_id' => $this->pvcProduk->id,
        'gudang_id' => $this->gudang->id,
        'qty_lembar' => 150.0,
        'panjang_meter' => 4.0,
        'jumlah' => 600.0,
        'harga_beli_satuan' => 12500.00,
        'subtotal' => 7500000.00,
    ]);

    // Test PembelianDetail Livewire component
    Livewire::actingAs($this->owner)
        ->test(PembelianDetail::class, ['id' => $pembelian->id])
        ->assertSee('150 lbr &times; 4 m', false)
        ->assertSee('10 dus pas');

    // Test Print PO View
    $response = $this->actingAs($this->owner)
        ->get(route('pembelian.print.po', $pembelian->id));

    $response->assertOk()
        ->assertSee('150 lbr &times; 4m', false)
        ->assertSee('10 dus pas');
});

test('pembelian edit supports editing and converting units seamlessly', function () {
    $pembelian = Pembelian::factory()->create([
        'supplier_id' => $this->supplier->id,
        'status' => StatusPembelian::Ordered,
        'total' => 7500000.00,
    ]);

    $pembelian->details()->create([
        'produk_id' => $this->pvcProduk->id,
        'gudang_id' => $this->gudang->id,
        'qty_lembar' => 150.0,
        'panjang_meter' => 4.0,
        'jumlah' => 600.0,
        'harga_beli_satuan' => 12500.00,
        'subtotal' => 7500000.00,
    ]);

    $component = Livewire::actingAs($this->owner)
        ->test(PembelianEdit::class, ['id' => $pembelian->id]);

    $items = $component->get('items');
    expect($items)->toHaveCount(1)
        ->and((float) $items[0]['qty_lembar'])->toBe(150.0)
        ->and((float) $items[0]['panjang_meter'])->toBe(4.0)
        ->and((float) $items[0]['jumlah'])->toBe(600.0)
        ->and($items[0]['kemasan'])->toBe('Dus')
        ->and((float) $items[0]['isi_per_kemasan'])->toBe(15.0);

    // Open calculator and change to 12 dus
    $component->call('openCalculator', 0)
        ->set('packCount', 12)
        ->call('applyCalculation');

    $updatedItems = $component->get('items');
    // 12 dus * 15 lbr = 180 lbr, 180 lbr * 4m = 720m
    expect((float) $updatedItems[0]['qty_lembar'])->toBe(180.0)
        ->and((float) $updatedItems[0]['jumlah'])->toBe(720.0);
});

test('pembelian calculator for non-roll items like dome lamp never defaults to roll mode and blade does not show roll tab', function () {
    $kategoriKubah = Kategori::factory()->create(['nama_kategori' => 'Kubah Lampu']);
    $kubahProduk = Produk::factory()->create([
        'kategori_id' => $kategoriKubah->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Dome Kubah Lampu Gypsum Polos 50cm',
        'satuan' => SatuanProduk::Pcs,
        'kemasan' => 'Kotak',
        'isi_per_kemasan' => 1.00,
        'harga_beli' => 75000.00,
    ]);

    $component = Livewire::actingAs($this->owner)
        ->test(PembelianCreate::class)
        ->call('addItem', $kubahProduk->id)
        ->call('openCalculator', 0);

    expect($component->get('isCalculatorModalOpen'))->toBeTrue()
        ->and($component->get('calcMode'))->toBe('kemasan')
        ->and($component->get('packLabel'))->toBe('Kotak')
        ->and($component->get('calcTargetUnit'))->toBe('Pcs (buah)');

    // Ensure the blade view shows Kotak and NEVER shows Roll ke Meter
    $component->assertSee('Kemasan: <strong>Kotak</strong>', false)
        ->assertDontSee('Rincian Konversi Roll');
});

test('pembelian calculator for roll items correctly sets roll mode', function () {
    $kategoriAksesoris = Kategori::factory()->create(['nama_kategori' => 'Aksesoris']);
    $foilProduk = Produk::factory()->create([
        'kategori_id' => $kategoriAksesoris->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Bubble Foil Single Metalizing',
        'satuan' => SatuanProduk::Meter,
        'panjang_per_roll' => 25.00,
        'isi_per_kemasan' => 25.00,
        'kemasan' => 'Roll',
        'harga_beli' => 5000.00,
    ]);

    $component = Livewire::actingAs($this->owner)
        ->test(PembelianCreate::class)
        ->call('addItem', $foilProduk->id)
        ->call('openCalculator', 0);

    expect($component->get('isCalculatorModalOpen'))->toBeTrue()
        ->and($component->get('calcMode'))->toBe('roll')
        ->and((float) $component->get('rollLength'))->toBe(25.0);

    $component->assertSee('Rincian Konversi Roll ke Satuan Meter Sistem:')
        ->assertDontSee('Dus Plafon PVC');
});

test('pembelian create and calculator for roll products like kasa gypsum handles roll length without pvc double multiplication', function () {
    $kategoriAksesoris = Kategori::factory()->create(['nama_kategori' => 'Aksesoris']);
    $kasaRollProduk = Produk::factory()->create([
        'kategori_id' => $kategoriAksesoris->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Kasa Gypsum Fiber Mesh Tape Perekat 2 inch x 90m',
        'kode_katalog' => 'KSA-GY-090',
        'satuan' => SatuanProduk::Meter,
        'panjang_meter' => null,
        'panjang_per_roll' => 90.00,
        'kemasan' => 'Roll',
        'isi_per_kemasan' => 90.00,
        'harga_beli' => 17500.00,
    ]);

    $component = Livewire::actingAs($this->owner)
        ->test(PembelianCreate::class)
        ->call('addItem', $kasaRollProduk->id);

    $items = $component->get('items');
    expect($items)->toHaveCount(1)
        ->and((float) $items[0]['jumlah'])->toBe(1.0)
        ->and($items[0]['qty_lembar'])->toBeNull()
        ->and($items[0]['panjang_meter'])->toBeNull();

    // Line item must NOT render Plafon PVC sheet input (Lbr x Pjg)
    $component->assertDontSee('lbr')
        ->assertDontSee('&times;');

    // Open calculator
    $component->call('openCalculator', 0);
    expect($component->get('isCalculatorModalOpen'))->toBeTrue()
        ->and($component->get('calcMode'))->toBe('roll')
        ->and((float) $component->get('rollLength'))->toBe(90.0)
        ->and($component->get('calcPanjangMeter'))->toBeNull()
        ->and((float) $component->get('calcIsiPerKemasan'))->toBe(90.0);

    // Modal badges should show 1 Roll = 90 Meter and NOT 8100 Meter or Lembar
    $component->assertSee('Panjang per Roll: <strong>90 Meter</strong>', false)
        ->assertSee('1 Roll = <strong>90 Meter</strong>', false)
        ->assertDontSee('8100 Meter')
        ->assertDontSee('Lembar/lembar');

    // Enter 1 Roll Utuh + 6 Meter Eceran
    $component->set('calcJmlKemasan', 1)
        ->set('calcJmlEceran', 6);

    // Computed physical stock must be 96m (1 * 90 + 6), NOT 8640m!
    expect((float) $component->get('computedPhysicalStock'))->toBe(96.0);

    $component->assertSee('Total Masuk ke PO:')
        ->assertSee('96,00 m');

    // Apply calculation
    $component->call('applyCalculation');

    $updatedItems = $component->get('items');
    expect((float) $updatedItems[0]['jumlah'])->toBe(96.0)
        ->and($updatedItems[0]['qty_lembar'])->toBeNull()
        ->and($updatedItems[0]['panjang_meter'])->toBeNull()
        ->and((float) $updatedItems[0]['subtotal'])->toBe(96.0 * 17500.0);

    // Physical badge should show 1 roll 6m
    $badge = $component->instance()->getItemPhysicalBadge($updatedItems[0]);
    expect($badge)->toBe('± 1 roll (1 roll 6m)');
});

test('pembelian create and calculator for dus-based items like sekrup and angle clip computes physical pcs badge and does not inflate row quantity', function () {
    $kategoriAksesoris = Kategori::factory()->create(['nama_kategori' => 'Aksesoris']);
    $angleClipProduk = Produk::factory()->create([
        'kategori_id' => $kategoriAksesoris->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Angle Clip Siku Penggantung Dak Beton',
        'satuan' => SatuanProduk::Dus,
        'kemasan' => 'Dus',
        'isi_per_kemasan' => 100.00,
        'harga_beli' => 45000.00,
    ]);

    $component = Livewire::actingAs($this->owner)
        ->test(PembelianCreate::class)
        ->call('addItem', $angleClipProduk->id);

    $items = $component->get('items');
    expect($items)->toHaveCount(1)
        ->and((float) $items[0]['jumlah'])->toBe(1.0)
        ->and($items[0]['satuan'])->toContain('Dus')
        ->and((float) $items[0]['isi_per_kemasan'])->toBe(100.0);

    // Initial badge must show 100 pcs (@ 100 pcs/dus), NOT "± 0 Dus"
    $badge = $component->instance()->getItemPhysicalBadge($items[0]);
    expect($badge)->toBe('± 100 pcs (@ 100 pcs/dus)');

    // Open calculator
    $component->call('openCalculator', 0);
    expect($component->get('isCalculatorModalOpen'))->toBeTrue()
        ->and($component->get('calcMode'))->toBe('kemasan_dus')
        ->and($component->get('packLabel'))->toBe('Dus')
        ->and((float) $component->get('packSize'))->toBe(100.0);

    $component->assertSee('Rincian Konversi Eceran Biji ke Satuan Dus:')
        ->assertSee('Isi per Dus: <strong>100 Pcs (biji)</strong>', false);

    // Change to 5 Dus and apply
    $component->set('calcJmlKemasan', 5)
        ->call('applyCalculation');

    $updatedItems = $component->get('items');
    // Result quantity must be 5 Dus, NOT 500 Dus!
    expect((float) $updatedItems[0]['jumlah'])->toBe(5.0)
        ->and((float) $updatedItems[0]['subtotal'])->toBe(225000.0); // 5 * 45.000

    // Updated badge should show 500 pcs
    $updatedBadge = $component->instance()->getItemPhysicalBadge($updatedItems[0]);
    expect($updatedBadge)->toBe('± 500 pcs (@ 100 pcs/dus)');
});
