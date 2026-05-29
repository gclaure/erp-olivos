<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportLogDetail extends Model
{
    use HasUuids;

    protected $fillable = [
        'import_log_id',
        'row_number',
        'status',
        'message',
        'row_data',
    ];

    protected $casts = [
        'row_data' => 'array',
    ];

    public function importLog()
    {
        return $this->belongsTo(ImportLog::class);
    }
}
