<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class RebuildDailySummaryJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 600;

    public $tries = 1;

    public function handle(): void
    {
        DB::transaction(function () {
            DB::table('daily_summaries')->delete();

            DB::statement('
                INSERT INTO daily_summaries (date, category_id, status_id, total_count, total_amount, updated_at)
                SELECT DATE(transaction_date), category_id, status_id, COUNT(*), SUM(amount), NOW()
                FROM transactions
                GROUP BY DATE(transaction_date), category_id, status_id
            ');
        });
    }
}
