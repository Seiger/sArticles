<?php namespace Seiger\sArticles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class sArticleTranslate extends Model
{
    protected $primaryKey = 'tid';

    /**
     * Get the article that owns the translation.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(sArticle::class, 'article', 'id');
    }
}
