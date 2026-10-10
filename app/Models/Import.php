<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    const STATUS_PENDING = 'pending';

    const STATUS_PROCESSING = 'processing';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const CSV_HEADER = ['reference_no', 'transaction_date', 'category', 'status', 'payment_method', 'amount', 'description'];

    protected $table = 'imports';

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'failed_rows',
        'batch_id',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function progressData()
    {
        $elapsed = $this->started_at ? (int) abs($this->started_at->diffInSeconds($this->finished_at ?? now())) : 0;
        $speed = $elapsed > 0 ? (int) round($this->processed_rows / $elapsed) : 0;
        $remaining = max($this->total_rows - $this->processed_rows, 0);

        return [
            'status' => $this->status,
            'total_rows' => $this->total_rows,
            'processed_rows' => $this->processed_rows,
            'failed_rows' => $this->failed_rows,
            'percent' => $this->total_rows > 0 ? round($this->processed_rows / $this->total_rows * 100, 1) : 0,
            'rows_per_second' => $speed,
            'elapsed_seconds' => $elapsed,
            'eta_seconds' => $speed > 0 && $this->status === self::STATUS_PROCESSING ? (int) ceil($remaining / $speed) : null,
            'error_message' => $this->error_message,
        ];
    }

    public static function hasValidHeader($path)
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle) ?: [];
        fclose($handle);

        $header = array_map(fn ($column) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $column)), $header);

        return $header === self::CSV_HEADER;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function importErrors()
    {
        return $this->hasMany(ImportError::class, 'import_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'import_id', 'id');
    }
}
