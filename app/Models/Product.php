<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'unique_id',
        'category_id',
        'name',
        'description',
        'images',
        'variants',
        'combinations',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'variants' => 'array',
            'combinations' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->unique_id)) {
                do {
                    $uniqueId = (string) random_int(
                        100000000000,
                        999999999999
                    );
                } while (
                    static::where('unique_id', $uniqueId)->exists()
                );

                $product->unique_id = $uniqueId;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getRouteKeyName(): string
    {
        return 'unique_id';
    }
}