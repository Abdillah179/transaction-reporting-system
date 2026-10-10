<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    const UPDATED_AT = null;

    protected $table = 'transactions';

    protected $fillable = [
        'reference_no',
        'transaction_date',
        'category_id',
        'status_id',
        'payment_method',
        'amount',
        'description',
        'import_id',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }

    public function import()
    {
        return $this->belongsTo(Import::class, 'import_id', 'id');
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when(isset($filters['cari']), fn ($query) => $query->where('reference_no', 'like', $filters['cari'].'%'))
            ->when(isset($filters['start_date']), fn ($query) => $query->where('transaction_date', '>=', $filters['start_date'].' 00:00:00'))
            ->when(isset($filters['end_date']), fn ($query) => $query->where('transaction_date', '<=', $filters['end_date'].' 23:59:59'))
            ->when(isset($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(isset($filters['status_id']), fn ($query) => $query->where('status_id', $filters['status_id']))
            ->when(isset($filters['min_amount']), fn ($query) => $query->where('amount', '>=', $filters['min_amount']))
            ->when(isset($filters['max_amount']), fn ($query) => $query->where('amount', '<=', $filters['max_amount']));
    }
}
