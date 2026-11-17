<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-issues lead, customer and invoice codes in the current format (LD001, CM001, IN001).
 *
 * One continuous counter per series, following creation order.
 * Dry-run by default: nothing is written unless --apply is passed.
 * Take a database backup before running with --apply.
 */
class RenumberCodes extends Command
{
    protected $signature = 'codes:renumber {--apply : Write the new codes to the database (default is dry-run)}';

    protected $description = 'Renumber lead, customer and invoice codes to the LD/CM/IN + 3-digit format';

    /** @var array<string, array{table: string, column: string, prefix: string}> */
    private array $series = [
        'leads' => ['table' => 'leads', 'column' => 'lead_code', 'prefix' => 'LD'],
        'customers' => ['table' => 'customers', 'column' => 'customer_code', 'prefix' => 'CM'],
        'invoices' => ['table' => 'invoices', 'column' => 'invoice_number', 'prefix' => 'IN'],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'APPLY mode: database will be updated.' : 'DRY-RUN: no changes will be written.');

        $plan = [];

        foreach ($this->series as $name => $cfg) {
            $rows = DB::table($cfg['table'])
                ->select('id', $cfg['column'].' as code', 'created_at')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $changes = [];
            $counter = 0;

            foreach ($rows as $row) {
                $counter++;
                $new = $cfg['prefix'].str_pad((string) $counter, 3, '0', STR_PAD_LEFT);

                $changes[] = ['id' => $row->id, 'old' => $row->code, 'new' => $new];
            }

            $plan[$name] = $changes;
            $this->line('');
            $this->comment(ucfirst($name).': '.count($changes).' record(s)');
            $this->table(['id', 'old code', 'new code'], array_map(
                fn ($c) => [$c['id'], $c['old'], $c['new']],
                $changes
            ));
        }

        if (! $apply) {
            $this->warn('Dry-run finished. Re-run with --apply after taking a DB backup.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($plan) {
            foreach ($this->series as $name => $cfg) {
                // Phase 1: park codes on temporary values so the unique index never collides.
                foreach ($plan[$name] as $c) {
                    DB::table($cfg['table'])->where('id', $c['id'])
                        ->update([$cfg['column'] => '__TMP__'.$c['id']]);
                }
                // Phase 2: write the final codes.
                foreach ($plan[$name] as $c) {
                    DB::table($cfg['table'])->where('id', $c['id'])
                        ->update([$cfg['column'] => $c['new']]);
                }
            }
        });

        $this->info('Codes renumbered successfully.');

        return self::SUCCESS;
    }
}
