<?php namespace Seiger\sArticles\Models;

use EvolutionCMS\Facades\UrlProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticlesAuthor;
use Seiger\sArticles\Models\sArticlesFeature;

/**
 * Class sArticle
 *
 * Represents an article model.
 *
 * @property-read string $link The URL of the article.
 */
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
     * Filter search
     *
     * @return mixed
     */
    public function scopeSearch()
    {
        if (request()->has('search')) {
            $fields = collect(['pagetitle', 'longtitle', 'introtext', 'content']);

            $search = Str::of(request('search'))
                ->stripTags()
                ->replaceMatches('/[^\p{L}\p{N}\@\.!#$%&\'*+-\/=?^_`{|}~]/iu', ' ') // allowed symbol in email
                ->replaceMatches('/(\s){2,}/', '$1') // removing extra spaces
                ->trim()->explode(' ')
                ->filter(fn($word) => mb_strlen($word) > 2);
            $select = collect([0]);
            $search->map(fn($word) => $fields->map(fn($field) => $select->push("(CASE WHEN `".DB::getTablePrefix()."s_article_translates`.`{$field}` LIKE '%{$word}%' THEN 1 ELSE 0 END)"))); // Generate points source
            return $this->addSelect('*', DB::Raw('(' . $select->implode(' + ') . ') as points'))
                ->when($search->count(), fn($query) => $query->where(fn($query) => $search->map(fn($word) => $fields->map(fn($field) => $query->orWhere($field, 'like', "%{$word}%")))))
                ->orderByDesc('points');
        }
    }

    /**
     * Get the author associated with the article.
     */
    public function author()
    {
        return $this->hasOne(sArticlesAuthor::class, 'autid', 'author_id');
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
        return $this->belongsToMany(sArticlesTag::class, 's_article_tags', 'article', 'tag');
    }

    /**
     * Get the categories for the Book.
     */
    public function categories()
    {
        return $this
            ->belongsToMany(sArticlesCategory::class, 's_article_categories', 'article', 'category')
            ->orderBy('s_articles_categories.base');
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
            $base_url = rtrim($base_url, evo()->getConfig('friendly_url_suffix', '')) . '/';
        }
        return $base_url.$this->alias.evo()->getConfig('friendly_url_suffix', '');
    }
}
