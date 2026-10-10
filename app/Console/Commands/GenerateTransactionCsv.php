<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateTransactionCsv extends Command
{
    protected $signature = 'transactions:generate
                            {rows=5000000 : Jumlah baris transaksi}
                            {--invalid=0 : Jumlah baris rusak untuk menguji validasi import}
                            {--path= : Lokasi file output}';

    protected $description = 'Membuat file CSV transaksi dummy dalam jumlah besar';

    private const PAYMENT_METHODS = ['transfer', 'e-wallet', 'credit_card', 'debit_card', 'cash'];

    public function handle()
    {
        $rows = (int) $this->argument('rows');
        $invalid = (int) $this->option('invalid');

        $categories = Category::pluck('code')->all();

        if (empty($categories)) {
            $this->error('Data category masih kosong, jalankan php artisan db:seed terlebih dahulu.');

            return self::FAILURE;
        }

        $path = $this->option('path') ?: storage_path("app/samples/transactions_{$rows}.csv");
        File::ensureDirectoryExists(dirname($path));

        $prefix = 'TRX'.date('ymdHis');
        $startTime = strtotime('-2 years');
        $endTime = time();
        $lastCategory = count($categories) - 1;
        $invalidEvery = $invalid > 0 ? max(1, intdiv($rows, $invalid)) : 0;
        $invalidCount = 0;

        $startedAt = microtime(true);
        $progress = $this->output->createProgressBar($rows);

        $file = fopen($path, 'w');
        fputcsv($file, ['reference_no', 'transaction_date', 'category', 'status', 'payment_method', 'amount', 'description']);

        for ($i = 1; $i <= $rows; $i++) {
            $category = $categories[mt_rand(0, $lastCategory)];
            $chance = mt_rand(1, 100);
            $status = $chance <= 85 ? 'success' : ($chance <= 95 ? 'pending' : 'failed');

            $row = [
                sprintf('%s-%08d', $prefix, $i),
                date('Y-m-d H:i:s', mt_rand($startTime, $endTime)),
                $category,
                $status,
                self::PAYMENT_METHODS[mt_rand(0, 4)],
                mt_rand(100, 50000) * 100,
                "Pembayaran {$category}",
            ];

            if ($invalidEvery > 0 && $i % $invalidEvery === 0 && $invalidCount < $invalid) {
                $row = $this->breakRow($row, $invalidCount);
                $invalidCount++;
            }

            fputcsv($file, $row);

            if ($i % 10000 === 0) {
                $progress->advance(10000);
            }
        }

        fclose($file);
        $progress->finish();
        $this->newLine(2);

        $this->table(['Info', 'Nilai'], [
            ['File', $path],
            ['Jumlah baris', number_format($rows, 0, ',', '.')],
            ['Baris rusak', number_format($invalidCount, 0, ',', '.')],
            ['Ukuran file', round(filesize($path) / 1024 / 1024, 2).' MB'],
            ['Waktu', round(microtime(true) - $startedAt, 2).' detik'],
            ['Peak memory', round(memory_get_peak_usage(true) / 1024 / 1024, 2).' MB'],
        ]);

        return self::SUCCESS;
    }

    private function breakRow(array $row, int $index)
    {
        switch ($index % 4) {
            case 0:
                $row[5] = 'abc';
                break;
            case 1:
                $row[1] = '2026-13-45 99:99:99';
                break;
            case 2:
                $row[2] = 'unknown';
                break;
            case 3:
                $row[3] = '';
                break;
        }

        return $row;
    }
}
