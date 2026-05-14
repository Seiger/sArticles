<?php namespace Seiger\sArticles\Support;

use Illuminate\Support\Str;
use Seiger\sArticles\Controllers\sArticlesController;

/**
 * LangIntegration package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class LangIntegration
{
    /**
     * Enabled for LangIntegration.
     *
     * This method keeps the enabled responsibility inside LangIntegration, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return bool True when the package condition is met, false otherwise.
     * @since 2.0.0
     */
    public function enabled(): bool
    {
        return (bool) evo()->getConfig('check_sLang', false)
            && class_exists('\Seiger\sLang\Facades\sLang');
    }

    /**
     * Resolve the default language used by article content.
     *
     * When sLang is available, its configured default language becomes the source of truth for
     * multilingual article tabs. Without sLang, the package falls back to the legacy controller
     * language and finally to `base` so existing single-language content remains readable.
     *
     * @return string Default language code used by article forms and content lookups.
     * @since 2.0.0
     */
    public function default(): string
    {
        if ($this->enabled()) {
            $facadeClass = '\Seiger\sLang\Facades\sLang';
            $language = trim((string) $facadeClass::langDefault());

            return $language !== '' ? $language : 'uk';
        }

        $language = trim((string) (new sArticlesController())->langDefault());

        return $language !== '' ? $language : 'base';
    }

    /**
     * Languages for LangIntegration.
     *
     * This method keeps the languages responsibility inside LangIntegration, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function languages(): array
    {
        if (!$this->enabled()) {
            return [$this->default()];
        }

        $facadeClass = '\Seiger\sLang\Facades\sLang';
        $default = $this->default();
        $languages = collect((array) $facadeClass::langConfig())
            ->map(fn ($language) => trim((string) $language))
            ->filter(fn ($language) => $language !== '')
            ->unique()
            ->values();

        if (!$languages->contains($default)) {
            $languages->prepend($default);
        }

        return $languages
            ->sortBy(fn ($language) => $language === $default ? 0 : 1)
            ->values()
            ->all() ?: [$default];
    }

    /**
     * Tab name for LangIntegration.
     *
     * This method keeps the tab name responsibility inside LangIntegration, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    public function tabName(string $language): string
    {
        return 'lang_' . Str::slug($language, '_');
    }

    /**
     * Tab label for LangIntegration.
     *
     * This method keeps the tab label responsibility inside LangIntegration, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    public function tabLabel(string $language): string
    {
        $info = $this->languageInfo($language);
        $short = trim((string) ($info['short'] ?? ''));

        return $short !== '' ? $short : strtoupper($language);
    }

    /**
     * Language title for LangIntegration.
     *
     * This method keeps the language title responsibility inside LangIntegration, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    public function languageTitle(string $language): string
    {
        $info = $this->languageInfo($language);
        $name = trim((string) ($info['name'] ?? ''));

        return $name !== '' ? $name : strtoupper($language);
    }

    /**
     * Language info for LangIntegration.
     *
     * This method keeps the language info responsibility inside LangIntegration, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function languageInfo(string $language): array
    {
        if (!$this->enabled()) {
            return [];
        }

        $facadeClass = '\Seiger\sLang\Facades\sLang';
        $list = (array) $facadeClass::langList();

        return (array) ($list[$language] ?? []);
    }
}
