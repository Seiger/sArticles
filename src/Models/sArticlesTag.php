<?php namespace Seiger\sArticles\Models;

use Illuminate\Database\Eloquent\Model;
use Seiger\sArticles\Models\sArticle;

class sArticlesTag extends Model
{
    protected $primaryKey = 'tagid';

    /**
     * Get the Articles for the Tag.
     */
    public function articles()
    {
        return $this->belongsToMany(sArticle::class, 's_article_tags', 'tag', 'article');
    }
}
