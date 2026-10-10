<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasUuids;

    const SYSTEM_CODES = ['success', 'pending', 'failed'];

    protected $table = 'statuses';

    protected $fillable = [
        'code',
        'name',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function transactions()
    {

        return $this->hasMany(Transaction::class, 'status_id', 'id');
    }
}
