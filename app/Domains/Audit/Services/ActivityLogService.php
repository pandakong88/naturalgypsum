<?php

namespace App\Domains\Audit\Services;

use App\Domains\Audit\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * List of sensitive fields that should never be logged in before/after or properties.
     */
    protected array $hiddenFields = [
        'password',
        'password_confirmation',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
        'secret',
    ];

    /**
     * Log an operational, transactional, or administrative activity.
     */
    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $properties = null,
        ?int $userId = null
    ): ActivityLog {
        $user = $userId ? User::find($userId) : Auth::user();
        $resolvedUserId = $user?->id;
        $resolvedUserName = $user?->name ?? ($userId ? "User #{$userId}" : 'System');

        $subjectType = $subject ? get_class($subject) : null;
        $subjectId = $subject?->getKey();
        $subjectLabel = $this->resolveSubjectLabel($subject);

        $sanitizedOld = $oldValues ? $this->sanitize($oldValues) : null;
        $sanitizedNew = $newValues ? $this->sanitize($newValues) : null;
        $sanitizedProps = $properties ? $this->sanitize($properties) : [];

        // Attach request context if available
        if (Request::hasSession()) {
            $sanitizedProps['ip'] = Request::ip();
            $sanitizedProps['url'] = Request::fullUrl();
        }

        return ActivityLog::create([
            'user_id' => $resolvedUserId,
            'user_name' => $resolvedUserName,
            'action' => strtolower(trim($action)),
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_label' => $subjectLabel,
            'description' => $description,
            'old_values' => $sanitizedOld,
            'new_values' => $sanitizedNew,
            'properties' => ! empty($sanitizedProps) ? $sanitizedProps : null,
            'ip_address' => Request::ip() ?? null,
            'user_agent' => Request::userAgent() ? substr(Request::userAgent(), 0, 500) : null,
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * Extract human-friendly label for a subject model.
     */
    protected function resolveSubjectLabel(?Model $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        return $subject->no_faktur
            ?? $subject->no_pembelian
            ?? $subject->no_penjualan
            ?? $subject->no_pembayaran
            ?? $subject->no_transfer
            ?? $subject->no_produksi
            ?? $subject->no_retur
            ?? $subject->no_stock_opname
            ?? $subject->nama
            ?? $subject->nama_kategori
            ?? $subject->nama_merek
            ?? $subject->nama_gudang
            ?? $subject->nama_supplier
            ?? $subject->nama_pelanggan
            ?? $subject->name
            ?? (string) $subject->getKey();
    }

    /**
     * Recursively strip out sensitive fields from data payloads.
     */
    public function sanitize(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $this->hiddenFields, true)) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
