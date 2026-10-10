<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySummary extends Model
{
    const CREATED_AT = null;

    protected $table = 'daily_summaries';

    protected $fillable = [
        'date',
        'category_id',
        'status_id',
        'total_count',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
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

    public function scopeFilterDate($query, $startDate, $endDate)
    {
        return $query
            ->when($startDate, fn ($query) => $query->where('date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->where('date', '<=', $endDate));
    }
}
