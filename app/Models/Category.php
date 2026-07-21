<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasTranslations;

    public array $translatable = [
        'name',
        'description',
    ];

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image_url',
        'sort_order',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted()
    {
        static::deleting(function ($category) {
            if ($category->is_system) {
                throw new \RuntimeException('Không thể xóa danh mục hệ thống.');
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }
}
