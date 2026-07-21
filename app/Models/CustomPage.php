<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'layout_draft',
        'layout_published',
        'builder_page_id',
        'builder_driver',
        'seo_title',
        'seo_description',
        'seo_image',
        'is_active',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'layout_draft' => 'array',
            'layout_published' => 'array',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }

    public function scopePublished($query)
    {
        return $query
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->whereNotNull('layout_published');
    }

    public function builderPage()
    {
        return $this->belongsTo(\HansSchouten\LaravelPageBuilder\Models\PageBuilderPage::class, 'builder_page_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
