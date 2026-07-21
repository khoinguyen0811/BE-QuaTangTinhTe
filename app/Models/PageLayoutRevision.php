<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageLayoutRevision extends Model
{
    protected $fillable = [
        'page_layout_id',
        'revision',
        'event',
        'content',
        'created_by',
        'note',
    ];

    protected $casts = [
        'revision' => 'integer',
        'content' => 'array',
    ];

    public function pageLayout()
    {
        return $this->belongsTo(PageLayout::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
