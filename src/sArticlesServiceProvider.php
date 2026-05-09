<?php namespace Seiger\sArticles;

use EvolutionCMS\ServiceProvider;
use Event;
use Livewire\Livewire;

/**
 * sArticlesServiceProvider package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class sArticlesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap package services after registration.
     *
     * Routes, config, migrations, views, translations, Livewire components, and publishable
     * assets are wired here.
     */
    public function boot()
    {
        // Add custom routes for package
        include(__DIR__ . '/Http/routes.php');

        $this->mergeConfigFrom(dirname(__DIR__) . '/config/sArticlesSettings.php', 'seiger.settings.sArticles');

        // Only Manager
        if (IN_MANAGER_MODE) {
            // Migration for create tables
            $this->loadMigrationsFrom(dirname(__DIR__) . '/database/migrations');

            // Views
            $this->loadViewsFrom(dirname(__DIR__) . '/views', 'sArticles');

            // MultiLang
            $this->loadTranslationsFrom(dirname(__DIR__) . '/lang', 'sArticles');

            // Check sArticles configuration
            $this->mergeConfigFrom(dirname(__DIR__) . '/config/sArticlesCheck.php', 'cms.settings');
            $this->mergeConfigFrom(dirname(__DIR__) . '/config/articles/table.php', 'sarticles.articles.table');
            $this->mergeConfigFrom(dirname(__DIR__) . '/config/authors/table.php', 'sarticles.authors.table');
            $this->mergeConfigFrom(dirname(__DIR__) . '/config/tags/table.php', 'sarticles.tags.table');
            $this->mergeConfigFrom(dirname(__DIR__) . '/config/categories/table.php', 'sarticles.categories.table');
            $this->mergeConfigFrom(dirname(__DIR__) . '/config/features/table.php', 'sarticles.features.table');
            $this->mergeConfigFrom(dirname(__DIR__) . '/config/comments/table.php', 'sarticles.comments.table');
            $this->mergeConfigFrom(dirname(__DIR__) . '/config/polls/table.php', 'sarticles.polls.table');
            $this->mergeConfigFrom(dirname(__DIR__) . '/config/tvparams/table.php', 'sarticles.tvparams.table');
            $this->mergeConfigFrom(dirname(__DIR__) . '/config/settings/form.php', 'evo-ui.forms.sarticles.settings');
            Livewire::component('sarticles.module-panel', \Seiger\sArticles\Livewire\ModulePanel::class);

            // For use config
            $this->publishes([
                dirname(__DIR__) . '/config/sArticlesAlias.php' => config_path('app/aliases/sArticles.php', true),
                dirname(__DIR__) . '/config/sArticlesSettings.php' => config_path('seiger/settings/sArticles.php', true),
                dirname(__DIR__) . '/images/noimage.png' => public_path('assets/images/noimage.png'),
                dirname(__DIR__) . '/images/seigerit-blue.svg' => public_path('assets/site/seigerit-blue.svg'),
                dirname(__DIR__) . '/views/s_articles_article.blade.php' => public_path('views/s_articles_article.blade.php'),
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
            ], 'sarticles');
        }

        $this->app->singleton(sArticles::class);
        $this->app->alias(sArticles::class, 'sArticles');
    }

    /**
     * Register package services with Evolution CMS.
     *
     * The provider adds plugins, manager module metadata, singleton bindings, and aliases used
     * by facade access.
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
            $this->app->registerModule(
                $lang['module_title'] ?? $lang['articles'],
                dirname(__DIR__) . '/module/sArticlesModule.php',
                $lang['module_icon'] ?? $lang['articles_icon']
            );
        }
    }
}
