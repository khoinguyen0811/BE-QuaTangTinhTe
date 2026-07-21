<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ProductVariant extends Model
{
    use HasTranslations;

    public array $translatable = [
        'name',
    ];

    protected $appends = [
        'size',
        'material',
        'color',
        'attribute_values',
    ];

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'image_url',
        'images',
        'option_values',
        'price',
        'compare_at_price',
        'stock_quantity',
        'allow_out_of_stock_order',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'option_values' => 'array',
        'images' => 'array',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'allow_out_of_stock_order' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getSizeAttribute(): ?string
    {
        return $this->option_values['size'] ?? null;
    }

    public function getMaterialAttribute(): ?string
    {
        return $this->option_values['material'] ?? null;
    }

    public function getColorAttribute(): ?string
    {
        return $this->option_values['color'] ?? null;
    }

    public function getAttributeValuesAttribute(): array
    {
        return $this->option_values ?? [];
    }
}
