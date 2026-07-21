<?php

namespace HansSchouten\LaravelPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;

class PageBuilderPageRevision extends Model
{
    protected $table = 'pagebuilder_page_revisions';

    protected $fillable = [
        'page_id',
        'revision',
        'project_json',
        'html',
        'css',
        'created_by',
    ];

    public function page()
    {
        return $this->belongsTo(PageBuilderPage::class, 'page_id');
    }
}
