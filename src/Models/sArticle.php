<?php namespace Seiger\sArticles\Models;

use Carbon\Carbon;
use EvolutionCMS\Facades\UrlProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
    protected $appends = ['coverSrc', 'link', 'dateObj'];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope('translate', function (Builder $builder) {
            if (!isset($builder->getQuery()->columns)) {
                $builder->select('*');
            }

            $locale = app()->getLocale();
            foreach ($builder->getQuery()->columns as $key => $column) {
                if (is_string($column) && str_starts_with($column, 'locale.')) {
                    $locale = explode('.', $column)[1];
                    unset($builder->getQuery()->columns[$key]);
                }
            }

            $builder->leftJoin('s_article_translates as sat', function ($leftJoin) use ($builder, $locale) {
                $leftJoin->on('s_articles.id', '=', 'sat.article')
                    ->where('sat.lang', function ($leftJoin) use ($builder, $locale) {
                        $leftJoin->select('lang')
                            ->from('s_article_translates as t')
                            ->whereRaw('`' . DB::getTablePrefix() . 't`.`article` = `' . DB::getTablePrefix() . 's_articles`.`id`')
                            ->whereIn('lang', [$locale, 'base'])
                            ->orderByRaw(
                                'CASE WHEN `lang` = ? THEN 0 WHEN `lang` = ? THEN 1 ELSE 2 END',
                                [$locale, 'base']
                            )
                            ->limit(1);
                    });
            });
        });
    }

    /**
     * Apply search filters to the query
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder The query builder object
     *
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder object
     */
    public function scopeSearch($builder)
    {
        if (request()->has('search')) {
            if (!isset($builder->getQuery()->columns)) {
                $builder->select('*');
            }

            $fields = collect([
                'sat.pagetitle',
                'sat.longtitle',
                'sat.introtext',
                'sat.content',
            ]);

            $search = Str::of(request('search'))
                ->stripTags()
                ->replaceMatches('/[^\p{L}\p{N}\@\.!#$%&\'*+-\/=?^_`{|}~]/iu', ' ') // allowed symbol in email
                ->replaceMatches('/(\s){2,}/', '$1') // removing extra spaces
                ->trim()->explode(' ')
                ->map(fn($word) => mb_strtolower($word))
                ->filter(fn($word) => mb_strlen($word) > 0);

            if (!$search->count()) {
                return $builder;
            }

            $select = collect([0]);
            $bindings = [];
            $exact = '%' . addcslashes(mb_strtolower($search->implode(' ')), '\\%_') . '%';

            $fields->each(function ($field) use ($builder, $select, $exact, &$bindings) {
                $select->push("(CASE WHEN LOWER(" . $builder->getGrammar()->wrap($field) . ") LIKE ? ESCAPE '\\\\' THEN 10 ELSE 0 END)");
                $bindings[] = $exact;
            }); // Generate Exact match points source
            $search->each(function ($word) use ($fields, $builder, $select, &$bindings) {
                $like = '%' . addcslashes($word, '\\%_') . '%';
                $fields->each(function ($field) use ($builder, $select, $like, &$bindings) {
                    $select->push("(CASE WHEN LOWER(" . $builder->getGrammar()->wrap($field) . ") LIKE ? ESCAPE '\\\\' THEN 1 ELSE 0 END)");
                    $bindings[] = $like;
                });
            }); // Generate Partial match points source

            $s = $builder->selectRaw('(' . $select->implode(' + ') . ') as points', $bindings);
            $s->when($search->count(), function ($query) use ($search, $fields, $builder) {
                $query->where(function ($query) use ($search, $fields, $builder) {
                    $search->each(function ($word) use ($fields, $query, $builder) {
                        $like = '%' . addcslashes($word, '\\%_') . '%';
                        $fields->each(function ($field) use ($query, $builder, $like) {
                            $query->orWhereRaw("LOWER(" . $builder->getGrammar()->wrap($field) . ") LIKE ? ESCAPE '\\\\'", [$like]);
                        });
                    });
                });
            });
            return $s->orderByDesc('points');
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

    /**
     * Get the article link
     *
     * @return string link
     */
    public function getDateObjAttribute()
    {
        return Carbon::parse($this->published_at);
    }
}
