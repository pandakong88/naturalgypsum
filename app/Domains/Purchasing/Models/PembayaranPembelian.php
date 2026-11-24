<?php

namespace App\Domains\Purchasing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pembelian_id',
    'no_pembayaran',
    'tanggal_bayar',
    'nominal',
    'metode_pembayaran',
    'keterangan',
    'created_by',
])]
class PembayaranPembelian extends Model
{
    protected $table = 'pembayaran_pembelian';

    protected function casts(): array
    {
        return [
            'tanggal_bayar' => 'date',
            'nominal' => 'decimal:2',
        ];
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
