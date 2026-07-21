<?php

namespace HansSchouten\LaravelPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;

class PageBuilderPage extends Model
{
    protected $table = 'pagebuilder_pages';

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($builderPage) {
            if (\App\Models\CustomPage::where('builder_page_id', $builderPage->id)->exists()) {
                throw new \Exception('Cannot delete builder page while linked to a custom page.');
            }
        });
    }

    protected $fillable = [
        'name',
        'layout',
        'data',
        'draft_html',
        'draft_css',
        'current_revision',
    ];

    public function translations()
    {
        return $this->hasMany(PageBuilderPageTranslation::class, 'page_id');
    }

    public function revisions()
    {
        return $this->hasMany(PageBuilderPageRevision::class, 'page_id');
    }
}
