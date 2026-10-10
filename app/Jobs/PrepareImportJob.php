<?php

namespace App\Jobs;

use App\Models\Import;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PrepareImportJob implements ShouldQueue
{
    use Queueable;

    const CHUNK_SIZE = 5000;

    public $timeout = 600;

    public $tries = 1;

    public function __construct(public Import $import) {}

    public function handle(): void
    {
        $import = $this->import;

        $import->update([
            'status' => Import::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        $file = fopen(Storage::disk('local')->path($import->file_path), 'r');
        fgetcsv($file);

        $jobs = [];
        $totalRows = 0;
        $chunkRows = 0;
        $chunkOffset = ftell($file);
        $chunkStartRow = 2;

        while (fgetcsv($file) !== false) {
            $totalRows++;
            $chunkRows++;

            if ($chunkRows === self::CHUNK_SIZE) {
                $jobs[] = new ImportChunkJob($import->id, $import->file_path, $chunkOffset, $chunkRows, $chunkStartRow);

                $chunkOffset = ftell($file);
                $chunkStartRow += $chunkRows;
                $chunkRows = 0;
            }
        }

        if ($chunkRows > 0) {
            $jobs[] = new ImportChunkJob($import->id, $import->file_path, $chunkOffset, $chunkRows, $chunkStartRow);
        }

        fclose($file);

        $import->update(['total_rows' => $totalRows]);

        if (empty($jobs)) {
            $import->update([
                'status' => Import::STATUS_COMPLETED,
                'finished_at' => now(),
            ]);

            return;
        }

        $importId = $import->id;

        $batch = Bus::batch($jobs)
            ->name("Import #{$importId}")
            ->allowFailures()
            ->finally(function (Batch $batch) use ($importId) {
                $failed = $batch->failedJobs > 0;

                Import::whereKey($importId)->update([
                    'status' => $failed ? Import::STATUS_FAILED : Import::STATUS_COMPLETED,
                    'error_message' => $failed ? "{$batch->failedJobs} dari {$batch->totalJobs} chunk gagal diproses, cek tabel failed_jobs." : null,
                    'finished_at' => now(),
                ]);

                RebuildDailySummaryJob::dispatch();
            })
            ->dispatch();

        $import->update(['batch_id' => $batch->id]);
    }

    public function failed(Throwable $exception): void
    {
        $this->import->update([
            'status' => Import::STATUS_FAILED,
            'error_message' => 'Gagal membaca file: '.$exception->getMessage(),
            'finished_at' => now(),
        ]);
    }
}
