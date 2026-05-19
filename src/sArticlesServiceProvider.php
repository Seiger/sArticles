<?php namespace Seiger\sArticles;

use EvolutionCMS\ServiceProvider;
use EvolutionCMS\AliasLoader;
use EvoUI\EvoUI;
use Event;
use Livewire\Livewire;
use Seiger\sArticles\Console\RerenderArticlesCommand;
use Seiger\sArticles\Facades\sArticles as sArticlesFacade;
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

        $this->mergeSettingsConfig();
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
                $this->registerLivewireModulePanel();
            });

            // For use config
            $this->publishes([
                dirname(__DIR__) . '/resources/publish/seiger/settings/.gitkeep' => config_path('seiger/settings/.gitkeep', true),
                dirname(__DIR__) . '/images/noimage.png' => public_path('assets/images/noimage.png'),
                dirname(__DIR__) . '/images/seigerit-blue.svg' => public_path('assets/site/seigerit-blue.svg'),
                dirname(__DIR__) . '/views/s_articles_article.blade.php' => public_path('views/s_articles_article.blade.php'),
                dirname(__DIR__) . '/builder/note/icon-note.svg' => public_path('assets/images/sarticles/icon-note.svg'),
            ], 'sarticles');
        }

    }

    /**
     * Merge package defaults with optional project-level settings.
     *
     * sArticles should work immediately from the vendor defaults, while projects that save or
     * publish local settings may override only the values they need. A recursive merge keeps new
     * default keys available after package updates instead of letting an older custom settings file
     * shadow entire nested sections.
     *
     * @return void No value is returned; the application config repository is updated in place.
     * @since 2.1.0
     */
    protected function mergeSettingsConfig(): void
    {
        $defaults = require dirname(__DIR__) . '/config/sArticlesSettings.php';
        $settings = config('seiger.settings.sArticles', []);

        if (!is_array($settings)) {
            $settings = [];
        }

        $this->app['config']->set('seiger.settings.sArticles', array_replace_recursive($defaults, $settings));
    }

    /**
     * Register the manager Livewire component when the EvoUI runtime is ready.
     *
     * sArticles must not boot Livewire directly because Evolution CMS needs EvoUI to provide the
     * Laravel bridge bindings used by Livewire v4. If package discovery boots providers in a
     * different order, the method waits until `livewire.finder` is resolved by EvoUI and registers
     * the package component at that point.
     *
     * @return void No value is returned; the Livewire component is registered now or deferred.
     * @since 2.1.0
     */
    protected function registerLivewireModulePanel(): void
    {
        if ($this->app->bound('livewire.finder')) {
            Livewire::component('sarticles.module-panel', \Seiger\sArticles\Livewire\ModulePanel::class);
            return;
        }

        $this->app->afterResolving('livewire.finder', function () {
            Livewire::component('sarticles.module-panel', \Seiger\sArticles\Livewire\ModulePanel::class);
        });
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
        $this->registerPublicApi();

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

    /**
     * Register the package public API for container and facade-style access.
     *
     * The package intentionally supports `sArticles::...` in snippets, chunks, Blade templates,
     * and project code. Registering the facade alias at runtime keeps that public API available
     * without requiring `core/custom/config/app/aliases/sArticles.php` to be copied into every
     * installation.
     *
     * The container alias remains available for code that resolves the service through
     * `app('sArticles')` instead of using the facade.
     *
     * @since 2.1.0
     */
    protected function registerPublicApi(): void
    {
        $this->app->singleton(sArticles::class);
        $this->app->alias(sArticles::class, 'sArticles');

        AliasLoader::getInstance()->alias('sArticles', sArticlesFacade::class);
    }
}
