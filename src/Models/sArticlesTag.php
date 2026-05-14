<?php namespace Seiger\sArticles\Models;

use Illuminate\Database\Eloquent\Model;
use Seiger\sArticles\Models\sArticle;

/**
 * sArticlesTag package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class sArticlesTag extends Model
{
    protected $primaryKey = 'tagid';

    /**
     * Articles for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     */
    public function articles()
    {
        return $this->belongsToMany(sArticle::class, 's_article_tags', 'tag', 'article');
    }
}
