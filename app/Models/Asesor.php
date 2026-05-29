<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asesor extends Model
{
    use HasUuids;

    protected $table = 'asesores';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get companies registered by this asesor.
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'vendedor_id');
    }
}
