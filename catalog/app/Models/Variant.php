<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Variant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'compare_at_price',
        'options',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

