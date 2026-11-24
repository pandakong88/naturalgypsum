<?php

use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Receivables\Enums\MetodePembayaran;
use App\Domains\Receivables\Models\Pembayaran;
use App\Livewire\Reports\PaymentReport;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create(['name' => 'Owner']);
    $this->owner->assignRole('Owner');

    $this->pelanggan = Pelanggan::factory()->create(['nama' => 'Toko Barokah']);

    Pembayaran::create([
        'no_pembayaran' => 'PAY-2026-001',
        'pelanggan_id' => $this->pelanggan->id,
        'tanggal' => now(),
        'jumlah_bayar' => 1000000,
        'metode' => MetodePembayaran::Tunai,
        'created_by' => $this->owner->id,
        'user_id' => $this->owner->id,
    ]);

    Pembayaran::create([
        'no_pembayaran' => 'PAY-2026-002',
        'pelanggan_id' => $this->pelanggan->id,
        'tanggal' => now(),
        'jumlah_bayar' => 2500000,
        'metode' => MetodePembayaran::Transfer,
        'catatan' => 'BCA-8899',
        'created_by' => $this->owner->id,
        'user_id' => $this->owner->id,
    ]);
});

test('TEST 1 — Payment report filters by payment method and shows correct breakdown totals', function () {
    $this->actingAs($this->owner);

    Livewire::test(PaymentReport::class)
        ->assertStatus(200)
        ->assertSee('PAY-2026-001')
        ->assertSee('PAY-2026-002')
        ->assertSee('Rp 3.500.000') // Total Masuk
        ->assertSee('Rp 1.000.000') // Tunai
        ->assertSee('Rp 2.500.000') // Transfer
        ->set('metode', 'transfer')
        ->assertSee('PAY-2026-002')
        ->assertDontSee('PAY-2026-001');
});
