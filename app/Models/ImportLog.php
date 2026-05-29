<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_hash',
        'total_rows',
        'success_rows',
        'error_rows',
        'status',
        'metrics',
    ];

    protected $casts = [
        'metrics' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(ImportLogDetail::class);
    }
}
