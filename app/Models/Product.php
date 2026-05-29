<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\HasSku;

class Product extends Model
{
    use HasUuids, SoftDeletes, HasSku;

    protected $fillable = [
        'code',
        'name',
        'description',
        'image_path',
        'price',
        'min_stock',
        'is_active',
        'has_expiration',
        'unit_of_measure_id',
        'location',
        'brand',
        'reference_link',
        'slug',
        'drive_links',
        'units_per_package',
        'package_name',
    ];

    protected $casts = [
        'price'            => 'decimal:4',
        'min_stock'        => 'decimal:4',
        'units_per_package'=> 'decimal:4',
        'is_active'        => 'boolean',
        'has_expiration'   => 'boolean',
        'drive_links'      => 'array',
    ];

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function kardexes(): HasMany
    {
        return $this->hasMany(Kardex::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (empty($product->slug) && !empty($product->name)) {
                $originalSlug = Str::slug($product->name);
                $slug = $originalSlug;
                $count = 1;

                while (Product::where('slug', $slug)
                    ->when($product->id, fn($q) => $q->where('id', '!=', $product->id))
                    ->exists()
                ) {
                    $slug = "{$originalSlug}-{$count}";
                    $count++;
                }

                $product->slug = $slug;
            }
        });

    }
}

