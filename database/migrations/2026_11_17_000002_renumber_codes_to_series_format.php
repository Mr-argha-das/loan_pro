<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Re-issues lead, customer and invoice codes in the current series format:
 *
 *   Lead      LD001, LD002, ...
 *   Customer  CM001, CM002, ...
 *   Invoice   IN001, IN002, ...
 *
 * One continuous counter per series, following creation order. The codes are
 * parked on temporary values first so the unique index never collides.
 */
return new class extends Migration
{
    /** @var array<int, array{table: string, column: string, prefix: string}> */
    private array $series = [
        ['table' => 'leads', 'column' => 'lead_code', 'prefix' => 'LD'],
        ['table' => 'customers', 'column' => 'customer_code', 'prefix' => 'CM'],
        ['table' => 'invoices', 'column' => 'invoice_number', 'prefix' => 'IN'],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            foreach ($this->series as $config) {
                $rows = DB::table($config['table'])
                    ->select('id')
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->get();

                // Phase 1: park the codes so the unique index never collides.
                foreach ($rows as $row) {
                    DB::table($config['table'])
                        ->where('id', $row->id)
                        ->update([$config['column'] => '__TMP__'.$row->id]);
                }

                // Phase 2: write the final codes.
                $counter = 0;

                foreach ($rows as $row) {
                    $counter++;
                    DB::table($config['table'])
                        ->where('id', $row->id)
                        ->update([
                            $config['column'] => $config['prefix'].str_pad((string) $counter, 3, '0', STR_PAD_LEFT),
                        ]);
                }
            }
        });
    }

    /**
     * Irreversible: the previous codes are not stored anywhere, so they cannot
     * be restored. Restore from a database backup instead.
     */
    public function down(): void
    {
        //
    }
};
