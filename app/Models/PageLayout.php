<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageLayout extends Model
{
    protected $fillable = [
        'page_key',
        'schema_version',
        'draft_content',
        'published_content',
        'draft_revision',
        'published_revision',
        'updated_by',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'schema_version' => 'integer',
        'draft_content' => 'array',
        'published_content' => 'array',
        'draft_revision' => 'integer',
        'published_revision' => 'integer',
        'published_at' => 'datetime',
    ];

    public function revisions()
    {
        return $this->hasMany(PageLayoutRevision::class);
    }
}
