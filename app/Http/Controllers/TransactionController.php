<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DailySummary;
use App\Models\Import;
use App\Models\Status;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

class TransactionController extends Controller
{
    const EXCEL_ROWS_PER_SHEET = 1000000;

    public function TransactionIndex(Request $request)
    {
        $validation = $request->validate($this->filterRules());

        $getDataTransaction = Transaction::with(['category', 'status'])
            ->select(['id', 'reference_no', 'transaction_date', 'category_id', 'status_id', 'payment_method', 'amount', 'description'])
            ->filter($validation)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->cursorPaginate(25)
            ->withQueryString();

        $totalData = null;

        if (! $request->anyFilled(['cari', 'min_amount', 'max_amount'])) {

            $totalData = DailySummary::filterDate($validation['start_date'] ?? null, $validation['end_date'] ?? null)
                ->when($validation['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
                ->when($validation['status_id'] ?? null, fn ($query, $statusId) => $query->where('status_id', $statusId))
                ->sum('total_count');
        }

        return view('Transaction.TransactionIndex', [
            'judul' => 'Data Transaksi',
            'dataTransaction' => $getDataTransaction,
            'totalData' => $totalData,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'statuses' => Status::orderBy('id')->get(['id', 'code', 'name']),
        ]);
    }

    public function TransactionExportCsv(Request $request)
    {
        $validation = $request->validate($this->filterRules());

        $categories = Category::pluck('code', 'id')->all();
        $statuses = Status::pluck('code', 'id')->all();

        $dataTransaction = $this->exportQuery($validation);

        $fileName = 'transactions_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($dataTransaction, $categories, $statuses) {
            set_time_limit(0);

            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, Import::CSV_HEADER);

            $count = 0;

            foreach ($dataTransaction->lazyById(5000) as $row) {
                fputcsv($handle, [
                    $row->reference_no,
                    $row->transaction_date,
                    $categories[$row->category_id] ?? '',
                    $statuses[$row->status_id] ?? '',
                    $row->payment_method,
                    $row->amount,
                    $row->description,
                ]);

                if (++$count % 5000 === 0) {
                    flush();
                }
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function TransactionExportExcel(Request $request)
    {
        $validation = $request->validate($this->filterRules());

        $categories = Category::pluck('name', 'id')->all();
        $statuses = Status::pluck('name', 'id')->all();

        $dataTransaction = $this->exportQuery($validation);

        $fileName = 'transactions_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($dataTransaction, $categories, $statuses) {
            set_time_limit(0);

            $header = ['No. Referensi', 'Tanggal Transaksi', 'Kategori', 'Status', 'Metode Pembayaran', 'Amount', 'Deskripsi'];

            $tempFolder = storage_path('app/private/exports/tmp');
            File::ensureDirectoryExists($tempFolder);

            $writer = new Writer(new Options(tempFolder: $tempFolder));
            $writer->openToFile('php://output');
            $writer->getCurrentSheet()->setName('Transaksi 1');
            $writer->addRow(Row::fromValues($header));

            $rowsInSheet = 0;
            $sheetNumber = 1;

            foreach ($dataTransaction->lazyById(5000) as $row) {
                if ($rowsInSheet === self::EXCEL_ROWS_PER_SHEET) {
                    $sheetNumber++;

                    $writer->addNewSheetAndMakeItCurrent()->setName("Transaksi {$sheetNumber}");
                    $writer->addRow(Row::fromValues($header));

                    $rowsInSheet = 0;
                }

                $writer->addRow(Row::fromValues([
                    $row->reference_no,
                    $row->transaction_date,
                    $categories[$row->category_id] ?? '',
                    $statuses[$row->status_id] ?? '',
                    $row->payment_method,
                    (float) $row->amount,
                    $row->description,
                ]));

                $rowsInSheet++;
            }

            $writer->close();
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function exportQuery(array $filters)
    {
        return Transaction::filter($filters)
            ->toBase()
            ->select(['id', 'reference_no', 'transaction_date', 'category_id', 'status_id', 'payment_method', 'amount', 'description']);
    }

    private function filterRules()
    {
        return [
            'cari' => 'nullable|string|max:30',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'category_id' => 'nullable|integer|exists:categories,id',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
        ];
    }
}
