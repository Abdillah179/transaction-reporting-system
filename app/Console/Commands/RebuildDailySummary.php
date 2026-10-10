<?php

namespace App\Console\Commands;

use App\Jobs\RebuildDailySummaryJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildDailySummary extends Command
{
    protected $signature = 'summaries:rebuild';

    protected $description = 'Menghitung ulang tabel daily_summaries dari tabel transactions';

    public function handle()
    {
        $startedAt = microtime(true);

        RebuildDailySummaryJob::dispatchSync();

        $this->table(['Info', 'Nilai'], [
            ['Jumlah transaksi', number_format(DB::table('transactions')->count(), 0, ',', '.')],
            ['Baris ringkasan', number_format(DB::table('daily_summaries')->count(), 0, ',', '.')],
            ['Waktu', round(microtime(true) - $startedAt, 2).' detik'],
        ]);

        return self::SUCCESS;
    }
}
