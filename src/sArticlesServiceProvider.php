<?php namespace Seiger\sOffers;

use EvolutionCMS\ServiceProvider;
use Event;

class sArticlesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Only Manager
        if (IN_MANAGER_MODE) {
            // Add custom routes for package
            include(__DIR__ . '/Http/routes.php');

            // Migration for create tables
            $this->loadMigrationsFrom(dirname(__DIR__) . '/database/migrations');

            // Views
            $this->loadViewsFrom(dirname(__DIR__) . '/views', 'sOffers');

            // MultiLang
            $this->loadTranslationsFrom(dirname(__DIR__) . '/lang', 'sOffers');

            // For use config
            $this->publishes([
                dirname(__DIR__) . '/config/sOffersAlias.php' => config_path('app/aliases/sOffers.php', true),
                dirname(__DIR__) . '/config/sOffer.php' => config_path('cms/settings/sOffer.php', true),
                dirname(__DIR__) . '/images/noimage.png' => public_path('assets/images/noimage.png', true),
            ]);
        }

        $this->app->singleton(sArticles::class);
        $this->app->alias(sArticles::class, 'sOffers');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Add plugins to Evo
        $this->loadPluginsFrom(dirname(__DIR__) . '/plugins/');

        // Only Manager
        if (IN_MANAGER_MODE) {
            // Add module to Evo. Module ID is md5('sOfferModule').
            $lang = 'en';
            if (isset($_SESSION['mgrUsrConfigSet']['manager_language'])) {
                $lang = $_SESSION['mgrUsrConfigSet']['manager_language'];
            } else {
                if (is_file(evo()->getSiteCacheFilePath())) {
                    $siteCache = file_get_contents(evo()->getSiteCacheFilePath());
                    preg_match('@\$c\[\'manager_language\'\]="\w+@i', $siteCache, $matches);
                    if (count($matches)) {
                        $lang = str_replace('$c[\'manager_language\']="', '', $matches[0]);
                    }
                }
            }
            $lang = include_once dirname(__DIR__) . '/lang/' . $lang . '/global.php';
            $this->app->registerModule($lang['offers'], dirname(__DIR__) . '/module/sOfferModule.php', $lang['offers_icon']);
        }
    }
}