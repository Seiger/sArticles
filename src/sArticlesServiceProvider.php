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

            // Check sArticles configuration
            $this->mergeConfigFrom(dirname(__DIR__) . '/config/sArticlesCheck.php', 'cms.settings');

            // For use config
            $this->publishes([
                dirname(__DIR__) . '/config/sArticlesAlias.php' => config_path('app/aliases/sArticles.php', true),
                dirname(__DIR__) . '/config/sArticlesSettings.php' => config_path('seiger/settings/sArticles.php', true),
                dirname(__DIR__) . '/images/noimage.png' => public_path('assets/images/noimage.png'),
                dirname(__DIR__) . '/images/seigerit-blue.svg' => public_path('assets/site/seigerit-blue.svg'),
                dirname(__DIR__) . '/builder/accordion/config.php' => public_path('assets/modules/sarticles/builder/accordion/config.php'),
                dirname(__DIR__) . '/builder/accordion/render.blade.php' => public_path('assets/modules/sarticles/builder/accordion/render.blade.php'),
                dirname(__DIR__) . '/builder/accordion/template.blade.php' => public_path('assets/modules/sarticles/builder/accordion/template.blade.php'),
                dirname(__DIR__) . '/builder/articlepreview/config.php' => public_path('assets/modules/sarticles/builder/articlepreview/config.php'),
                dirname(__DIR__) . '/builder/articlepreview/render.blade.php' => public_path('assets/modules/sarticles/builder/articlepreview/render.blade.php'),
                dirname(__DIR__) . '/builder/articlepreview/template.blade.php' => public_path('assets/modules/sarticles/builder/articlepreview/template.blade.php'),
                dirname(__DIR__) . '/builder/file/config.php' => public_path('assets/modules/sarticles/builder/file/config.php'),
                dirname(__DIR__) . '/builder/file/render.blade.php' => public_path('assets/modules/sarticles/builder/file/render.blade.php'),
                dirname(__DIR__) . '/builder/file/template.blade.php' => public_path('assets/modules/sarticles/builder/file/template.blade.php'),
                dirname(__DIR__) . '/builder/framevideo/config.php' => public_path('assets/modules/sarticles/builder/framevideo/config.php'),
                dirname(__DIR__) . '/builder/framevideo/render.blade.php' => public_path('assets/modules/sarticles/builder/framevideo/render.blade.php'),
                dirname(__DIR__) . '/builder/framevideo/template.blade.php' => public_path('assets/modules/sarticles/builder/framevideo/template.blade.php'),
                dirname(__DIR__) . '/builder/imgandtext/config.php' => public_path('assets/modules/sarticles/builder/imgandtext/config.php'),
                dirname(__DIR__) . '/builder/imgandtext/render.blade.php' => public_path('assets/modules/sarticles/builder/imgandtext/render.blade.php'),
                dirname(__DIR__) . '/builder/imgandtext/template.blade.php' => public_path('assets/modules/sarticles/builder/imgandtext/template.blade.php'),
                dirname(__DIR__) . '/builder/note/config.php' => public_path('assets/modules/sarticles/builder/note/config.php'),
                dirname(__DIR__) . '/builder/note/render.blade.php' => public_path('assets/modules/sarticles/builder/note/render.blade.php'),
                dirname(__DIR__) . '/builder/note/template.blade.php' => public_path('assets/modules/sarticles/builder/note/template.blade.php'),
                dirname(__DIR__) . '/builder/note/icon-note.svg' => public_path('assets/modules/sarticles/builder/note/icon-note.svg'),
                dirname(__DIR__) . '/builder/poll/config.php' => public_path('assets/modules/sarticles/builder/poll/config.php'),
                dirname(__DIR__) . '/builder/poll/render.blade.php' => public_path('assets/modules/sarticles/builder/poll/render.blade.php'),
                dirname(__DIR__) . '/builder/poll/template.blade.php' => public_path('assets/modules/sarticles/builder/poll/template.blade.php'),
                dirname(__DIR__) . '/builder/quote/config.php' => public_path('assets/modules/sarticles/builder/quote/config.php'),
                dirname(__DIR__) . '/builder/quote/render.blade.php' => public_path('assets/modules/sarticles/builder/quote/render.blade.php'),
                dirname(__DIR__) . '/builder/quote/template.blade.php' => public_path('assets/modules/sarticles/builder/quote/template.blade.php'),
                dirname(__DIR__) . '/builder/richtext/config.php' => public_path('assets/modules/sarticles/builder/richtext/config.php'),
                dirname(__DIR__) . '/builder/richtext/render.blade.php' => public_path('assets/modules/sarticles/builder/richtext/render.blade.php'),
                dirname(__DIR__) . '/builder/richtext/template.blade.php' => public_path('assets/modules/sarticles/builder/richtext/template.blade.php'),
                dirname(__DIR__) . '/builder/singleimg/config.php' => public_path('assets/modules/sarticles/builder/singleimg/config.php'),
                dirname(__DIR__) . '/builder/singleimg/render.blade.php' => public_path('assets/modules/sarticles/builder/singleimg/render.blade.php'),
                dirname(__DIR__) . '/builder/singleimg/template.blade.php' => public_path('assets/modules/sarticles/builder/singleimg/template.blade.php'),
                dirname(__DIR__) . '/builder/slider/config.php' => public_path('assets/modules/sarticles/builder/slider/config.php'),
                dirname(__DIR__) . '/builder/slider/render.blade.php' => public_path('assets/modules/sarticles/builder/slider/render.blade.php'),
                dirname(__DIR__) . '/builder/slider/template.blade.php' => public_path('assets/modules/sarticles/builder/slider/template.blade.php'),
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
