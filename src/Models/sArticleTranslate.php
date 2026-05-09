<?php namespace Seiger\sArticles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * sArticleTranslate package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class sArticleTranslate extends Model
{
    protected $primaryKey = 'tid';

    /**
     * Article for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @return BelongsTo Value returned for the caller.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(sArticle::class, 'article', 'id');
    }
}
