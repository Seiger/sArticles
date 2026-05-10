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
        s_articles_assert(($composer['extra']['laravel']['providers'][0] ?? null) === 'Seiger\\sArticles\\sArticlesServiceProvider', 'Service provider must stay registered.');
        s_articles_assert(($composer['scripts']['test'] ?? null) === 'php tests/run.php', 'Composer test script must run the compatibility suite.');
    });
});

s_articles_group('module-shell', function (): void {
    s_articles_test('module panel uses evo-ui form/table shell and dirty navigation guard', function (): void {
        $panel = s_articles_read('views/livewire/module-panel.blade.php');

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
            "'type' => 'config-map'",
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
            'categories()->sync',
            'tags()->sync',
            'features()->sync',
            'setArticlesListing',
        ] as $marker) {
            s_articles_assert_contains($marker, $provider, 'Missing ArticlesTableData provider marker: ' . $marker);
        }
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
