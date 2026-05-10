<?php namespace Seiger\sArticles\Support;

use Illuminate\Support\Collection;

/**
 * Resolve and render sArticles content builder blocks.
 *
 * The builder JSON stored in article translations is the source of truth. This renderer turns
 * that structured payload into the materialized HTML kept in the legacy `content` column while
 * using Laravel package views, so sites can override individual blocks through
 * `views/vendor/sarticles/render/*.blade.php`.
 *
 * @since 2.0.0
 */
class BuilderRenderer
{
    /**
     * Render a full builder payload into compact frontend HTML.
     *
     * Unknown block IDs are skipped instead of failing the whole article. This keeps old content
     * editable when a site temporarily disables or removes a builder block.
     *
     * @param array<int, array<string, mixed>> $builder Builder data in storage format.
     * @return string Materialized HTML for the article translation content column.
     * @since 2.0.0
     */
    public function renderContent(array $builder): string
    {
        $renders = collect($this->configs())
            ->mapWithKeys(fn (array $config) => [(string) ($config['id'] ?? '') => (string) ($config['view'] ?? '')])
            ->filter(fn (string $view, string $id) => $id !== '' && $view !== '')
            ->all();

        if (!count($renders)) {
            return '';
        }

        $content = collect($builder)
            ->map(function (array $item, int $position) use ($renders) {
                $id = (string) array_key_first($item);

                if ($id === '' || !isset($renders[$id])) {
                    return '';
                }

                return $this->renderBlock($renders[$id], $id, $item[$id] ?? '', $position);
            })
            ->implode('');

        return str_replace([chr(9), chr(10), chr(13), '  '], '', $content);
    }

    /**
     * Render one builder block through the lowercase package view namespace.
     *
     * Laravel resolves overrides from `views/vendor/sarticles/render/<view>.blade.php` before
     * falling back to the package view registered by the service provider.
     *
     * @param string $view Render view basename resolved from the builder directory name.
     * @param string $id Stored builder block identifier.
     * @param mixed $value Stored block payload.
     * @param int $position Zero-based block position inside the article builder.
     * @return string Rendered block HTML.
     * @since 2.0.0
     */
    public function renderBlock(string $view, string $id, mixed $value, int $position = 0): string
    {
        return view('sarticles::render.' . $view, compact('id', 'value', 'position'))->render();
    }

    /**
     * Discover content builder block configuration from package sources.
     *
     * The 2.x builder no longer uses published assets as the runtime source. Config and manager
     * templates are read from the package, while frontend render HTML is handled by package views.
     *
     * @return array<int, array<string, mixed>> Builder definitions keyed for manager and render flows.
     * @since 2.0.0
     */
    public function configs(): array
    {
        $configs = [];

        if (!class_exists('sArticles') && class_exists(\Seiger\sArticles\Facades\sArticles::class)) {
            class_alias(\Seiger\sArticles\Facades\sArticles::class, 'sArticles');
        }

        foreach (glob($this->builderPath() . '/*/config.php') ?: [] as $path) {
            $view = basename(dirname($path));
            $config = require $path;

            if (!is_array($config)) {
                continue;
            }

            $id = (string) ($config['id'] ?? $view);

            if ($id === '' || isset($configs[$id])) {
                continue;
            }

            $configs[$id] = array_merge($config, [
                'id' => $id,
                'template' => $view,
                'view' => $view,
                'template_path' => dirname($path) . '/template.blade.php',
                'render_view' => 'sarticles::render.' . $view,
            ]);
        }

        return array_values($configs);
    }

    /**
     * Return the package builder directory used by manager templates.
     *
     * The path stays internal to the package; custom frontend markup belongs in Laravel's
     * `views/vendor/sarticles/render` override directory instead.
     *
     * @return string Absolute builder directory path.
     * @since 2.0.0
     */
    public function builderPath(): string
    {
        return dirname(__DIR__, 2) . '/builder';
    }

    /**
     * Return the package builder template roots for legacy manager rendering.
     *
     * Legacy manager tabs render `template.blade.php` files directly from this root while new
     * frontend render output goes through namespaced package views.
     *
     * @return array<int, string> Absolute view roots for builder editor templates.
     * @since 2.0.0
     */
    public function builderTemplateRoots(): array
    {
        return Collection::make([$this->builderPath()])
            ->filter(fn (string $root) => is_dir($root))
            ->values()
            ->all();
    }
}
