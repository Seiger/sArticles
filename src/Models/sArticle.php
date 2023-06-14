<?php namespace Seiger\sOffers\Models;

use EvolutionCMS\Facades\UrlProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Seiger\sOffers\Controllers\sArticlesController;
use Seiger\sOffers\Models\sOFeature;

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
        $controller = new sArticlesController();
        $locale = evo()->getConfig('lang', $controller->langDefault());

        static::addGlobalScope('translates', function (Builder $builder) use ($locale) {
            $builder->leftJoin('s_offer_translates', function ($leftJoin) use ($locale) {
                $leftJoin->on('s_offers.id', '=', 's_offer_translates.offer')
                    ->where('lang', function ($leftJoin) use ($locale) {
                        $leftJoin->select('lang')
                            ->from('s_offer_translates')
                            ->whereRaw(DB::getTablePrefix().'s_offer_translates.offer = '.DB::getTablePrefix().'s_offers.id')
                            ->whereIn('lang', [$locale, 'base'])
                            ->orderByRaw('FIELD(lang, "'.$locale.'", "base")')
                            ->limit(1);
                    });
            });
        });
    }

    /**
     * Get the features for the Offer.
     */
    public function features()
    {
        return $this
            ->belongsToMany(sOFeature::class, 's_offer_features', 'offer', 'feature')
            ->orderBy('s_o_features.position');
    }

    /**
     * Only active offers
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeActive($builder)
    {
        return $builder->where('s_offers.published', '1');
    }

    /**
     * Get the offer cover src link
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
     * Get the offer link
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
            $base_url = MODX_SITE_URL . ltrim($base_url, '/');
        }
        return $base_url.$this->alias.evo()->getConfig('friendly_url_suffix', '');
    }
}