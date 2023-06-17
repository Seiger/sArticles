<?php namespace Seiger\sArticles\Models;

use EvolutionCMS\Facades\UrlProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticlesFeature;

class sArticle extends Model
{
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['coverSrc', 'link'];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope('translates', function (Builder $builder) {
            $builder->leftJoin('s_article_translates', function ($leftJoin) {
                $leftJoin->on('s_articles.id', '=', 's_article_translates.article')
                    ->where('lang', function ($leftJoin) {
                        $leftJoin->select('lang')
                            ->from('s_article_translates')
                            ->whereRaw(DB::getTablePrefix().'s_article_translates.article = '.DB::getTablePrefix().'s_articles.id')
                            ->whereIn('lang', [evo()->getLocale(), 'base'])
                            ->orderByRaw('FIELD(lang, "'.evo()->getLocale().'", "base")')
                            ->limit(1);
                    });
            });
        });
    }

    /**
     * Get the features for the Article.
     */
    public function features()
    {
        return $this
            ->belongsToMany(sArticlesFeature::class, 's_article_features', 'article', 'feature')
            ->orderBy('s_articles_features.position');
    }

    /**
     * Get the tags for the Article.
     */
    public function tags()
    {
        return $this
            ->belongsToMany(sArticlesTag::class, 's_article_tags', 'article', 'tag')
            ->orderBy('s_articles_tags.base');
    }

    /**
     * Only active articles
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeActive($builder)
    {
        return $builder->where('s_articles.published', '1');
    }

    /**
     * Get the article cover src link
     *
     * @return string cover_src
     */
    public function getCoverSrcAttribute()
    {
        if (!empty($this->cover) && is_file(MODX_BASE_PATH . $this->cover)) {
            $coverSrc = MODX_SITE_URL . $this->cover;
        } else {
            $coverSrc = MODX_SITE_URL . 'assets/images/noimage.png';
        }

        return $coverSrc;
    }

    /**
     * Get the article link
     *
     * @return string link
     */
    public function getLinkAttribute()
    {
        if ($this->parent == 0) {
            $this->parent = evo()->getConfig('site_start', 1);
        }
        $base_url = UrlProcessor::makeUrl($this->parent);
        if (str_starts_with($base_url, '/')) {
            $base_url = MODX_SITE_URL . trim($base_url, '/');
        }
        if (!str_ends_with($base_url, '/')) {
            $base_url = str_replace(evo()->getConfig('friendly_url_suffix', ''), '', $base_url) . '/';
        }
        return $base_url.$this->alias.evo()->getConfig('friendly_url_suffix', '');
    }
}
