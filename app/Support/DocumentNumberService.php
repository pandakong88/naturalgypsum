<?php

namespace App\Support;

use App\Domains\Inventory\Models\DocumentSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /**
     * Generate a concurrency-safe sequential document number.
     *
     * Example outputs:
     * - PB-2026-000001
     * - PJ-2026-000001
     * - TR-2026-000001
     *
     * @param  string  $type  Document type prefix (e.g., 'PB', 'PJ', 'TR', 'SO')
     * @param  int|null  $year  Transaction year (defaults to current year)
     * @param  int  $digits  Zero-padding width (defaults to 6)
     */
    public function generate(string $type, ?int $year = null, int $digits = 6): string
    {
        $type = strtoupper(trim($type));
        $year = $year ?? (int) Carbon::now()->format('Y');

        return DB::transaction(function () use ($type, $year, $digits) {
            /** @var DocumentSequence $sequence */
            $sequence = DocumentSequence::where('type', $type)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = DocumentSequence::create([
                    'type' => $type,
                    'year' => $year,
                    'last_number' => 0,
                ]);

                // Re-lock the newly created row
                $sequence = DocumentSequence::where('id', $sequence->id)
                    ->lockForUpdate()
                    ->first();
            }

            $nextNumber = $sequence->last_number + 1;
            $sequence->update(['last_number' => $nextNumber]);

            return sprintf('%s-%04d-%0'.$digits.'d', $type, $year, $nextNumber);
        });
    }
}
