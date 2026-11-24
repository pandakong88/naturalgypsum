<?php

namespace App\Domains\Audit\Models;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'old_values',
        'new_values',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeFilterByUser(Builder $query, ?int $userId): Builder
    {
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    public function scopeFilterByAction(Builder $query, ?string $action): Builder
    {
        if ($action) {
            $query->where('action', $action);
        }

        return $query;
    }

    public function scopeFilterByDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }

    public function scopeFilterByModule(Builder $query, ?string $module): Builder
    {
        if (! filled($module)) {
            return $query;
        }

        return match ($module) {
            'master' => $query->whereIn('subject_type', [
                Kategori::class,
                Merek::class,
                Gudang::class,
                Supplier::class,
                Produk::class,
                Pelanggan::class,
            ]),
            'sales' => $query->where(function ($q) {
                $q->where('subject_type', 'like', '%Sales%')
                    ->orWhere('subject_type', 'like', '%Penjualan%');
            }),
            'purchasing' => $query->where(function ($q) {
                $q->where('subject_type', 'like', '%Purchasing%')
                    ->orWhere('subject_type', 'like', '%Pembelian%')
                    ->orWhere('subject_type', 'like', '%ReturSupplier%');
            }),
            'inventory' => $query->where(function ($q) {
                $q->where('subject_type', 'like', '%Inventory%')
                    ->orWhere('subject_type', 'like', '%Stock%')
                    ->orWhere('subject_type', 'like', '%Transfer%');
            }),
            'security' => $query->where(function ($q) {
                $q->where('subject_type', User::class)
                    ->orWhereIn('action', ['login', 'logout']);
            }),
            default => $query->where('subject_type', 'like', "%{$module}%"),
        };
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (filled($term)) {
            $term = trim($term);
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', "%{$term}%")
                    ->orWhere('user_name', 'like', "%{$term}%")
                    ->orWhere('subject_label', 'like', "%{$term}%")
                    ->orWhere('action', 'like', "%{$term}%");
            });
        }

        return $query;
    }
}
