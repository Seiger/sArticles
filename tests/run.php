<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$passed = 0;
$failed = 0;
$currentGroup = 'general';

function s_articles_group(string $name, Closure $tests): void
{
    global $currentGroup;

    $previous = $currentGroup;
    $currentGroup = $name;
    echo "GROUP {$name}\n";
    $tests();
    $currentGroup = $previous;
}

function s_articles_test(string $name, Closure $test): void
{
    global $passed, $failed, $currentGroup;

    try {
        $test();
        $passed++;
        echo "PASS [{$currentGroup}] {$name}\n";
    } catch (Throwable $exception) {
        $failed++;
        echo "FAIL [{$currentGroup}] {$name}\n";
        echo '  ' . $exception->getMessage() . "\n";
    }
}

function s_articles_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function s_articles_assert_contains(string $needle, string $haystack, string $message): void
{
    s_articles_assert(str_contains($haystack, $needle), $message);
}

function s_articles_path(string $path): string
{
    global $root;

    return $root . '/' . ltrim($path, '/');
}

function s_articles_read(string $path): string
{
    $absolute = s_articles_path($path);
    s_articles_assert(is_file($absolute), 'Expected file to exist: ' . $path);

    return (string) file_get_contents($absolute);
}

function s_articles_config(string $path): array
{
    $absolute = s_articles_path($path);
    s_articles_assert(is_file($absolute), 'Expected config file to exist: ' . $path);

    $config = require $absolute;
    s_articles_assert(is_array($config), 'Config must return an array: ' . $path);

    return $config;
}

function s_articles_field_types(array $fields): array
{
    return array_values(array_unique(array_map(
        fn (array $field): string => (string) ($field['type'] ?? 'text'),
        $fields
    )));
}

s_articles_group('package', function () use ($root): void {
    s_articles_test('composer declares evo-ui as the manager UI dependency', function () use ($root): void {
        $composer = json_decode((string) file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);

        s_articles_assert(($composer['type'] ?? null) === 'evolution-cms-module', 'sArticles must stay an Evolution CMS module.');
        s_articles_assert(isset($composer['require']['evolution-cms/evo-ui']), 'sArticles must require evolution-cms/evo-ui.');
        s_articles_assert(($composer['require']['evolution-cms/evo-ui'] ?? null) === '^1.0.2', 'sArticles must require the EvoUI release with symlink-published runtime assets.');
        s_articles_assert(($composer['extra']['laravel']['providers'][0] ?? null) === 'Seiger\\sArticles\\sArticlesServiceProvider', 'Service provider must stay registered.');
        s_articles_assert(!isset($composer['extra']['laravel']['aliases']), 'Facade alias must be registered by the service provider, not generated as a custom alias file.');
        s_articles_assert(($composer['scripts']['test'] ?? null) === 'php tests/run.php', 'Composer test script must run the compatibility suite.');
    });

    s_articles_test('service provider registers sArticles facade alias at runtime', function (): void {
        $provider = s_articles_read('src/sArticlesServiceProvider.php');

        foreach ([
            'use EvolutionCMS\\AliasLoader;',
            'AliasLoader::getInstance()->alias(\'sArticles\', sArticlesFacade::class);',
            'function registerPublicApi(): void',
            '$this->app->alias(sArticles::class, \'sArticles\');',
        ] as $marker) {
            s_articles_assert_contains($marker, $provider, 'Missing runtime facade alias marker: ' . $marker);
        }

        s_articles_assert(!is_file(s_articles_path('config/sArticlesAlias.php')), 'Legacy published alias file should not remain in the package.');
    });

    s_articles_test('service provider protects Livewire discovery order', function (): void {
        $provider = s_articles_read('src/sArticlesServiceProvider.php');

        foreach ([
            'function registerLivewireModulePanel(): void',
            "\$this->app->bound('livewire.finder')",
            "\$this->app->afterResolving('livewire.finder'",
            '$this->registerLivewireModulePanel();',
            "Livewire::component('sarticles.module-panel'",
        ] as $marker) {
            s_articles_assert_contains($marker, $provider, 'Missing Livewire discovery guard marker: ' . $marker);
        }

        s_articles_assert(!str_contains($provider, 'LivewireServiceProvider::class'), 'sArticles must not boot Livewire directly without the EvoUI bridge.');
    });

    s_articles_test('settings use vendor defaults with optional project overrides', function (): void {
        $provider = s_articles_read('src/sArticlesServiceProvider.php');
        $controller = s_articles_read('src/Controllers/sArticlesController.php');

        foreach ([
            'function mergeSettingsConfig(): void',
            "require dirname(__DIR__) . '/config/sArticlesSettings.php'",
            "config('seiger.settings.sArticles', [])",
            'array_replace_recursive($defaults, $settings)',
            "'/resources/publish/seiger/settings/.gitkeep'",
            "config_path('seiger/settings/.gitkeep', true)",
        ] as $marker) {
            s_articles_assert_contains($marker, $provider, 'Missing settings merge/publish marker: ' . $marker);
        }

        s_articles_assert(!str_contains($provider, "config/sArticlesSettings.php' => config_path('seiger/settings/sArticles.php'"), 'Settings publish must not copy the full package defaults.');
        s_articles_assert(is_file(s_articles_path('resources/publish/seiger/settings/.gitkeep')), 'Settings publish placeholder must exist.');
        s_articles_assert_contains('mkdir($directory, 0775, true)', $controller, 'Settings save must create the custom settings directory on first change.');
        s_articles_assert_contains('file_put_contents($path, $string, LOCK_EX)', $controller, 'Settings save must write the project override atomically.');
    });
});

s_articles_group('module-shell', function (): void {
    s_articles_test('module panel uses evo-ui form/table shell and dirty navigation guard', function (): void {
        $panel = s_articles_read('views/livewire/module-panel.blade.php');
        $shell = s_articles_read('views/articles/shell.blade.php');

        foreach ([
            '<livewire:evo-ui.form',
            '<x-evo::table.livewire',
            'window.EvoUI.form.isDirty()',
            'data-evo-form-dirty',
            'x-on:evo-ui:form.saved.window',
            'wire:key="evo-ui-form-',
            'data-evo-tab-panel',
        ] as $marker) {
            s_articles_assert_contains($marker, $panel, 'Missing evo-ui module panel marker: ' . $marker);
        }

        s_articles_assert_contains("@include('evo::partials.assets')", $shell, 'sArticles shell must load EvoUI through the shared EvoUI asset partial.');
        s_articles_assert(!is_file(s_articles_path('views/partials/evo-ui-assets.blade.php')), 'sArticles must not duplicate EvoUI asset publishing or direct vendor asset URLs.');
        s_articles_assert(!str_contains($shell, 'core/vendor/evolution-cms/evo-ui/resources/'), 'The manager shell must not expose vendor asset URLs.');
    });
});

s_articles_group('articles-table', function (): void {
    s_articles_test('articles table config covers table/list, filters, typed columns and row actions', function (): void {
        $table = s_articles_config('config/articles/table.php');

        s_articles_assert(($table['provider'] ?? null) === \Seiger\sArticles\Tables\ArticlesTableData::class, 'Articles table must use ArticlesTableData provider.');
        s_articles_assert(($table['views'] ?? []) === ['table', 'list'], 'Articles table must expose table/list views.');
        s_articles_assert(($table['default_view'] ?? null) === 'table', 'Articles table default view must be table.');
        s_articles_assert(($table['search']['enabled'] ?? false) === true, 'Articles table search must stay enabled.');

        $filterStates = array_column($table['filters'] ?? [], 'state');
        foreach (['section', 'tag', 'category', 'feature', 'published_at', 'availability'] as $state) {
            s_articles_assert(in_array($state, $filterStates, true), 'Missing articles filter state: ' . $state);
        }

        $columnTypes = array_column($table['columns'] ?? [], 'type', 'key');
        foreach ([
            'cover' => 'image',
            'title' => 'link',
            'section' => 'link',
            'categories' => 'chips',
            'tags' => 'chips',
            'features' => 'chips',
            'published_at' => 'date',
            'views' => 'badge',
        ] as $key => $type) {
            s_articles_assert(($columnTypes[$key] ?? null) === $type, 'Unexpected article column type for ' . $key);
        }

        $rowActionMethods = array_column($table['row_actions'] ?? [], 'method', 'key');
        foreach (['publish' => 'togglePublished', 'edit' => 'openEditModal', 'duplicate' => 'duplicateRow', 'delete' => 'openDeleteModal'] as $key => $method) {
            s_articles_assert(($rowActionMethods[$key] ?? null) === $method, 'Missing article row action: ' . $key);
        }
    });

    s_articles_test('articles modal config covers article form, relations and builder contract', function (): void {
        $modal = s_articles_config('config/articles/modal.php');
        $fields = (array) ($modal['fields'] ?? []);
        $types = s_articles_field_types($fields);
        $names = array_column($fields, 'name');

        s_articles_assert(($modal['enabled'] ?? false) === true, 'Article modal must stay enabled.');
        s_articles_assert(($modal['layout'] ?? null) === 'split', 'Article modal must use split layout.');
        s_articles_assert(in_array('main', array_column($modal['tabs'] ?? [], 'name'), true), 'Article modal must have main tab.');
        s_articles_assert(in_array('content', array_column($modal['tabs'] ?? [], 'name'), true), 'Article modal must have content tab.');

        foreach (['alias', 'image', 'datetime-local', 'select', 'choices', 'builder'] as $type) {
            s_articles_assert(in_array($type, $types, true), 'Missing article modal field type: ' . $type);
        }

        foreach (['categories', 'main_tag', 'tags', 'features', 'relevants', 'content_builder'] as $name) {
            s_articles_assert(in_array($name, $names, true), 'Missing article modal field: ' . $name);
        }

        $providerFields = array_filter($fields, fn (array $field): bool => ($field['options_provider'] ?? null) === 'articleModalOptions');
        s_articles_assert(count($providerFields) >= 6, 'Article modal relation choices must use articleModalOptions.');

        $builder = array_values(array_filter($fields, fn (array $field): bool => ($field['name'] ?? null) === 'content_builder'))[0] ?? [];
        s_articles_assert(($builder['blocks_provider'] ?? null) === 'articleBuilderBlocks', 'Article builder must use articleBuilderBlocks provider.');
    });
});

s_articles_group('settings-form', function (): void {
    s_articles_test('settings form stays a config-backed evo-ui form with type config-map', function (): void {
        $form = s_articles_config('config/settings/form.php');

        s_articles_assert(($form['variant'] ?? null) === 'config', 'Settings form must stay config-backed.');
        s_articles_assert(($form['source']['type'] ?? null) === 'config', 'Settings form source type must be config.');
        s_articles_assert(($form['source']['root'] ?? null) === 'seiger.settings.sArticles', 'Settings form root must stay canonical.');

        $surface = var_export($form, true);
        foreach ([
            "'type' => 'rich_text_editors'",
            "'name' => 'section_template_ids'",
            "'type' => 'multi-select'",
            "EvolutionCMS\\\\Models\\\\SiteTemplate",
            "'type' => 'config-map'",
            "'name' => 'general.tvparams_on'",
            "'delete_guard'",
            "'table' => 's_articles'",
            "'column' => 'type'",
            "'protected_keys'",
            "'lock_first_key' => true",
        ] as $marker) {
            s_articles_assert_contains($marker, $surface, 'Missing settings form marker: ' . $marker);
        }
    });
});

s_articles_group('provider-hooks', function (): void {
    s_articles_test('frontend article routing uses current site start instead of a global blank resource', function (): void {
        $plugin = s_articles_read('plugins/sArticlesPlugin.php');
        $component = s_articles_read('src/sArticles.php');
        $controller = s_articles_read('src/Controllers/sArticlesController.php');

        foreach ([
            'evo()->setPlaceholder(\'article\', (int) $article->id);',
            'evo()->sendForward(sArticles::articleForwardResource());',
            '$templateAlias = sArticles::articleTemplateAlias();',
            '$article->template = sArticles::articleTemplateId($templateAlias);',
            '$article->templatealias = $templateAlias;',
            '$article->menutitle = $article->menutitle ?? $article->pagetitle ?? \'\';',
            "'article' => \$article",
            'function articleForwardResource(): int',
            'function articleTemplateAlias(): string',
            'function articleBelongsToCurrentSite(sArticle $article): bool',
            "Cache::get('sMultisite-' . \$siteKey . '-resources')",
            'sArticles::articleBelongsToCurrentSite($article)',
            "sArticle::where('s_articles.alias', \$articleAlias)->get()",
            "evo()->getConfig('sart_template_alias', '')",
            "evo()->getConfig('sart_blank', 0)",
            "evo()->getConfig('site_start', 1)",
            'parse_url((string) $article->link, PHP_URL_PATH)',
        ] as $marker) {
            s_articles_assert_contains($marker, $plugin . "\n" . $component . "\n" . $controller, 'Missing site-start article routing marker: ' . $marker);
        }

        s_articles_assert(!str_contains($plugin, "sendForward(sArticles::isLegacyMode() ?"), 'Article routing must not forward public URLs to the legacy blank resource.');
        s_articles_assert(!str_contains($plugin, 'if (sArticles::isLegacyMode()) {' . "\n" . '        return;' . "\n" . '    }'), 'Placeholder-based article hydration must work even when a legacy blank resource is configured.');
        s_articles_assert(!str_contains($controller, "str_replace(EVO_SITE_URL, '', \$article->link)"), 'Article URL cache must not depend on the current host constant.');
    });

    s_articles_test('ArticlesTableData owns module behavior behind evo-ui hooks', function (): void {
        $provider = s_articles_read('src/Tables/ArticlesTableData.php');

        foreach ([
            'public function deleteRow',
            'public function togglePublished',
            'public function duplicate',
            'public function modalDefaults',
            'public function modalData',
            'public function modalOptions',
            'public function modalFields',
            'public function articleModalOptions',
            'public function articleBuilderBlocks',
            'public function saveModal',
            'public function filters',
            'protected function sectionTemplateIds',
            'protected function defaultParentId',
            'protected function validatedParentId',
            '$this->sectionTemplateIds($type)',
            '$this->validatedParentId($type',
            "whereIn('template', \$templateIds)",
            "count(\$templateIds) ? []",
            'categories()->sync',
            'tags()->sync',
            'features()->sync',
            'setArticlesListing',
        ] as $marker) {
            s_articles_assert_contains($marker, $provider, 'Missing ArticlesTableData provider marker: ' . $marker);
        }
    });

    s_articles_test('article duplicate title suffix uses package-local translations', function (): void {
        $provider = s_articles_read('src/Tables/ArticlesTableData.php');

        s_articles_assert_contains("__('sArticles::global.duplicate_suffix')", $provider, 'Article duplicate titles must use the package-local noun suffix.');
        s_articles_assert(!str_contains($provider, "__('global.duplicate')"), 'Article duplicate titles must not reuse the manager action label.');

        $expected = [
            'de' => 'Kopie',
            'en' => 'copy',
            'fr' => 'copie',
            'pl' => 'kopia',
            'ru' => 'копия',
            'uk' => 'копія',
        ];

        foreach ($expected as $locale => $suffix) {
            $translations = require s_articles_path("lang/{$locale}/global.php");
            s_articles_assert(($translations['duplicate_suffix'] ?? null) === $suffix, "Duplicate suffix must be localized for {$locale}.");
            s_articles_assert(isset($translations['invalid_section_for_type']), "Invalid section message must be localized for {$locale}.");
        }
    });

    s_articles_test('article views use session and cookie markers instead of raw reload counts', function (): void {
        $component = s_articles_read('src/sArticles.php');

        foreach ([
            'function trackView(sArticle $article): void',
            'function hasTrackedArticleView(int $articleId): bool',
            'function rememberArticleView(int $articleId): void',
            'function articleViewSessionKey(): string',
            'session()->get($sessionKey',
            'session()->put($sessionKey',
            '$_SESSION[$sessionKey]',
            'setcookie(',
            'hash_equals(',
            's_articles_view_',
        ] as $marker) {
            s_articles_assert_contains($marker, $component, 'Missing article view tracking marker: ' . $marker);
        }

        s_articles_assert(!str_contains($component, "in_array(\$article->id, \$_SESSION['s_articles_article_views']"), 'Article views must not rely only on the legacy PHP session marker.');
    });
});

s_articles_group('builder', function (): void {
    s_articles_test('builder blocks cover rich editor, image/file and relation-style article content', function (): void {
        $configs = [];
        foreach (glob(s_articles_path('builder/*/config.php')) ?: [] as $configPath) {
            $configs[] = (string) file_get_contents($configPath);
        }

        $surface = implode("\n", $configs)
            . "\n" . s_articles_read('builder/richtext/template.blade.php')
            . "\n" . s_articles_read('builder/singleimg/template.blade.php')
            . "\n" . s_articles_read('builder/file/template.blade.php')
            . "\n" . s_articles_read('builder/articlepreview/template.blade.php');

        foreach ([
            "'id' => 'richtext'",
            "'id' => 'singleimg'",
            "'id' => 'singlefile'",
            "'id' => 'previewarticle'",
            "'id' => 'slider'",
            'textarea',
            'image_for_field',
            'BrowseFileServer',
        ] as $marker) {
            s_articles_assert_contains($marker, $surface, 'Missing builder compatibility marker: ' . $marker);
        }
    });

    s_articles_test('builder render views use Laravel vendor override conventions', function (): void {
        $provider = s_articles_read('src/sArticlesServiceProvider.php');
        $renderer = s_articles_read('src/Support/BuilderRenderer.php');
        $command = s_articles_read('src/Console/RerenderArticlesCommand.php');

        foreach ([
            "loadViewsFrom(dirname(__DIR__) . '/views', 'sarticles')",
            "view('sarticles::render.' . \$view",
            "views/vendor/sarticles/render",
            'sarticles:rerender',
            '{--articles=',
            '{--chunk=200',
            'chunkById',
        ] as $marker) {
            s_articles_assert_contains($marker, $provider . "\n" . $renderer . "\n" . $command, 'Missing builder render marker: ' . $marker);
        }

        foreach (glob(s_articles_path('views/render/*.blade.php')) ?: [] as $renderView) {
            s_articles_assert(is_file($renderView), 'Render view must exist: ' . $renderView);
        }

        foreach (glob(s_articles_path('builder/*/render.blade.php')) ?: [] as $legacyRender) {
            s_articles_assert(false, 'Legacy asset render file should not remain: ' . $legacyRender);
        }
    });
});

if ($failed > 0) {
    exit(1);
}

echo "OK {$passed} tests\n";
