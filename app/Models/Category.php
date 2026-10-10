<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasUuids;

    protected $table = 'categories';

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
        return $this->hasMany(Transaction::class, 'category_id', 'id');
    }
}
