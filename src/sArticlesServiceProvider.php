<?php namespace Seiger\sArticles;

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
            $this->loadViewsFrom(dirname(__DIR__) . '/views', 'sArticles');

            // MultiLang
            $this->loadTranslationsFrom(dirname(__DIR__) . '/lang', 'sArticles');

            // For use config
            $this->publishes([
                dirname(__DIR__) . '/config/sArticlesAlias.php' => config_path('app/aliases/sArticles.php', true),
                dirname(__DIR__) . '/config/sArticlesSettings.php' => config_path('seiger/settings/sArticles.php', true),
                dirname(__DIR__) . '/images/noimage.png' => public_path('assets/images/noimage.png'),
                dirname(__DIR__) . '/images/seigerit-yellow.svg' => public_path('assets/site/seigerit-yellow.svg'),
                dirname(__DIR__) . '/builder/articlepreview/config.php' => public_path('assets/modules/sarticles/builder/articlepreview/config.php'),
                dirname(__DIR__) . '/builder/articlepreview/render.php' => public_path('assets/modules/sarticles/builder/articlepreview/render.php'),
                dirname(__DIR__) . '/builder/articlepreview/template.php' => public_path('assets/modules/sarticles/builder/articlepreview/template.php'),
                dirname(__DIR__) . '/builder/framevideo/config.php' => public_path('assets/modules/sarticles/builder/framevideo/config.php'),
                dirname(__DIR__) . '/builder/framevideo/render.php' => public_path('assets/modules/sarticles/builder/framevideo/render.php'),
                dirname(__DIR__) . '/builder/framevideo/template.php' => public_path('assets/modules/sarticles/builder/framevideo/template.php'),
                dirname(__DIR__) . '/builder/poll/config.php' => public_path('assets/modules/sarticles/builder/poll/config.php'),
                dirname(__DIR__) . '/builder/poll/render.php' => public_path('assets/modules/sarticles/builder/poll/render.php'),
                dirname(__DIR__) . '/builder/poll/template.php' => public_path('assets/modules/sarticles/builder/poll/template.php'),
                dirname(__DIR__) . '/builder/quote/config.php' => public_path('assets/modules/sarticles/builder/quote/config.php'),
                dirname(__DIR__) . '/builder/quote/render.php' => public_path('assets/modules/sarticles/builder/quote/render.php'),
                dirname(__DIR__) . '/builder/quote/template.php' => public_path('assets/modules/sarticles/builder/quote/template.php'),
                dirname(__DIR__) . '/builder/richtext/config.php' => public_path('assets/modules/sarticles/builder/richtext/config.php'),
                dirname(__DIR__) . '/builder/richtext/render.php' => public_path('assets/modules/sarticles/builder/richtext/render.php'),
                dirname(__DIR__) . '/builder/richtext/template.php' => public_path('assets/modules/sarticles/builder/richtext/template.php'),
                dirname(__DIR__) . '/builder/singleimg/config.php' => public_path('assets/modules/sarticles/builder/singleimg/config.php'),
                dirname(__DIR__) . '/builder/singleimg/render.php' => public_path('assets/modules/sarticles/builder/singleimg/render.php'),
                dirname(__DIR__) . '/builder/singleimg/template.php' => public_path('assets/modules/sarticles/builder/singleimg/template.php'),
                dirname(__DIR__) . '/builder/slider/config.php' => public_path('assets/modules/sarticles/builder/slider/config.php'),
                dirname(__DIR__) . '/builder/slider/render.php' => public_path('assets/modules/sarticles/builder/slider/render.php'),
                dirname(__DIR__) . '/builder/slider/template.php' => public_path('assets/modules/sarticles/builder/slider/template.php'),
            ]);
        }

        $this->app->singleton(sArticles::class);
        $this->app->alias(sArticles::class, 'sArticles');
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
            $this->app->registerModule($lang['articles'], dirname(__DIR__) . '/module/sArticlesModule.php', $lang['articles_icon']);
        }
    }
}
