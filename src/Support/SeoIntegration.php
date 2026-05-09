<?php namespace Seiger\sArticles\Support;

use Seiger\sArticles\Models\sArticle;
use Seiger\sArticles\Models\sArticleTranslate;

/**
 * SeoIntegration package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class SeoIntegration
{
    protected const RESOURCE_TYPE = 'article';
    protected const DOMAIN_KEY = 'default';
    protected const BASE_LANG = 'base';

    /**
     * Enabled for SeoIntegration.
     *
     * This method keeps the enabled responsibility inside SeoIntegration, so callers can rely on
     * a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return bool True when the package condition is met, false otherwise.
     * @since 2.0.0
     */
    public function enabled(): bool
    {
        return (bool) evo()->getConfig('check_sSeo', false)
            && class_exists('\Seiger\sSeo\Facades\sSeo')
            && class_exists('\Seiger\sSeo\Models\sSeoModel');
    }

    /**
     * Multilingual for SeoIntegration.
     *
     * This method keeps the multilingual responsibility inside SeoIntegration, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return bool True when the package condition is met, false otherwise.
     * @since 2.0.0
     */
    public function multilingual(): bool
    {
        return (bool) evo()->getConfig('check_sLang', false);
    }

    /**
     * Standalone tab enabled for SeoIntegration.
     *
     * This method keeps the standalone tab enabled responsibility inside SeoIntegration, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return bool True when the package condition is met, false otherwise.
     * @since 2.0.0
     */
    public function standaloneTabEnabled(): bool
    {
        return $this->enabled() && !$this->multilingual();
    }

    /**
     * Defaults for SeoIntegration.
     *
     * This method keeps the defaults responsibility inside SeoIntegration, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function defaults(): array
    {
        return [
            'robots' => '',
            'meta_title' => '',
            'meta_description' => '',
            'meta_keywords' => '',
            'canonical_url' => '',
            'exclude_from_sitemap' => false,
            'priority' => '0.5',
            'changefreq' => 'weekly',
        ];
    }

    /**
     * Article data for SeoIntegration.
     *
     * This method keeps the article data responsibility inside SeoIntegration, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function articleData(int $articleId, string $lang = self::BASE_LANG): array
    {
        if (!$this->enabled() || $articleId <= 0) {
            return $this->defaults();
        }

        $modelClass = '\Seiger\sSeo\Models\sSeoModel';
        $row = $modelClass::query()
            ->where('resource_id', $articleId)
            ->where('resource_type', self::RESOURCE_TYPE)
            ->where('domain_key', self::DOMAIN_KEY)
            ->where('lang', $lang)
            ->first();

        if (!$row) {
            return $this->defaults();
        }

        return array_replace($this->defaults(), [
            'robots' => (string) ($row->robots ?? ''),
            'meta_title' => (string) ($row->meta_title ?? ''),
            'meta_description' => (string) ($row->meta_description ?? ''),
            'meta_keywords' => (string) ($row->meta_keywords ?? ''),
            'canonical_url' => (string) ($row->canonical_url ?? ''),
            'exclude_from_sitemap' => (bool) ($row->exclude_from_sitemap ?? false),
            'priority' => number_format((float) ($row->priority ?? 0.5), 1, '.', ''),
            'changefreq' => (string) ($row->changefreq ?? 'weekly'),
        ]);
    }

    /**
     * Persist article data.
     *
     * This method keeps the save article responsibility inside SeoIntegration, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function saveArticle(sArticle $article, sArticleTranslate $content, array $seo): void
    {
        if (!$this->standaloneTabEnabled() || !(int) $article->id) {
            return;
        }

        $lang = self::BASE_LANG;
        $payload = [
            'resource_id' => (int) $article->id,
            'resource_type' => self::RESOURCE_TYPE,
            'domain_key' => self::DOMAIN_KEY,
            $lang => $this->payloadFields($seo),
        ];
        $payload[$lang]['domain_key'] = self::DOMAIN_KEY;

        $facadeClass = '\Seiger\sSeo\Facades\sSeo';
        $facadeClass::updateSeoFields($payload);
    }

    /**
     * Persist article translations data.
     *
     * This method keeps the save article translations responsibility inside SeoIntegration, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function saveArticleTranslations(sArticle $article, array $seoByLanguage, array $languages): void
    {
        if (!$this->enabled() || !$this->multilingual() || !(int) $article->id) {
            return;
        }

        $payload = [
            'resource_id' => (int) $article->id,
            'resource_type' => self::RESOURCE_TYPE,
            'domain_key' => self::DOMAIN_KEY,
        ];

        foreach ($languages as $language) {
            $language = trim((string) $language);

            if ($language === '') {
                continue;
            }

            $payload[$language] = $this->payloadFields((array) data_get($seoByLanguage, $language, []));
            $payload[$language]['domain_key'] = self::DOMAIN_KEY;
        }

        $facadeClass = '\Seiger\sSeo\Facades\sSeo';
        $facadeClass::updateSeoFields($payload);
    }

    /**
     * Robots options for SeoIntegration.
     *
     * This method keeps the robots options responsibility inside SeoIntegration, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function robotsOptions(): array
    {
        return [
            ['value' => '', 'label' => '-'],
            ['value' => 'index,follow', 'label' => 'index,follow'],
            ['value' => 'index,nofollow', 'label' => 'index,nofollow'],
            ['value' => 'noindex,nofollow', 'label' => 'noindex,nofollow'],
        ];
    }

    /**
     * Priority options for SeoIntegration.
     *
     * This method keeps the priority options responsibility inside SeoIntegration, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function priorityOptions(): array
    {
        return collect(range(10, 1))
            ->map(fn (int $value) => [
                'value' => number_format($value / 10, 1, '.', ''),
                'label' => number_format($value / 10, 1, '.', ''),
            ])
            ->all();
    }

    /**
     * Change frequency options for SeoIntegration.
     *
     * This method keeps the change frequency options responsibility inside SeoIntegration, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function changeFrequencyOptions(): array
    {
        return collect(['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])
            ->map(fn (string $value) => ['value' => $value, 'label' => $value])
            ->all();
    }

    /**
     * Payload fields for SeoIntegration.
     *
     * This method keeps the payload fields responsibility inside SeoIntegration, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function payloadFields(array $seo): array
    {
        return [
            'robots' => $this->allowedValue((string) data_get($seo, 'robots', ''), array_column($this->robotsOptions(), 'value'), ''),
            'meta_title' => trim((string) data_get($seo, 'meta_title', '')),
            'meta_description' => trim((string) data_get($seo, 'meta_description', '')),
            'meta_keywords' => trim((string) data_get($seo, 'meta_keywords', '')),
            'canonical_url' => trim((string) data_get($seo, 'canonical_url', '')),
            'exclude_from_sitemap' => (bool) data_get($seo, 'exclude_from_sitemap', false),
            'priority' => $this->allowedValue((string) data_get($seo, 'priority', '0.5'), array_column($this->priorityOptions(), 'value'), '0.5'),
            'changefreq' => $this->allowedValue((string) data_get($seo, 'changefreq', 'weekly'), array_column($this->changeFrequencyOptions(), 'value'), 'weekly'),
        ];
    }

    /**
     * Allowed value for SeoIntegration.
     *
     * This method keeps the allowed value responsibility inside SeoIntegration, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function allowedValue(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
