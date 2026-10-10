<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportError extends Model
{
    const UPDATED_AT = null;

    protected $table = 'import_errors';

    protected $fillable = [
        'import_id',
        'row_number',
        'reason',
        'raw_data',
    ];

    public function import()
    {
        return $this->belongsTo(Import::class, 'import_id', 'id');
    }
}
