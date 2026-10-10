<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Status;
use DateTime;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportChunkJob implements ShouldQueue
{
    use Batchable, Queueable;

    public $timeout = 300;

    public $tries = 3;

    public function __construct(
        public int $importId,
        public string $filePath,
        public int $offset,
        public int $rowCount,
        public int $startRow,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $categories = Category::pluck('id', 'code')->all();
        $statuses = Status::pluck('id', 'code')->all();

        $file = fopen(Storage::disk('local')->path($this->filePath), 'r');
        fseek($file, $this->offset);

        $now = now()->toDateTimeString();
        $transactions = [];
        $errors = [];

        for ($i = 0; $i < $this->rowCount; $i++) {
            $row = fgetcsv($file);

            if ($row === false) {
                break;
            }

            $error = $this->validateRow($row, $categories, $statuses);

            if ($error) {
                $errors[] = [
                    'import_id' => $this->importId,
                    'row_number' => $this->startRow + $i,
                    'reason' => $error,
                    'raw_data' => implode(',', $row),
                    'created_at' => $now,
                ];

                continue;
            }

            [$referenceNo, $transactionDate, $category, $status, $paymentMethod, $amount, $description] = $row;

            $transactions[] = [
                'reference_no' => $referenceNo,
                'transaction_date' => $transactionDate,
                'category_id' => $categories[$category],
                'status_id' => $statuses[$status],
                'payment_method' => $paymentMethod,
                'amount' => $amount,
                'description' => $description !== '' ? $description : null,
                'import_id' => $this->importId,
                'created_at' => $now,
            ];
        }

        fclose($file);

        DB::transaction(function () use ($transactions, $errors, $now) {
            if ($transactions) {
                DB::table('transactions')->insertOrIgnore($transactions);
            }

            if ($errors) {
                DB::table('import_errors')->insert($errors);
            }

            DB::table('imports')->where('id', $this->importId)->incrementEach([
                'processed_rows' => count($transactions) + count($errors),
                'failed_rows' => count($errors),
            ], ['updated_at' => $now]);
        });
    }

    private function validateRow(array $row, array $categories, array $statuses)
    {
        if (count($row) !== 7) {
            return 'Jumlah kolom tidak sesuai';
        }

        [$referenceNo, $transactionDate, $category, $status, $paymentMethod, $amount, $description] = $row;

        if ($referenceNo === '' || strlen($referenceNo) > 30) {
            return 'reference_no kosong atau lebih dari 30 karakter';
        }

        $date = DateTime::createFromFormat('Y-m-d H:i:s', $transactionDate);

        if (! $date || $date->format('Y-m-d H:i:s') !== $transactionDate) {
            return "transaction_date '{$transactionDate}' tidak valid";
        }

        if (! isset($categories[$category])) {
            return "Category '{$category}' tidak terdaftar";
        }

        if (! isset($statuses[$status])) {
            return "Status '{$status}' tidak terdaftar";
        }

        if ($paymentMethod === '' || strlen($paymentMethod) > 20) {
            return 'payment_method kosong atau lebih dari 20 karakter';
        }

        if (! is_numeric($amount) || $amount < 0 || $amount > 9999999999999.99) {
            return "Amount '{$amount}' bukan angka yang valid";
        }

        if (mb_strlen($description) > 255) {
            return 'description lebih dari 255 karakter';
        }

        return null;
    }
}
