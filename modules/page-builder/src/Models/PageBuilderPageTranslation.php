<?php

namespace HansSchouten\LaravelPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;

class PageBuilderPageTranslation extends Model
{
    protected $table = 'pagebuilder_page_translations';

    protected $fillable = [
        'page_id',
        'locale',
        'title',
        'meta_title',
        'meta_description',
        'route',
    ];

    public function page()
    {
        return $this->belongsTo(PageBuilderPage::class, 'page_id');
    }
}
