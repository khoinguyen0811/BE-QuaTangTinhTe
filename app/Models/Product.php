<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;

    public array $translatable = [
        'name',
        'short_description',
        'description',
    ];

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'image_url',
        'price',
        'compare_at_price',
        'cost_price',
        'stock_quantity',
        'manage_stock',
        'is_active',
        'is_featured',
        'published_at',
        
        // Custom ecommerce features matching the old database schema
        'material',
        'print_detail',
        'style',
        'care_instructions',
        'badge',
        'fake_sold_count',
        'min_fake_views',
        'max_fake_views',
        'is_web_exclusive',
        'is_limited',
        'limited_max_stock',
        'model_height',
        'model_weight',
        'model_size_worn',
        'size_chart_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'manage_stock' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        
        // Custom casts
        'fake_sold_count' => 'integer',
        'min_fake_views' => 'integer',
        'max_fake_views' => 'integer',
        'is_web_exclusive' => 'boolean',
        'is_limited' => 'boolean',
        'limited_max_stock' => 'integer',
    ];

    protected static function booted()
    {
        static::saved(function ($product) {
            if ($product->category_id) {
                $currentPivotIds = $product->categories()->pluck('categories.id')->toArray();
                if (empty($currentPivotIds)) {
                    $product->categories()->sync([$product->category_id]);
                } elseif ($currentPivotIds[0] !== (int)$product->category_id) {
                    $newPivotIds = array_unique(array_merge([(int)$product->category_id], $currentPivotIds));
                    $product->categories()->sync(array_slice($newPivotIds, 0, 3));
                }
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
