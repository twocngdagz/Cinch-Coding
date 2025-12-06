<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'status',
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }
}

