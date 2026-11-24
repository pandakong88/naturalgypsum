<?php

use App\Domains\Audit\Models\ActivityLog;
use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Actions\CreatePelangganAction;
use App\Domains\Catalog\Actions\CreateProdukAction;
use App\Domains\Catalog\Actions\DeletePelangganAction;
use App\Domains\Catalog\Actions\DeleteProdukAction;
use App\Domains\Catalog\Actions\RestorePelangganAction;
use App\Domains\Catalog\Actions\RestoreProdukAction;
use App\Domains\Catalog\Actions\UpdatePelangganAction;
use App\Domains\Catalog\Actions\UpdateProdukAction;
use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Inventory\Actions\CancelStockOpnameAction;
use App\Domains\Inventory\Actions\CompleteStockOpnameAction;
use App\Domains\Inventory\Actions\CreateStockOpnameAction;
use App\Domains\Inventory\Enums\StatusStockOpname;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Purchasing\Actions\CreatePembelianAction;
use App\Domains\Purchasing\Models\Pembelian;
use App\Domains\Sales\Actions\CreatePenjualanAction;
use App\Domains\Sales\Models\Penjualan;
use App\Livewire\Admin\ActivityLogIndex;
use App\Livewire\Auth\Login;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create(['name' => 'Owner System', 'email' => 'owner@example.com']);
    $this->owner->assignRole('Owner');

    $this->staffGudang = User::factory()->create(['name' => 'Staff Gudang', 'email' => 'gudang@example.com']);
    $this->staffGudang->assignRole('Staff Gudang');

    $this->unauthorizedUser = User::factory()->create(['name' => 'Plain User', 'email' => 'plain@example.com']);

    $this->logger = app(ActivityLogService::class);
});

test('TEST 1 — ActivityLogService creates detailed activity record with actor and subject', function () {
    $this->actingAs($this->owner);

    $kategori = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);

    $log = $this->logger->log(
        action: 'create',
        description: 'Menambahkan kategori baru Plafon PVC',
        subject: $kategori,
        newValues: ['nama_kategori' => 'Plafon PVC']
    );

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($this->owner->id)
        ->and($log->user_name)->toBe('Owner System')
        ->and($log->action)->toBe('create')
        ->and($log->subject_type)->toBe(Kategori::class)
        ->and($log->subject_id)->toBe($kategori->id)
        ->and($log->subject_label)->toBe('Plafon PVC')
        ->and($log->new_values['nama_kategori'])->toBe('Plafon PVC');
});

test('TEST 2 — Sensitive authentication fields are sanitized and never stored in activity logs', function () {
    $this->actingAs($this->owner);

    $dirtyPayload = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'remember_token' => 'random_token_xyz',
        'api_token' => 'super_secret_api_token',
        'nested' => [
            'role' => 'Manager',
            'secret' => 'top_secret_code',
        ],
    ];

    $sanitized = $this->logger->sanitize($dirtyPayload);

    expect(isset($sanitized['password']))->toBeFalse()
        ->and(isset($sanitized['password_confirmation']))->toBeFalse()
        ->and(isset($sanitized['remember_token']))->toBeFalse()
        ->and(isset($sanitized['api_token']))->toBeFalse()
        ->and(isset($sanitized['nested']['secret']))->toBeFalse()
        ->and($sanitized['name'])->toBe('John Doe')
        ->and($sanitized['nested']['role'])->toBe('Manager');
});

test('TEST 3 — Stock Opname lifecycle generates complete audit trail records', function () {
    $this->actingAs($this->owner);

    $gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Pusat']);
    $produk = Produk::factory()->create(['nama' => 'Papan PVC 4m']);

    $createAction = app(CreateStockOpnameAction::class);
    $completeAction = app(CompleteStockOpnameAction::class);
    $cancelAction = app(CancelStockOpnameAction::class);

    // 1. Create Draft
    $opname = $createAction->execute([
        'gudang_id' => $gudang->id,
        'tanggal' => now()->toDateString(),
        'status' => StatusStockOpname::Draft,
        'items' => [
            ['produk_id' => $produk->id, 'stok_fisik' => 10],
        ],
    ]);

    $createLog = ActivityLog::where('action', 'create_draft')
        ->where('subject_type', $opname::class)
        ->where('subject_id', $opname->id)
        ->first();
    expect($createLog)->not->toBeNull()
        ->and($createLog->user_id)->toBe($this->owner->id);

    // 2. Complete Draft
    $completeAction->execute($opname);

    $completeLog = ActivityLog::where('action', 'complete')
        ->where('subject_type', $opname::class)
        ->where('subject_id', $opname->id)
        ->first();
    expect($completeLog)->not->toBeNull()
        ->and($completeLog->user_id)->toBe($this->owner->id);

    // 3. Cancel Opname
    $cancelAction->execute($opname, 'Salah hitung fisik');

    $cancelLog = ActivityLog::where('action', 'cancel')
        ->where('subject_type', $opname::class)
        ->where('subject_id', $opname->id)
        ->first();
    expect($cancelLog)->not->toBeNull()
        ->and($cancelLog->new_values['alasan_batal'])->toBe('Salah hitung fisik');
});

test('TEST 4 — Login and logout events generate security activity logs', function () {
    $user = User::factory()->create([
        'email' => 'auth.test@example.com',
        'password' => Hash::make('password123'),
    ]);
    $user->assignRole('Owner');

    // 1. Login
    Livewire::test(Login::class)
        ->set('email', 'auth.test@example.com')
        ->set('password', 'password123')
        ->call('login');

    $loginLog = ActivityLog::where('action', 'login')
        ->where('user_id', $user->id)
        ->first();
    expect($loginLog)->not->toBeNull()
        ->and($loginLog->description)->toContain('User berhasil masuk');

    // 2. Logout
    $this->actingAs($user);
    $this->post(route('logout'));

    $logoutLog = ActivityLog::where('action', 'logout')
        ->where('user_id', $user->id)
        ->first();
    expect($logoutLog)->not->toBeNull()
        ->and($logoutLog->description)->toContain('logout');
});

test('TEST 5 — ActivityLog policy enforces strict immutability (no create, update, or delete via policy)', function () {
    $this->actingAs($this->owner);

    $log = ActivityLog::create([
        'user_id' => $this->owner->id,
        'user_name' => 'Owner System',
        'action' => 'test',
        'description' => 'Test log',
        'created_at' => now(),
    ]);

    expect(auth()->user()->can('create', ActivityLog::class))->toBeFalse()
        ->and(auth()->user()->can('update', $log))->toBeFalse()
        ->and(auth()->user()->can('delete', $log))->toBeFalse();
});

test('TEST 6 — Livewire ActivityLogIndex renders, searches, and filters by user and action', function () {
    $this->actingAs($this->owner);

    $log1 = $this->logger->log('create', 'Aktivitas satu', userId: $this->owner->id);
    $log2 = $this->logger->log('cancel', 'Aktivitas dua pembatalan', userId: $this->staffGudang->id);

    Livewire::test(ActivityLogIndex::class)
        ->assertStatus(200)
        ->assertSee('Aktivitas satu')
        ->assertSee('Aktivitas dua pembatalan')
        ->set('filterAction', 'cancel')
        ->assertSee('Aktivitas dua pembatalan')
        ->assertDontSee('Aktivitas satu')
        ->set('filterAction', '')
        ->set('search', 'Aktivitas satu')
        ->assertSee('Aktivitas satu')
        ->assertDontSee('Aktivitas dua pembatalan')
        ->call('showDetail', $log1->id)
        ->assertSee('Rincian Log Aktivitas #'.$log1->id);
});

test('TEST 7 — Unauthorized users cannot access activity logs', function () {
    $this->actingAs($this->unauthorizedUser);

    Livewire::test(ActivityLogIndex::class)
        ->assertForbidden();
});

test('TEST 8 — Livewire ActivityLogIndex filters by module category', function () {
    $this->actingAs($this->owner);

    $kategori = Kategori::factory()->create();
    $this->logger->log('create', 'Master Kategori log', subject: $kategori);
    $this->logger->log('login', 'User login security log');

    Livewire::test(ActivityLogIndex::class)
        ->set('filterModule', 'master')
        ->assertSee('Master Kategori log')
        ->assertDontSee('User login security log')
        ->set('filterModule', 'security')
        ->assertSee('User login security log')
        ->assertDontSee('Master Kategori log');
});

test('TEST 9 — Livewire exportCsv generates streamed CSV file with correct headers and data', function () {
    $this->actingAs($this->owner);

    $this->logger->log('create', 'Catatan log ekspor 1', userId: $this->owner->id);

    $response = Livewire::test(ActivityLogIndex::class)
        ->call('exportCsv');

    $streamed = $response->instance()->exportCsv();
    expect($streamed->headers->get('content-type'))->toBe('text/csv; charset=UTF-8');

    ob_start();
    $streamed->sendContent();
    $csvContent = ob_get_clean();

    expect($csvContent)->toContain('ID,Waktu')
        ->and($csvContent)->toContain('Pengguna (Actor)')
        ->and($csvContent)->toContain('Catatan log ekspor 1');
});

test('TEST 10 — Master Produk lifecycle generates complete audit trail with price changes', function () {
    $this->actingAs($this->owner);
    $kategori = Kategori::factory()->create();

    $createAction = app(CreateProdukAction::class);
    $updateAction = app(UpdateProdukAction::class);
    $deleteAction = app(DeleteProdukAction::class);
    $restoreAction = app(RestoreProdukAction::class);

    // 1. Create
    $produk = $createAction->execute([
        'kategori_id' => $kategori->id,
        'kode_katalog' => 'PRD-AUDIT-01',
        'nama' => 'Papan PVC Uji Audit',
        'satuan' => SatuanProduk::Lembar,
        'harga_beli' => 25000,
        'harga_jual' => 35000,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'create',
        'subject_type' => Produk::class,
        'subject_id' => $produk->id,
        'user_id' => $this->owner->id,
    ]);

    // 2. Update price
    $updateAction->execute($produk, [
        'kategori_id' => $kategori->id,
        'kode_katalog' => 'PRD-AUDIT-01',
        'nama' => 'Papan PVC Uji Audit',
        'satuan' => SatuanProduk::Lembar,
        'harga_beli' => 26000,
        'harga_jual' => 38000, // Price updated
    ]);

    $updateLog = ActivityLog::where('action', 'update')
        ->where('subject_type', Produk::class)
        ->where('subject_id', $produk->id)
        ->latest('id')
        ->first();

    expect($updateLog)->not->toBeNull()
        ->and((float) $updateLog->old_values['harga_jual'])->toBe(35000.0)
        ->and((float) $updateLog->new_values['harga_jual'])->toBe(38000.0);

    // 3. Delete
    $deleteAction->execute($produk);
    $this->assertDatabaseHas('activity_logs', [
        'action' => 'delete',
        'subject_type' => Produk::class,
        'subject_id' => $produk->id,
    ]);

    // 4. Restore
    $restoreAction->execute($produk);
    $this->assertDatabaseHas('activity_logs', [
        'action' => 'restore',
        'subject_type' => Produk::class,
        'subject_id' => $produk->id,
    ]);
});

test('TEST 11 — Master Pelanggan lifecycle generates complete audit trail with limit piutang', function () {
    $this->actingAs($this->owner);

    $createAction = app(CreatePelangganAction::class);
    $updateAction = app(UpdatePelangganAction::class);
    $deleteAction = app(DeletePelangganAction::class);
    $restoreAction = app(RestorePelangganAction::class);

    // 1. Create
    $pelanggan = $createAction->execute([
        'nama' => 'Pelanggan Kontraktor Audit',
        'kontak' => '081234567899',
        'tipe' => TipePelanggan::Pemborong,
        'limit_piutang' => 10000000,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'create',
        'subject_type' => Pelanggan::class,
        'subject_id' => $pelanggan->id,
    ]);

    // 2. Update limit piutang
    $updateAction->execute($pelanggan, [
        'nama' => 'Pelanggan Kontraktor Audit',
        'tipe' => TipePelanggan::Pemborong,
        'limit_piutang' => 25000000,
    ]);

    $updateLog = ActivityLog::where('action', 'update')
        ->where('subject_type', Pelanggan::class)
        ->where('subject_id', $pelanggan->id)
        ->latest('id')
        ->first();

    expect($updateLog)->not->toBeNull()
        ->and((float) $updateLog->old_values['limit_piutang'])->toBe(10000000.0)
        ->and((float) $updateLog->new_values['limit_piutang'])->toBe(25000000.0);

    // 3. Delete & Restore
    $deleteAction->execute($pelanggan);
    $this->assertDatabaseHas('activity_logs', [
        'action' => 'delete',
        'subject_type' => Pelanggan::class,
        'subject_id' => $pelanggan->id,
    ]);

    $restoreAction->execute($pelanggan);
    $this->assertDatabaseHas('activity_logs', [
        'action' => 'restore',
        'subject_type' => Pelanggan::class,
        'subject_id' => $pelanggan->id,
    ]);
});

test('TEST 12 — Transaksi Pembelian creation records detailed activity log', function () {
    $this->actingAs($this->owner);
    $supplier = Supplier::factory()->create();
    $kategori = Kategori::factory()->create();
    $gudang = Gudang::factory()->create();
    $produk = Produk::factory()->create([
        'kategori_id' => $kategori->id,
        'harga_beli' => 50000,
    ]);

    $pembelian = app(CreatePembelianAction::class)->execute([
        'supplier_id' => $supplier->id,
        'tanggal' => now()->toDateString(),
        'items' => [
            [
                'produk_id' => $produk->id,
                'gudang_id' => $gudang->id,
                'jumlah' => 10,
                'harga_beli_satuan' => 50000,
            ],
        ],
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'create',
        'subject_type' => Pembelian::class,
        'subject_id' => $pembelian->id,
        'user_id' => $this->owner->id,
    ]);
});

test('TEST 13 — Transaksi Penjualan creation records detailed activity log', function () {
    $this->actingAs($this->owner);
    $pelanggan = Pelanggan::factory()->create();
    $kategori = Kategori::factory()->create();
    $gudang = Gudang::factory()->create();
    $produk = Produk::factory()->create([
        'kategori_id' => $kategori->id,
        'harga_jual' => 75000,
    ]);

    // Give product stock
    Stok::factory()->create([
        'produk_id' => $produk->id,
        'gudang_id' => $gudang->id,
        'jumlah' => 100,
    ]);

    $penjualan = app(CreatePenjualanAction::class)->execute([
        'pelanggan_id' => $pelanggan->id,
        'gudang_id' => $gudang->id,
        'tanggal' => now()->toDateString(),
        'items' => [
            [
                'produk_id' => $produk->id,
                'gudang_id' => $gudang->id,
                'harga_satuan' => 75000,
                'jumlah' => 5,
            ],
        ],
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'create',
        'subject_type' => Penjualan::class,
        'subject_id' => $penjualan->id,
        'user_id' => $this->owner->id,
    ]);
});
