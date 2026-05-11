<?php namespace Seiger\sArticles;

use EvolutionCMS\ServiceProvider;
use EvoUI\EvoUI;
use Event;
use Livewire\Livewire;
use Seiger\sArticles\Console\RerenderArticlesCommand;
use Seiger\sArticles\Support\BuilderRenderer;

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
     * Routes, config, lowercase package views, translations, publishable assets, and console
     * commands are wired here.
     *
     * Livewire component registration is deferred until the application is fully booted. This
     * keeps composer/package discovery safe on fresh installs where sArticles can boot before
     * Livewire has registered its internal services such as `livewire.finder`.
     */
    public function boot()
    {
        // Add custom routes for package
        include(__DIR__ . '/Http/routes.php');

        $this->mergeConfigFrom(dirname(__DIR__) . '/config/sArticlesSettings.php', 'seiger.settings.sArticles');
        $this->loadViewsFrom(dirname(__DIR__) . '/views', 'sarticles');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RerenderArticlesCommand::class,
            ]);
        }

        // Only Manager
        if (defined('IN_MANAGER_MODE') && IN_MANAGER_MODE) {
            // Migration for create tables
            $this->loadMigrationsFrom(dirname(__DIR__) . '/database/migrations');

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
            app(EvoUI::class)->registerFormField('types', 'sarticles::evo-ui.form.types-config-map');
            $this->app->booted(function () {
                Livewire::component('sarticles.module-panel', \Seiger\sArticles\Livewire\ModulePanel::class);
            });

            // For use config
            $this->publishes([
                dirname(__DIR__) . '/config/sArticlesAlias.php' => config_path('app/aliases/sArticles.php', true),
                dirname(__DIR__) . '/config/sArticlesSettings.php' => config_path('seiger/settings/sArticles.php', true),
                dirname(__DIR__) . '/images/noimage.png' => public_path('assets/images/noimage.png'),
                dirname(__DIR__) . '/images/seigerit-blue.svg' => public_path('assets/site/seigerit-blue.svg'),
                dirname(__DIR__) . '/views/s_articles_article.blade.php' => public_path('views/s_articles_article.blade.php'),
                dirname(__DIR__) . '/builder/note/icon-note.svg' => public_path('assets/images/sarticles/icon-note.svg'),
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
        $this->app->singleton(BuilderRenderer::class);

        // Only Manager
        if (defined('IN_MANAGER_MODE') && IN_MANAGER_MODE) {
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
