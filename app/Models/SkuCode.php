<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkuCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'product_name',
        'brand',
        'default_value_of_product',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_value_of_product' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
