<?php

namespace Seiger\sArticles\Tables;

use EvolutionCMS\Facades\UrlProcessor;
use EvolutionCMS\Models\SiteContent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticle;
use Seiger\sArticles\Models\sArticlesAuthor;
use Seiger\sArticles\Models\sArticlesCategory;
use Seiger\sArticles\Models\sArticlesFeature;
use Seiger\sArticles\Models\sArticlesPoll;
use Seiger\sArticles\Models\sArticlesTag;
use Seiger\sArticles\Models\sArticleTranslate;
use Seiger\sArticles\Support\LangIntegration;
use Seiger\sArticles\Support\SeoIntegration;

class ArticlesTableData
{
    protected string $moduleUrl;
    protected string $type;

    public function __construct(
        protected array $context = [],
        protected array $state = [],
        protected array $config = [],
    ) {
        $this->moduleUrl = (string) ($context['moduleUrl'] ?? '');
        $this->type = (string) ($context['type'] ?? 'article') ?: 'article';
    }

    public function total(): int
    {
        return (clone $this->articlesQuery())->toBase()->getCountForPagination();
    }

    public function rows(int $page, int $perPage): array
    {
        $articles = $this->articlesQuery()->forPage(max(1, $page), max(1, $perPage))->get();
        $parents = SiteContent::select('id', 'pagetitle')
            ->whereIn('id', $articles->pluck('parent')->unique()->filter()->values()->all())
            ->get()
            ->pluck('pagetitle', 'id')
            ->toArray();

        return $this->articleRows($articles, $parents);
    }

    public function addArticleLabel(): string
    {
        $text = \sArticles::config('types.' . $this->activeType() . '.add_button_text', __('sArticles::global.add_article'));

        return __('global.add') . ' ' . $text;
    }

    public function deleteName(int $articleId): string
    {
        $name = $this->articleTitle($articleId);

        return $name !== '' ? $name : (string) $articleId;
    }

    public function deleteRow(int $articleId): void
    {
        if (!isset($_SESSION['mgrValidated'])) {
            return;
        }

        DB::table('s_articles')->where('id', $articleId)->delete();
        DB::table('s_article_translates')->where('article', $articleId)->delete();
        DB::table('s_article_categories')->where('article', $articleId)->delete();
        DB::table('s_article_features')->where('article', $articleId)->delete();
        DB::table('s_article_tags')->where('article', $articleId)->delete();
        DB::table('s_article_comments')->where('article_id', $articleId)->delete();

        (new sArticlesController())->setArticlesListing();
    }

    public function togglePublished(int $articleId): void
    {
        if (!isset($_SESSION['mgrValidated'])) {
            return;
        }

        $article = sArticle::find($articleId);

        if (!$article) {
            return;
        }

        $article->published = $article->published ? 0 : 1;
        $article->save();
    }

    public function duplicate(int $articleId): void
    {
        if (!isset($_SESSION['mgrValidated'])) {
            return;
        }

        $source = sArticle::withoutGlobalScope('translate')->find($articleId);

        if (!$source) {
            return;
        }

        $copy = $source->replicate();
        $copy->published = 0;
        $copy->views = 0;
        $copy->alias = $this->uniqueArticleAlias((string) $source->alias, 0);
        $copy->created_at = now();
        $copy->updated_at = now();
        $copy->save();

        $this->duplicateArticleTranslations((int) $source->id, (int) $copy->id);
        $copy->categories()->sync($source->categories()->pluck('s_articles_categories.catid')->all());
        $copy->tags()->sync($source->tags()->pluck('s_articles_tags.tagid')->all());
        $copy->features()->sync($source->features()->pluck('s_articles_features.fid')->all());

        (new sArticlesController())->setArticlesListing();
    }

    public function modalDefaults(): array
    {
        $type = $this->activeType();

        $data = [
            'type' => $type,
            'published' => true,
            'published_at' => now()->format('Y-m-d\TH:i'),
            'parent' => (int) evo()->getConfig('site_start', 1),
            'author_id' => (int) sArticlesAuthor::query()->orderBy('base_name')->value('autid'),
            'position' => 0,
            'cover' => '',
            'alias' => '',
            'pagetitle' => '',
            'longtitle' => '',
            'cover_title' => '',
            'introtext' => '',
            'description' => '',
            'content_builder' => [
                [
                    'type' => 'richtext',
                    'data' => ['value' => ''],
                ],
            ],
            'categories' => [],
            'main_tag' => '',
            'tags' => [],
            'features' => [],
            'relevants' => [],
        ];

        if ($this->lang()->enabled()) {
            $translations = collect($this->lang()->languages())
                ->mapWithKeys(fn (string $language) => [$language => $this->translationDefaults()])
                ->all();

            $data['translations'] = $translations;

            if ($this->seo()->enabled()) {
                $data['seo'] = collect(array_keys($translations))
                    ->mapWithKeys(fn (string $language) => [$language => $this->seo()->defaults()])
                    ->all();
            }
        }

        if ($this->seo()->standaloneTabEnabled()) {
            $data['seo'] = $this->seo()->defaults();
        }

        return $data;
    }

    public function modalData(int $articleId): array
    {
        $article = sArticle::withoutGlobalScope('translate')->find($articleId);

        if (!$article) {
            return $this->modalDefaults();
        }

        $tagIds = $article->tags()->pluck('s_articles_tags.tagid')->map(fn ($id) => (int) $id)->values()->all();

        $data = [
            'type' => (string) ($article->type ?: $this->activeType()),
            'published' => (bool) $article->published,
            'published_at' => $article->published_at ? Carbon::parse($article->published_at)->format('Y-m-d\TH:i') : '',
            'parent' => (int) $article->parent,
            'author_id' => (int) $article->author_id,
            'position' => (int) $article->position,
            'cover' => (string) $article->cover,
            'alias' => (string) $article->alias,
            'categories' => $article->categories()->pluck('s_articles_categories.catid')->map(fn ($id) => (string) $id)->values()->all(),
            'main_tag' => $tagIds[0] ?? '',
            'tags' => array_map('strval', $tagIds),
            'features' => $article->features()->pluck('s_articles_features.fid')->map(fn ($id) => (string) $id)->values()->all(),
            'relevants' => collect(data_is_json($article->relevants ?? '', true) ?: [])->map(fn ($id) => (string) $id)->values()->all(),
        ];

        if ($this->lang()->enabled()) {
            $languages = $this->lang()->languages();
            $data['translations'] = collect($languages)
                ->mapWithKeys(fn (string $language) => [$language => $this->translationData($articleId, $language)])
                ->all();

            if ($this->seo()->enabled()) {
                $data['seo'] = collect($languages)
                    ->mapWithKeys(fn (string $language) => [$language => $this->seo()->articleData($articleId, $language)])
                    ->all();
            }

            return $data;
        }

        $content = $this->articleContent($articleId);
        $constructor = data_is_json($content->constructor ?? '', true) ?: [];
        $data = array_merge($data, [
            'pagetitle' => (string) ($content->pagetitle ?? ''),
            'longtitle' => (string) ($content->longtitle ?? ''),
            'cover_title' => (string) ($constructor['cover_title'] ?? ''),
            'introtext' => (string) ($content->introtext ?? ''),
            'description' => (string) ($content->description ?? ''),
            'content_builder' => $this->modalBuilderData((string) ($content->builder ?? ''), (string) ($content->content ?? '')),
        ]);

        if ($this->seo()->standaloneTabEnabled()) {
            $data['seo'] = $this->seo()->articleData($articleId);
        }

        return $data;
    }

    public function modalOptions(array $modal, array $data = [], ?int $articleId = null, string $mode = 'create'): array
    {
        if ($this->lang()->enabled()) {
            $modal['tabs'] = collect($this->lang()->languages())
                ->flatMap(function (string $language) {
                    $label = mb_strtoupper($this->lang()->tabLabel($language));

                    return [
                        [
                            'name' => $this->languageMainTab($language),
                            'label' => $label,
                            'icon' => 'settings',
                            'title' => $this->lang()->languageTitle($language),
                        ],
                        [
                            'name' => $this->languageContentTab($language),
                            'label' => trim($label . ' ' . __('sArticles::global.content')),
                            'icon' => 'file-text',
                            'title' => $this->lang()->languageTitle($language),
                        ],
                    ];
                })
                ->values()
                ->all();

            return $modal;
        }

        if (!$this->seo()->standaloneTabEnabled()) {
            return $modal;
        }

        $tabs = collect((array) ($modal['tabs'] ?? []));

        if (!$tabs->contains(fn ($tab) => is_array($tab) && ($tab['name'] ?? '') === 'seo')) {
            $tabs->push([
                'name' => 'seo',
                'label' => 'sSeo::global.title',
                'icon' => 'chart-line',
            ]);
        }

        $modal['tabs'] = $tabs->values()->all();

        return $modal;
    }

    public function modalTitle(array $data = [], ?int $articleId = null, string $mode = 'create'): string
    {
        $type = trim((string) data_get($data, 'type', ''));

        if ($type === '' && $articleId) {
            $type = trim((string) (sArticle::withoutGlobalScope('translate')->whereKey($articleId)->value('type') ?: ''));
        }

        $type = $type !== '' ? $type : $this->activeType();
        $verb = $mode === 'edit'
            ? __('sArticles::global.edit')
            : __('sArticles::global.add');

        return trim($verb . ' ' . $this->typeLabel($type));
    }

    public function modalHeaderMeta(array $data = [], ?int $articleId = null, string $mode = 'create'): array
    {
        $type = trim((string) data_get($data, 'type', '')) ?: $this->activeType();
        $items = [
            [
                'label' => 'global.resource_type',
                'value' => $this->typeLabel($type),
                'icon' => 'article',
            ],
        ];

        if (!$articleId) {
            return $items;
        }

        $article = sArticle::withoutGlobalScope('translate')->find($articleId);

        if (!$article) {
            return $items;
        }

        return array_merge([
            [
                'label' => 'ID',
                'value' => (string) (int) $article->id,
                'icon' => 'hash',
            ],
        ], $items, [
            [
                'label' => 'sArticles::global.views',
                'value' => (string) (int) $article->views,
                'icon' => 'eye',
            ],
        ]);
    }

    public function modalFields(array $fields, array $data = [], ?int $articleId = null, string $mode = 'create'): array
    {
        $type = (string) data_get($data, 'type', $this->activeType());
        $type = $type !== '' ? $type : $this->activeType();
        $multilingual = $this->lang()->enabled();
        $localizedFields = ['pagetitle', 'longtitle', 'cover_title', 'introtext', 'description', 'content_builder'];

        $fields = collect($fields)
            ->reject(fn ($field) => ($field['name'] ?? '') === 'type')
            ->reject(fn ($field) => $multilingual && in_array((string) ($field['name'] ?? ''), $localizedFields, true))
            ->reject(fn ($field) => ($field['name'] ?? '') === 'author_id' && (int) \sArticles::config('general.authors_on', 1) !== 1)
            ->reject(fn ($field) => ($field['name'] ?? '') === 'categories' && (int) \sArticles::config('general.categories_on', 1) !== 1)
            ->reject(fn ($field) => in_array(($field['name'] ?? ''), ['main_tag', 'tags'], true) && (int) \sArticles::config('general.tags_on', 1) !== 1)
            ->reject(fn ($field) => ($field['name'] ?? '') === 'features' && (int) \sArticles::config('general.features_on', 1) !== 1)
            ->reject(fn ($field) => ($field['name'] ?? '') === 'relevants' && (int) \sArticles::config('general.relevants_on', 1) !== 1)
            ->reject(fn ($field) => ($field['name'] ?? '') === 'published_at' && (int) \sArticles::config('types.' . $type . '.publish_date_on', 1) !== 1)
            ->reject(fn ($field) => ($field['name'] ?? '') === 'longtitle' && (int) \sArticles::config('types.' . $type . '.long_title_on', 1) !== 1)
            ->reject(fn ($field) => ($field['name'] ?? '') === 'cover_title' && (int) \sArticles::config('types.' . $type . '.cover_title_on', 1) !== 1)
            ->reject(fn ($field) => ($field['name'] ?? '') === 'introtext' && (int) \sArticles::config('types.' . $type . '.introtext_on', 1) !== 1)
            ->reject(fn ($field) => ($field['name'] ?? '') === 'description' && (int) \sArticles::config('types.' . $type . '.description_on', 1) !== 1)
            ->map(function (array $field) use ($type, $multilingual) {
                if ($multilingual && ($field['name'] ?? '') === 'alias') {
                    $field['source'] = ['translations.' . $this->lang()->default() . '.pagetitle'];
                }

                if (($field['name'] ?? '') === 'introtext' && (int) \sArticles::config('types.' . $type . '.visual_editor_introtext', 0) === 1) {
                    $field['type'] = 'editor';
                    $field['height'] = '220px';
                }

                if (($field['name'] ?? '') === 'description' && (int) \sArticles::config('types.' . $type . '.visual_editor_description', 0) === 1) {
                    $field['type'] = 'editor';
                    $field['height'] = '340px';
                }

                if (($field['type'] ?? '') === 'editor') {
                    $field = $this->withConfiguredEditor($field);
                }

                return $field;
            })
            ->values()
            ->all();

        if ($multilingual) {
            return $this->multilingualModalFields($fields, $type);
        }

        if ($this->seo()->standaloneTabEnabled()) {
            $fields = array_merge($fields, $this->seoModalFields());
        }

        return $fields;
    }

    public function articleModalOptions(array $field, array $data = [], ?int $articleId = null, string $mode = 'create'): array
    {
        $name = (string) ($field['name'] ?? '');

        if (Str::endsWith($name, '.robots')) {
            return $this->seo()->robotsOptions();
        }

        if (Str::endsWith($name, '.priority')) {
            return $this->seo()->priorityOptions();
        }

        if (Str::endsWith($name, '.changefreq')) {
            return $this->seo()->changeFrequencyOptions();
        }

        if (Str::endsWith($name, '.seorobots')) {
            return [
                ['value' => 'index,follow', 'label' => 'index,follow'],
                ['value' => 'noindex,nofollow', 'label' => 'noindex,nofollow'],
            ];
        }

        return match ($name) {
            'type' => collect($this->availableTypes())
                ->map(fn ($type) => ['value' => $type, 'label' => $this->typeLabel($type)])
                ->values()
                ->all(),
            'parent' => $this->parentOptions(),
            'author_id' => $this->authorOptions(),
            'categories' => $this->taxonomyOptions(sArticlesCategory::query()->orderBy($this->baseColumn())->get(), 'catid'),
            'main_tag' => collect([['value' => '', 'label' => '-']])
                ->merge($this->taxonomyOptions(sArticlesTag::query()->orderBy($this->baseColumn())->get(), 'tagid'))
                ->values()
                ->all(),
            'tags' => $this->taxonomyOptions(sArticlesTag::query()->orderBy($this->baseColumn())->get(), 'tagid'),
            'features' => $this->taxonomyOptions(sArticlesFeature::query()->orderBy($this->baseColumn())->get(), 'fid'),
            'relevants' => $this->articleOptions($articleId),
            'seo.robots' => $this->seo()->robotsOptions(),
            'seo.priority' => $this->seo()->priorityOptions(),
            'seo.changefreq' => $this->seo()->changeFrequencyOptions(),
            default => [],
        };
    }

    protected function languageMainTab(string $language): string
    {
        return $this->lang()->tabName($language) . '_main';
    }

    protected function languageContentTab(string $language): string
    {
        return $this->lang()->tabName($language) . '_content';
    }

    protected function multilingualModalFields(array $commonFields, string $type): array
    {
        $fields = [];
        $common = collect($commonFields)->keyBy(fn ($field) => (string) ($field['name'] ?? ''));

        foreach ($this->lang()->languages() as $language) {
            $mainTab = $this->languageMainTab($language);
            $contentTab = $this->languageContentTab($language);
            $fields = array_merge($fields, $this->languageMainFields($type, $language, $mainTab, $common));

            $fields[] = [
                'name' => 'translations.' . $language . '.content_builder',
                'type' => 'builder',
                'label' => 'sArticles::global.content',
                'tab' => $contentTab,
                'section' => 'content',
                'show_label' => false,
                'span' => 'full',
                'blocks_provider' => 'articleBuilderBlocks',
                'rules' => ['array'],
            ];
        }

        return $fields;
    }

    protected function languageMainFields(string $type, string $language, string $tab, Collection $common): array
    {
        $fields = [];
        $defaultLanguage = $this->lang()->default();
        $prefix = 'translations.' . $language . '.';
        $requiredTitle = $language === $defaultLanguage ? ['required', 'string', 'max:100'] : ['nullable', 'string', 'max:100'];

        $fields[] = [
            'name' => $prefix . 'pagetitle',
            'type' => 'text',
            'label' => 'global.resource_title',
            'help' => 'global.resource_title_help',
            'tab' => $tab,
            'section' => 'main',
            'live' => $language === $defaultLanguage,
            'rules' => $requiredTitle,
        ];

        if ((int) \sArticles::config('types.' . $type . '.long_title_on', 1) === 1) {
            $fields[] = [
                'name' => $prefix . 'longtitle',
                'type' => 'text',
                'label' => 'global.long_title',
                'help' => 'global.resource_long_title_help',
                'tab' => $tab,
                'section' => 'main',
                'rules' => ['nullable', 'string', 'max:255'],
            ];
        }

        if ((int) \sArticles::config('types.' . $type . '.introtext_on', 1) === 1) {
            $fields[] = [
                'name' => $prefix . 'introtext',
                'type' => (int) \sArticles::config('types.' . $type . '.visual_editor_introtext', 0) === 1 ? 'editor' : 'textarea',
                'label' => 'global.resource_summary',
                'help' => 'global.resource_summary_help',
                'tab' => $tab,
                'section' => 'main',
                'rows' => 4,
                'height' => '220px',
                'rules' => ['nullable', 'string'],
            ];
            $fields[array_key_last($fields)] = $this->withConfiguredEditor($fields[array_key_last($fields)]);
        }

        if ($common->has('alias')) {
            $fields[] = $this->languageCommonField((array) $common->get('alias'), $language, $tab, 'main');
        }

        if ((int) \sArticles::config('types.' . $type . '.description_on', 1) === 1) {
            $fields[] = [
                'name' => $prefix . 'description',
                'type' => (int) \sArticles::config('types.' . $type . '.visual_editor_description', 0) === 1 ? 'editor' : 'textarea',
                'label' => 'global.description',
                'help' => 'global.resource_description_help',
                'tab' => $tab,
                'section' => 'main',
                'rows' => 5,
                'height' => '340px',
                'rules' => ['nullable', 'string'],
            ];
            $fields[array_key_last($fields)] = $this->withConfiguredEditor($fields[array_key_last($fields)]);
        }

        if ($common->has('cover')) {
            $fields[] = $this->languageCommonField((array) $common->get('cover'), $language, $tab, 'main');
        }

        if ((int) \sArticles::config('types.' . $type . '.cover_title_on', 1) === 1) {
            $fields[] = [
                'name' => $prefix . 'cover_title',
                'type' => 'text',
                'label' => 'sArticles::global.cover_title',
                'help' => 'sArticles::global.cover_title_help',
                'tab' => $tab,
                'section' => 'main',
                'rules' => ['nullable', 'string', 'max:255'],
            ];
        }

        foreach (['published', 'published_at', 'parent', 'author_id', 'position', 'categories', 'main_tag', 'tags', 'features', 'relevants'] as $name) {
            if ($common->has($name)) {
                $fields[] = $this->languageCommonField((array) $common->get($name), $language, $tab, 'relations');
            }
        }

        $fields = array_merge($fields, $this->languageSeoFields($language, $tab));

        return $fields;
    }

    protected function languageCommonField(array $field, string $language, string $tab, string $section): array
    {
        $field['tab'] = $tab;
        $field['section'] = $section;
        $field['id_suffix'] = $language;

        if (($field['name'] ?? '') === 'alias') {
            $field['source'] = ['translations.' . $this->lang()->default() . '.pagetitle'];
        }

        return $field;
    }

    protected function languageSeoFields(string $language, string $tab): array
    {
        if (!$this->seo()->enabled()) {
            return [
                [
                    'name' => 'translations.' . $language . '.seotitle',
                    'type' => 'text',
                    'label' => 'sArticles::global.seotitle',
                    'help' => 'sArticles::global.seotitle_help',
                    'tab' => $tab,
                    'section' => 'relations',
                    'rules' => ['nullable', 'string', 'max:100'],
                ],
                [
                    'name' => 'translations.' . $language . '.seodescription',
                    'type' => 'textarea',
                    'label' => 'sArticles::global.seodescription',
                    'help' => 'sArticles::global.seodescription_help',
                    'tab' => $tab,
                    'section' => 'relations',
                    'rows' => 3,
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                [
                    'name' => 'translations.' . $language . '.seorobots',
                    'type' => 'select',
                    'label' => 'sArticles::global.seorobots',
                    'help' => 'sArticles::global.seorobots_help',
                    'tab' => $tab,
                    'section' => 'relations',
                    'options_provider' => 'articleModalOptions',
                    'rules' => ['nullable', 'string'],
                ],
            ];
        }

        return $this->seoModalFields('seo.' . $language . '.', $tab, 'relations');
    }

    protected function seoModalFields(string $prefix = 'seo.', string $tab = 'seo', string $section = ''): array
    {
        return [
            [
                'name' => $prefix . 'robots',
                'type' => 'select',
                'label' => 'sSeo::global.robots',
                'help' => 'sSeo::global.robots_help',
                'tab' => $tab,
                'section' => $section,
                'span' => 'full',
                'options_provider' => 'articleModalOptions',
                'rules' => ['nullable', 'string'],
            ],
            [
                'name' => $prefix . 'meta_title',
                'type' => 'text',
                'label' => 'sSeo::global.meta_title',
                'help' => 'sSeo::global.meta_title_help',
                'tab' => $tab,
                'section' => $section,
                'span' => 'full',
                'rules' => ['nullable', 'string', 'max:255'],
            ],
            [
                'name' => $prefix . 'meta_description',
                'type' => 'textarea',
                'label' => 'sSeo::global.meta_description',
                'help' => 'sSeo::global.meta_description_help',
                'tab' => $tab,
                'section' => $section,
                'span' => 'full',
                'rows' => 3,
                'rules' => ['nullable', 'string'],
            ],
            [
                'name' => $prefix . 'meta_keywords',
                'type' => 'text',
                'label' => 'sSeo::global.meta_keywords',
                'help' => 'sSeo::global.meta_keywords_help',
                'tab' => $tab,
                'section' => $section,
                'span' => 'full',
                'rules' => ['nullable', 'string'],
            ],
            [
                'name' => $prefix . 'canonical_url',
                'type' => 'text',
                'label' => 'sSeo::global.canonical',
                'help' => 'sSeo::global.canonical_help',
                'tab' => $tab,
                'section' => $section,
                'span' => 'full',
                'rules' => ['nullable', 'string', 'max:255'],
            ],
            [
                'name' => $prefix . 'exclude_from_sitemap',
                'type' => 'checkbox',
                'label' => 'sSeo::global.exclude_from_sitemap',
                'help' => 'sSeo::global.exclude_from_sitemap_help',
                'tab' => $tab,
                'section' => $section,
                'rules' => ['boolean'],
            ],
            [
                'name' => $prefix . 'priority',
                'type' => 'select',
                'label' => 'sSeo::global.priority',
                'help' => 'sSeo::global.priority_help',
                'tab' => $tab,
                'section' => $section,
                'options_provider' => 'articleModalOptions',
                'rules' => ['nullable', 'string'],
            ],
            [
                'name' => $prefix . 'changefreq',
                'type' => 'select',
                'label' => 'sSeo::global.change_frequency',
                'help' => 'sSeo::global.change_frequency_help',
                'tab' => $tab,
                'section' => $section,
                'options_provider' => 'articleModalOptions',
                'rules' => ['nullable', 'string'],
            ],
        ];
    }

    public function articleBuilderBlocks(array $field, array $data = [], ?int $articleId = null, string $mode = 'create'): array
    {
        $configured = collect($this->builderConfigs())
            ->filter(fn ($config) => (int) ($config['active'] ?? 0) === 1)
            ->sortBy(fn ($config) => [(int) ($config['order'] ?? 99), (string) ($config['title'] ?? '')])
            ->values();

        return $configured
            ->map(function (array $config) use ($articleId) {
                $type = (string) ($config['id'] ?? '');
                $definition = $this->builderBlockDefinition($type, $articleId);

                return array_merge([
                    'type' => $type,
                    'label' => (string) ($config['title'] ?? $type),
                    'icon' => 'file-text',
                    'defaults' => [],
                    'fields' => [],
                ], $definition);
            })
            ->filter(fn ($block) => $block['type'] !== '' && count((array) ($block['fields'] ?? [])))
            ->values()
            ->all();
    }

    public function saveModal(array $data, ?int $articleId = null, string $mode = 'create'): int
    {
        if (!isset($_SESSION['mgrValidated'])) {
            return (int) ($articleId ?? 0);
        }

        $controller = new sArticlesController();
        $article = $articleId ? sArticle::withoutGlobalScope('translate')->find($articleId) : null;
        $article = $article ?: new sArticle();
        $title = $this->modalPrimaryTitle($data);
        $type = trim((string) data_get($data, 'type', $this->activeType())) ?: $this->activeType();
        $publishedAt = $this->modalDateTime((string) data_get($data, 'published_at', ''));
        $alias = trim((string) data_get($data, 'alias', ''));
        $votes = data_is_json($article->votes ?? '', true);

        if (!$votes) {
            $votes = ['total' => 1, '1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 1];
        }

        $article->published = data_get($data, 'published') ? 1 : 0;
        $article->parent = max(0, (int) data_get($data, 'parent', 0));
        $article->author_id = max(0, (int) data_get($data, 'author_id', 0));
        $article->position = max(0, (int) data_get($data, 'position', 0));
        $article->cover = trim((string) data_get($data, 'cover', ''));
        $article->type = $type;
        $article->published_at = $publishedAt ?: now()->toDateTimeString();
        $article->relevants = json_encode($this->integerIds((array) data_get($data, 'relevants', [])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $article->votes = json_encode($votes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $article->alias = $controller->validateAlias($alias !== '' ? $alias : ($title !== '' ? $title : 'article'), (int) ($article->id ?? 0));
        $article->save();

        $article->categories()->sync($this->integerIds((array) data_get($data, 'categories', [])));
        $article->features()->sync($this->integerIds((array) data_get($data, 'features', [])));
        $article->tags()->sync($this->normalizedTagIds($data));

        if ($this->lang()->enabled()) {
            foreach ($this->lang()->languages() as $language) {
                $content = $this->saveTranslationContent($article, $language, (array) data_get($data, 'translations.' . $language, []));

                evo()->invokeEvent('sArticlesAfterContentSave', compact('article', 'content'));
            }

            $this->seo()->saveArticleTranslations($article, (array) data_get($data, 'seo', []), $this->lang()->languages());
            $controller->setArticlesListing();

            return (int) $article->id;
        }

        $content = $this->articleContent((int) $article->id);
        $constructor = data_is_json($content->constructor ?? '', true) ?: [];
        $constructor['cover_title'] = (string) data_get($data, 'cover_title', '');
        $builder = $this->builderDataForStorage((array) data_get($data, 'content_builder', []));

        $content->article = (int) $article->id;
        $content->lang = (string) ($content->lang ?: $this->contentLanguage());
        $content->pagetitle = $title;
        $content->longtitle = (string) data_get($data, 'longtitle', '');
        $content->introtext = (string) data_get($data, 'introtext', '');
        $content->description = (string) data_get($data, 'description', '');
        $content->content = $this->renderBuilderContent($builder);
        $content->builder = json_encode($builder, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $content->constructor = json_encode($constructor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $content->save();

        $this->seo()->saveArticle($article, $content, (array) data_get($data, 'seo', []));

        evo()->invokeEvent('sArticlesAfterContentSave', compact('article', 'content'));
        $controller->setArticlesListing();

        return (int) $article->id;
    }

    public function filters(array $filters): array
    {
        $filters = collect($filters)
            ->reject(fn ($filter) => ($filter['state'] ?? null) === 'type')
            ->values();

        if (!$this->usesTypeFilter()) {
            return $filters->all();
        }

        return $filters
            ->prepend([
                'state' => 'type',
                'type' => 'select',
                'icon' => 'article',
                'label' => 'global.resource_type',
                'search_label' => 'global.resource_type',
                'searchable' => false,
                'clearable' => false,
                'default' => $this->activeType(),
            ])
            ->values()
            ->all();
    }

    public function filterGroups(): array
    {
        $type = $this->activeType();
        $articleIds = sArticle::query()
            ->select('s_articles.id')
            ->whereType($type)
            ->pluck('s_articles.id')
            ->filter()
            ->unique()
            ->values();
        $parentIds = sArticle::query()
            ->select('s_articles.parent')
            ->whereType($type)
            ->distinct()
            ->pluck('s_articles.parent')
            ->filter()
            ->unique()
            ->values();
        $categoryIds = DB::table('s_article_categories')->whereIn('article', $articleIds)->distinct()->pluck('category')->map(fn ($id) => (int) $id)->filter()->values();
        $tagIds = DB::table('s_article_tags')->whereIn('article', $articleIds)->distinct()->pluck('tag')->map(fn ($id) => (int) $id)->filter()->values();
        $featureIds = DB::table('s_article_features')->whereIn('article', $articleIds)->distinct()->pluck('feature')->map(fn ($id) => (int) $id)->filter()->values();
        $parents = SiteContent::select('id', 'pagetitle')
            ->whereIn('id', $parentIds->filter(fn ($parent) => (int) $parent > 1)->all())
            ->orderBy('pagetitle')
            ->get()
            ->pluck('pagetitle', 'id')
            ->toArray();

        $categories = sArticlesCategory::whereIn('catid', $categoryIds)->orderBy($this->baseColumn())->get();
        $tags = sArticlesTag::whereIn('tagid', $tagIds)->orderBy($this->baseColumn())->get();
        $features = sArticlesFeature::whereIn('fid', $featureIds)->orderBy($this->baseColumn())->get();

        $groups = [
            [
                'key' => 'section',
                'items' => collect($parentIds->contains(fn ($parent) => (int) $parent <= 1) ? [['id' => 1, 'label' => evo()->getConfig('site_name')]] : [])
                    ->merge(collect($parents)->map(fn ($title, $id) => ['id' => (int) $id, 'label' => $title]))
                    ->values()
                    ->all(),
            ],
            [
                'key' => 'tag',
                'items' => $tags->map(fn ($tag) => ['id' => (int) $tag->tagid, 'label' => $this->taxonomyLabel($tag)])->filter(fn ($item) => $item['label'] !== '')->values()->all(),
            ],
            [
                'key' => 'category',
                'items' => $categories->map(fn ($category) => ['id' => (int) $category->catid, 'label' => $this->taxonomyLabel($category)])->filter(fn ($item) => $item['label'] !== '')->values()->all(),
            ],
            [
                'key' => 'feature',
                'items' => $features->map(fn ($feature) => ['id' => (int) $feature->fid, 'label' => $this->taxonomyLabel($feature)])->filter(fn ($item) => $item['label'] !== '')->values()->all(),
            ],
        ];

        if ($this->usesTypeFilter()) {
            array_unshift($groups, [
                'key' => 'type',
                'items' => collect($this->availableTypes())
                    ->map(fn ($type) => ['id' => $type, 'label' => $this->typeLabel($type)])
                    ->values()
                    ->all(),
            ]);
        }

        return $groups;
    }

    protected function seo(): SeoIntegration
    {
        return app(SeoIntegration::class);
    }

    protected function lang(): LangIntegration
    {
        return app(LangIntegration::class);
    }

    protected function articlesQuery(): Builder
    {
        $query = sArticle::query()
            ->with(['categories', 'tags', 'features'])
            ->whereType($this->activeType());

        $this->applySearch($query);
        $this->applyAvailability($query);
        $this->applyTaxonomyFilters($query);
        $this->applyPublishedDateFilter($query);

        if (!$this->applySort($query)) {
            $query->orderBy('s_articles.published_at', 'desc');
        }

        return $query->orderBy('s_articles.id', 'desc');
    }

    protected function articleContent(int $articleId): sArticleTranslate
    {
        $language = $this->contentLanguage();
        $content = sArticleTranslate::whereArticle($articleId)->whereLang($language)->first();

        if (!$content && $language !== 'base') {
            $content = sArticleTranslate::whereArticle($articleId)->whereLang('base')->first();
        }

        if (!$content) {
            $content = new sArticleTranslate();
            $content->article = $articleId;
            $content->lang = $language;
            $content->builder = '[]';
            $content->constructor = '{}';
        }

        return $content;
    }

    protected function articleTranslation(int $articleId, string $language, bool $allowDefaultFallback = false): sArticleTranslate
    {
        $language = trim($language) !== '' ? trim($language) : $this->lang()->default();
        $content = sArticleTranslate::whereArticle($articleId)->whereLang($language)->first();

        if (!$content && $allowDefaultFallback && $language === $this->lang()->default() && $language !== 'base') {
            $content = sArticleTranslate::whereArticle($articleId)->whereLang('base')->first();
        }

        if (!$content) {
            $content = new sArticleTranslate();
            $content->article = $articleId;
            $content->lang = $language;
            $content->builder = '[]';
            $content->constructor = '{}';
        }

        return $content;
    }

    protected function translationDefaults(): array
    {
        return [
            'pagetitle' => '',
            'longtitle' => '',
            'cover_title' => '',
            'introtext' => '',
            'description' => '',
            'seotitle' => '',
            'seodescription' => '',
            'seorobots' => 'index,follow',
            'content_builder' => [
                [
                    'type' => 'richtext',
                    'data' => ['value' => ''],
                ],
            ],
        ];
    }

    protected function translationData(int $articleId, string $language): array
    {
        $content = $this->articleTranslation($articleId, $language, true);
        $constructor = data_is_json($content->constructor ?? '', true) ?: [];

        return array_replace($this->translationDefaults(), [
            'pagetitle' => (string) ($content->pagetitle ?? ''),
            'longtitle' => (string) ($content->longtitle ?? ''),
            'cover_title' => (string) ($constructor['cover_title'] ?? ''),
            'introtext' => (string) ($content->introtext ?? ''),
            'description' => (string) ($content->description ?? ''),
            'seotitle' => (string) ($content->seotitle ?? ''),
            'seodescription' => (string) ($content->seodescription ?? ''),
            'seorobots' => (string) ($content->seorobots ?? 'index,follow'),
            'content_builder' => $this->modalBuilderData((string) ($content->builder ?? ''), (string) ($content->content ?? '')),
        ]);
    }

    protected function saveTranslationContent(sArticle $article, string $language, array $data): sArticleTranslate
    {
        $content = $this->articleTranslation((int) $article->id, $language);
        $constructor = data_is_json($content->constructor ?? '', true) ?: [];
        $constructor['cover_title'] = (string) data_get($data, 'cover_title', '');
        $builder = $this->builderDataForStorage((array) data_get($data, 'content_builder', []));

        $content->article = (int) $article->id;
        $content->lang = $language;
        $content->pagetitle = trim((string) data_get($data, 'pagetitle', ''));
        $content->longtitle = (string) data_get($data, 'longtitle', '');
        $content->introtext = (string) data_get($data, 'introtext', '');
        $content->description = (string) data_get($data, 'description', '');
        $content->content = $this->renderBuilderContent($builder);
        $content->builder = json_encode($builder, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $content->constructor = json_encode($constructor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!$this->seo()->enabled()) {
            $content->seotitle = trim((string) data_get($data, 'seotitle', ''));
            $content->seodescription = trim((string) data_get($data, 'seodescription', ''));
            $content->seorobots = in_array((string) data_get($data, 'seorobots', 'index,follow'), ['index,follow', 'noindex,nofollow'], true)
                ? (string) data_get($data, 'seorobots', 'index,follow')
                : 'index,follow';
        }

        $content->save();

        return $content;
    }

    protected function modalPrimaryTitle(array $data): string
    {
        if ($this->lang()->enabled()) {
            $title = trim((string) data_get($data, 'translations.' . $this->lang()->default() . '.pagetitle', ''));

            if ($title !== '') {
                return $title;
            }
        }

        return trim((string) data_get($data, 'pagetitle', ''));
    }

    protected function contentLanguage(): string
    {
        $language = (new sArticlesController())->langDefault();

        return trim($language) !== '' ? $language : 'base';
    }

    protected function modalDateTime(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : '';
    }

    protected function normalizedTagIds(array $data): array
    {
        return collect([data_get($data, 'main_tag')])
            ->merge((array) data_get($data, 'tags', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function integerIds(array $items): array
    {
        return collect($items)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function parentOptions(): array
    {
        $items = collect([
            ['value' => '0', 'label' => evo()->getConfig('site_name')],
        ]);

        return $items
            ->merge(
                SiteContent::withTrashed()
                    ->select('id', 'pagetitle')
                    ->orderBy('pagetitle')
                    ->get()
                    ->map(fn ($resource) => [
                        'value' => (string) $resource->id,
                        'label' => trim((string) $resource->pagetitle) !== '' ? (string) $resource->pagetitle : ('#' . $resource->id),
                    ])
            )
            ->values()
            ->all();
    }

    protected function authorOptions(): array
    {
        return collect([['value' => '0', 'label' => '-']])
            ->merge(
                sArticlesAuthor::query()
                    ->orderBy('base_name')
                    ->get()
                    ->map(fn ($author) => [
                        'value' => (string) $author->autid,
                        'label' => trim((string) ($author->base_name . ' ' . $author->base_lastname)) ?: ('#' . $author->autid),
                    ])
            )
            ->values()
            ->all();
    }

    protected function taxonomyOptions(Collection $items, string $key): array
    {
        return $items
            ->map(fn ($item) => [
                'value' => (string) data_get($item, $key),
                'label' => $this->taxonomyLabel($item),
            ])
            ->filter(fn ($item) => $item['value'] !== '' && $item['label'] !== '')
            ->values()
            ->all();
    }

    protected function articleOptions(?int $excludeId = null): array
    {
        return sArticle::query()
            ->select('s_articles.id', 'sat.pagetitle')
            ->when($excludeId, fn ($query) => $query->where('s_articles.id', '<>', $excludeId))
            ->orderBy('sat.pagetitle')
            ->get()
            ->map(fn ($article) => [
                'value' => (string) $article->id,
                'label' => trim((string) $article->pagetitle) ?: ('#' . $article->id),
            ])
            ->values()
            ->all();
    }

    protected function articleRows(Collection $articles, array $parents): array
    {
        return $articles
            ->map(function (sArticle $article) use ($parents) {
                $title = $article->pagetitle ?: __('sArticles::global.no_text');

                return [
                    'id' => (int) $article->id,
                    'wire_key' => 'article-row-' . (int) $article->id,
                    'delete_name' => $title,
                    'published' => (bool) $article->published,
                    'cover' => [
                        'src' => $article->coverSrc,
                        'alt' => $title,
                    ],
                    'title' => [
                        'label' => $title,
                        'href' => $article->link,
                        'target' => '_blank',
                    ],
                    'section' => [
                        'label' => $article->parent > 1
                            ? ($parents[$article->parent] ?? $article->parent)
                            : evo()->getConfig('site_name'),
                        'href' => $this->resourceUrl($article->parent > 1 ? (int) $article->parent : 1),
                        'target' => '_blank',
                    ],
                    'categories' => $this->taxonomyLabels($article->categories),
                    'tags' => $this->taxonomyLabels($article->tags),
                    'features' => $this->taxonomyLabels($article->features),
                    'published_at' => $this->formatDate($article->published_at),
                    'views' => (int) $article->views,
                ];
            })
            ->values()
            ->all();
    }

    protected function modalBuilderData(string $builderJson, string $content): array
    {
        $builder = data_is_json($builderJson, true);

        if (!is_array($builder) || !count($builder)) {
            return [
                [
                    'type' => 'richtext',
                    'data' => ['value' => $content],
                ],
            ];
        }

        return collect($builder)
            ->filter(fn ($item) => is_array($item) && count($item))
            ->map(function (array $item) {
                $type = (string) array_key_first($item);
                $value = $item[$type] ?? '';

                return [
                    'type' => $type,
                    'data' => $this->modalBuilderBlockData($type, $value),
                ];
            })
            ->values()
            ->all();
    }

    protected function modalBuilderBlockData(string $type, mixed $value): array
    {
        if (in_array($type, ['richtext', 'note', 'framevideo'], true)) {
            return ['value' => (string) $value];
        }

        if ($type === 'slider') {
            return ['slides' => $this->sliderBuilderItems(is_array($value) ? $value : [])];
        }

        if ($type === 'accordion') {
            return ['items' => $this->accordionBuilderItems(is_array($value) ? $value : [])];
        }

        return is_array($value) ? $value : [];
    }

    protected function builderDataForStorage(array $blocks): array
    {
        return collect($blocks)
            ->filter(fn ($block) => is_array($block) && trim((string) ($block['type'] ?? '')) !== '')
            ->map(function (array $block) {
                $type = trim((string) ($block['type'] ?? ''));
                $data = (array) ($block['data'] ?? []);

                if (in_array($type, ['richtext', 'note', 'framevideo'], true)) {
                    return [$type => (string) ($data['value'] ?? '')];
                }

                if ($type === 'slider') {
                    $slides = (array) ($data['slides'] ?? []);

                    if (!count($slides) && isset($data['json'])) {
                        return [$type => data_is_json((string) $data['json'], true) ?: []];
                    }

                    return [$type => [
                        'src' => collect($slides)->map(fn ($item) => (string) data_get($item, 'src', ''))->values()->all(),
                        'alt' => collect($slides)->map(fn ($item) => (string) data_get($item, 'alt', ''))->values()->all(),
                    ]];
                }

                if ($type === 'accordion') {
                    $items = (array) ($data['items'] ?? []);

                    if (!count($items) && isset($data['json'])) {
                        return [$type => data_is_json((string) $data['json'], true) ?: []];
                    }

                    return [$type => [
                        'title' => collect($items)->map(fn ($item) => (string) data_get($item, 'title', ''))->values()->all(),
                        'icon' => collect($items)->map(fn ($item) => (string) data_get($item, 'icon', ''))->values()->all(),
                        'richtext' => collect($items)->map(fn ($item) => (string) data_get($item, 'richtext', ''))->values()->all(),
                    ]];
                }

                return [$type => $data];
            })
            ->values()
            ->all();
    }

    protected function sliderBuilderItems(array $value): array
    {
        $sources = array_values((array) ($value['src'] ?? []));
        $alts = array_values((array) ($value['alt'] ?? []));
        $count = max(count($sources), count($alts), 1);

        return collect(range(0, $count - 1))
            ->map(fn ($index) => [
                'src' => (string) ($sources[$index] ?? ''),
                'alt' => (string) ($alts[$index] ?? ''),
            ])
            ->values()
            ->all();
    }

    protected function accordionBuilderItems(array $value): array
    {
        $titles = array_values((array) ($value['title'] ?? []));
        $icons = array_values((array) ($value['icon'] ?? []));
        $texts = array_values((array) ($value['richtext'] ?? []));
        $count = max(count($titles), count($icons), count($texts), 1);

        return collect(range(0, $count - 1))
            ->map(fn ($index) => [
                'title' => (string) ($titles[$index] ?? ''),
                'icon' => (string) ($icons[$index] ?? ''),
                'richtext' => (string) ($texts[$index] ?? ''),
            ])
            ->values()
            ->all();
    }

    protected function renderBuilderContent(array $builder): string
    {
        $renders = collect($this->builderConfigs())
            ->filter(fn ($config) => is_file((string) ($config['render_path'] ?? '')))
            ->mapWithKeys(fn ($config) => [(string) ($config['id'] ?? '') => (string) ($config['template'] ?? '')])
            ->filter(fn ($template, $id) => $id !== '' && $template !== '')
            ->all();

        if (!count($renders)) {
            return '';
        }

        $previousPaths = method_exists(View::getFinder(), 'getPaths') ? View::getFinder()->getPaths() : [];
        View::getFinder()->setPaths($this->builderViewRoots());

        try {
            $content = collect($builder)
                ->map(function (array $item, int $position) use ($renders) {
                    $id = (string) array_key_first($item);

                    if ($id === '' || !isset($renders[$id])) {
                        return '';
                    }

                    $value = $item[$id] ?? '';

                    return view($renders[$id] . '.render', compact('id', 'value', 'position'))->render();
                })
                ->implode('');
        } finally {
            if (count($previousPaths)) {
                View::getFinder()->setPaths($previousPaths);
            }
        }

        return str_replace([chr(9), chr(10), chr(13), '  '], '', $content);
    }

    protected function builderConfigs(): array
    {
        $configs = [];

        if (!class_exists('sArticles') && class_exists(\Seiger\sArticles\Facades\sArticles::class)) {
            class_alias(\Seiger\sArticles\Facades\sArticles::class, 'sArticles');
        }

        foreach ($this->builderViewRoots() as $root) {
            foreach (glob(rtrim($root, '/') . '/*/config.php') ?: [] as $path) {
                $template = basename(dirname($path));
                $config = require $path;

                if (!is_array($config)) {
                    continue;
                }

                $id = (string) ($config['id'] ?? $template);

                if ($id === '' || isset($configs[$id])) {
                    continue;
                }

                $configs[$id] = array_merge($config, [
                    'id' => $id,
                    'template' => $template,
                    'render_path' => dirname($path) . '/render.blade.php',
                ]);
            }
        }

        return array_values($configs);
    }

    protected function builderViewRoots(): array
    {
        $roots = [];

        if (defined('EVO_BASE_PATH')) {
            $roots[] = rtrim(EVO_BASE_PATH, '/') . '/assets/modules/sarticles/builder';
        }

        $roots[] = dirname(__DIR__, 2) . '/builder';

        return collect($roots)
            ->filter(fn ($root) => is_dir($root))
            ->unique()
            ->values()
            ->all();
    }

    protected function builderBlockDefinition(string $type, ?int $articleId = null): array
    {
        $definition = match ($type) {
            'richtext' => [
                'label' => 'RichText',
                'icon' => 'file-text',
                'defaults' => ['value' => ''],
                'fields' => [
                    ['name' => 'value', 'type' => 'editor', 'label' => 'sArticles::global.content', 'span' => 'full', 'rows' => 10, 'height' => '420px', 'rules' => ['nullable', 'string']],
                ],
            ],
            'singleimg' => [
                'label' => 'SingleImg',
                'icon' => 'image',
                'defaults' => ['src' => '', 'title' => '', 'alt' => '', 'link' => ''],
                'fields' => [
                    ['name' => 'src', 'type' => 'image', 'label' => 'sArticles::global.image', 'span' => 'full', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'title', 'type' => 'text', 'label' => 'sArticles::global.cover_title', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'alt', 'type' => 'text', 'label' => 'Alt', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'link', 'type' => 'text', 'label' => 'Link', 'rules' => ['nullable', 'string', 'max:255']],
                ],
            ],
            'imgandtext' => [
                'label' => 'Зображення та текст',
                'icon' => 'photo-edit',
                'defaults' => ['align' => 'left', 'src' => '', 'title' => '', 'alt' => '', 'link' => '', 'text' => ''],
                'fields' => [
                    ['name' => 'align', 'type' => 'select', 'label' => 'Align', 'options' => [['value' => 'left', 'label' => 'Зображення зліва'], ['value' => 'right', 'label' => 'Зображення справа']]],
                    ['name' => 'src', 'type' => 'image', 'label' => 'sArticles::global.image', 'span' => 'full', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'title', 'type' => 'text', 'label' => 'sArticles::global.cover_title', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'alt', 'type' => 'text', 'label' => 'Alt', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'link', 'type' => 'text', 'label' => 'Link', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'text', 'type' => 'editor', 'label' => 'sArticles::global.content', 'span' => 'full', 'rows' => 8, 'height' => '360px', 'rules' => ['nullable', 'string']],
                ],
            ],
            'framevideo' => [
                'label' => 'YouTube',
                'icon' => 'brand-youtube',
                'defaults' => ['value' => ''],
                'fields' => [
                    ['name' => 'value', 'type' => 'text', 'label' => 'URL', 'span' => 'full', 'rules' => ['nullable', 'string', 'max:255']],
                ],
            ],
            'quote' => [
                'label' => 'Quote',
                'icon' => 'blockquote',
                'defaults' => ['src' => '', 'author' => '', 'text' => ''],
                'fields' => [
                    ['name' => 'src', 'type' => 'image', 'label' => 'sArticles::global.image', 'span' => 'full', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'author', 'type' => 'text', 'label' => 'Author', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'text', 'type' => 'textarea', 'label' => 'Quote', 'span' => 'full', 'rows' => 5, 'rules' => ['nullable', 'string']],
                ],
            ],
            'note' => [
                'label' => 'Note',
                'icon' => 'note',
                'defaults' => ['value' => ''],
                'fields' => [
                    ['name' => 'value', 'type' => 'textarea', 'label' => 'Note', 'span' => 'full', 'rows' => 4, 'rules' => ['nullable', 'string']],
                ],
            ],
            'previewarticle' => [
                'label' => 'ArticlePreview',
                'icon' => 'article',
                'defaults' => ['id' => ''],
                'fields' => [
                    ['name' => 'id', 'type' => 'select', 'label' => 'sArticles::global.article', 'span' => 'full', 'options' => $this->articleOptions($articleId), 'rules' => ['nullable', 'integer']],
                ],
            ],
            'poll' => [
                'label' => 'Poll',
                'icon' => 'chart-bar',
                'defaults' => ['id' => ''],
                'fields' => [
                    ['name' => 'id', 'type' => 'select', 'label' => 'sArticles::global.polls', 'span' => 'full', 'options' => $this->pollOptions(), 'rules' => ['nullable', 'integer']],
                ],
            ],
            'singlefile' => [
                'label' => 'Файл',
                'icon' => 'file',
                'defaults' => ['icon' => '', 'file' => '', 'title' => ''],
                'fields' => [
                    ['name' => 'icon', 'type' => 'image', 'label' => 'Іконка', 'span' => 'full', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'file', 'type' => 'file', 'label' => 'Файл', 'span' => 'full', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'title', 'type' => 'text', 'label' => 'Назва', 'span' => 'full', 'rules' => ['nullable', 'string', 'max:255']],
                ],
            ],
            'slider' => [
                'label' => 'Slider',
                'icon' => 'slideshow',
                'defaults' => ['slides' => [['src' => '', 'alt' => '']]],
                'fields' => [
                    [
                        'name' => 'slides',
                        'type' => 'items',
                        'label' => 'Зображення',
                        'item_label' => 'Слайд',
                        'add_label' => 'Додати слайд',
                        'span' => 'full',
                        'rules' => ['array'],
                        'defaults' => ['src' => '', 'alt' => ''],
                        'fields' => [
                            ['name' => 'src', 'type' => 'image', 'label' => 'sArticles::global.image', 'span' => 'full', 'rules' => ['nullable', 'string', 'max:255']],
                            ['name' => 'alt', 'type' => 'text', 'label' => 'Alt', 'span' => 'full', 'rules' => ['nullable', 'string', 'max:255']],
                        ],
                    ],
                ],
            ],
            'accordion' => [
                'label' => 'Accordion',
                'icon' => 'layout-list',
                'defaults' => ['items' => [['title' => '', 'icon' => '', 'richtext' => '']]],
                'fields' => [
                    [
                        'name' => 'items',
                        'type' => 'items',
                        'label' => 'Список текстів',
                        'item_label' => 'Панель',
                        'add_label' => 'Додати текст',
                        'span' => 'full',
                        'rules' => ['array'],
                        'defaults' => ['title' => '', 'icon' => '', 'richtext' => ''],
                        'fields' => [
                            ['name' => 'title', 'type' => 'text', 'label' => 'Заголовок', 'rules' => ['nullable', 'string', 'max:255']],
                            ['name' => 'icon', 'type' => 'image', 'label' => 'Іконка', 'rules' => ['nullable', 'string', 'max:255']],
                            ['name' => 'richtext', 'type' => 'editor', 'label' => 'sArticles::global.content', 'span' => 'full', 'rows' => 4, 'height' => '180px', 'rules' => ['nullable', 'string']],
                        ],
                    ],
                ],
            ],
            default => [],
        };

        return $this->withConfiguredEditorFields($definition);
    }

    protected function withConfiguredEditorFields(array $definition): array
    {
        if (empty($definition['fields']) || !is_array($definition['fields'])) {
            return $definition;
        }

        $definition['fields'] = collect($definition['fields'])
            ->map(function (array $field) {
                if (($field['type'] ?? '') === 'items' && !empty($field['fields']) && is_array($field['fields'])) {
                    $field['fields'] = collect($field['fields'])
                        ->map(fn (array $nestedField) => $this->withConfiguredEditor($nestedField))
                        ->all();

                    return $field;
                }

                return $this->withConfiguredEditor($field);
            })
            ->all();

        return $definition;
    }

    protected function withConfiguredEditor(array $field): array
    {
        if (($field['type'] ?? '') !== 'editor') {
            return $field;
        }

        $field['editor'] = $this->configuredEditor();
        $field['editor_switcher'] = false;

        return $field;
    }

    protected function configuredEditor(): string
    {
        $editor = trim((string) \sArticles::config('general.editor', 'system'));

        if ($editor === '' || $editor === 'system') {
            return (string) evo()->getConfig('which_editor', 'eTinyMCE');
        }

        return $editor;
    }

    protected function pollOptions(): array
    {
        $locale = app()->getLocale();
        $fallback = (string) evo()->getConfig('s_lang_default', 'base');

        return collect([['value' => '', 'label' => '-']])
            ->merge(
                sArticlesPoll::query()
                    ->orderBy('pollid')
                    ->get()
                    ->map(function (sArticlesPoll $poll) use ($locale, $fallback) {
                        $question = $poll->question;
                        $label = is_array($question)
                            ? (string) ($question[$locale] ?? $question[$fallback] ?? $question['base'] ?? reset($question))
                            : (string) $question;

                        return [
                            'value' => (string) $poll->pollid,
                            'label' => trim($label) !== '' ? $label : ('#' . $poll->pollid),
                        ];
                    })
            )
            ->values()
            ->all();
    }

    protected function articleTitle(int $articleId): string
    {
        return trim((string) DB::table('s_article_translates')
            ->where('article', $articleId)
            ->orderByRaw(
                'CASE lang WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END',
                [app()->getLocale(), 'base']
            )
            ->value('pagetitle'));
    }

    protected function applySearch(Builder $query): void
    {
        $words = Str::of((string) $this->state('search', ''))
            ->stripTags()
            ->replaceMatches('/[^\p{L}\p{N}\@\.!#$%&\'*+-\/=?^_`{|}~]/iu', ' ')
            ->replaceMatches('/(\s){2,}/', '$1')
            ->trim()
            ->explode(' ')
            ->map(fn ($word) => mb_strtolower($word))
            ->filter(fn ($word) => mb_strlen($word) > 0)
            ->values();

        if (!$words->count()) {
            return;
        }

        $fields = collect(['sat.pagetitle', 'sat.longtitle', 'sat.introtext', 'sat.content']);
        $select = collect([0]);
        $bindings = [];
        $exact = '%' . addcslashes(mb_strtolower($words->implode(' ')), '\\%_') . '%';

        $fields->each(function ($field) use ($query, $select, $exact, &$bindings) {
            $select->push('(CASE WHEN ' . $this->likeSql($query, $field) . ' THEN 10 ELSE 0 END)');
            $bindings[] = $exact;
        });

        $words->each(function ($word) use ($fields, $query, $select, &$bindings) {
            $like = '%' . addcslashes($word, '\\%_') . '%';
            $fields->each(function ($field) use ($query, $select, $like, &$bindings) {
                $select->push('(CASE WHEN ' . $this->likeSql($query, $field) . ' THEN 1 ELSE 0 END)');
                $bindings[] = $like;
            });
        });

        $query
            ->selectRaw('(' . $select->implode(' + ') . ') as points', $bindings)
            ->where(function ($where) use ($words, $fields, $query) {
                $words->each(function ($word) use ($fields, $where, $query) {
                    $like = '%' . addcslashes($word, '\\%_') . '%';
                    $fields->each(function ($field) use ($where, $query, $like) {
                        $where->orWhereRaw($this->likeSql($query, $field), [$like]);
                    });
                });
            })
            ->orderByDesc('points');
    }

    protected function likeSql(Builder $query, string $field): string
    {
        $sql = 'LOWER(' . $query->getGrammar()->wrap($field) . ') LIKE ?';

        return DB::connection()->getDriverName() === 'sqlite' ? $sql : $sql . " ESCAPE '\\\\'";
    }

    protected function applyAvailability(Builder $query): void
    {
        $availability = (string) $this->filterState('availability', 'all');

        if ($availability === 'published') {
            $query->where('s_articles.published', 1);
        } elseif ($availability === 'unpublished') {
            $query->where('s_articles.published', 0);
        }
    }

    protected function applyTaxonomyFilters(Builder $query): void
    {
        $sections = $this->filterIds('section');
        $categories = $this->filterIds('category');
        $tags = $this->filterIds('tag');
        $features = $this->filterIds('feature');

        if (count($sections)) {
            $query->where(function ($sectionsQuery) use ($sections) {
                if (in_array(1, $sections, true)) {
                    $sectionsQuery->orWhere('s_articles.parent', '<=', 1);
                }

                $parentIds = array_values(array_filter($sections, fn ($id) => $id > 1));
                if (count($parentIds)) {
                    $sectionsQuery->orWhereIn('s_articles.parent', $parentIds);
                }
            });
        }

        if (count($categories)) {
            $query->whereHas('categories', fn ($categoriesQuery) => $categoriesQuery->whereIn('s_articles_categories.catid', $categories));
        }

        if (count($tags)) {
            $query->whereHas('tags', fn ($tagsQuery) => $tagsQuery->whereIn('s_articles_tags.tagid', $tags));
        }

        if (count($features)) {
            $query->whereHas('features', fn ($featuresQuery) => $featuresQuery->whereIn('s_articles_features.fid', $features));
        }
    }

    protected function applyPublishedDateFilter(Builder $query): void
    {
        $value = (array) $this->filterState('published_at', []);
        $from = $this->normalizeFilterDate((string) ($value['from'] ?? ''));
        $to = $this->normalizeFilterDate((string) ($value['to'] ?? ''));

        if ($from !== '') {
            $query->whereDate('s_articles.published_at', '>=', $from);
        }

        if ($to !== '') {
            $query->whereDate('s_articles.published_at', '<=', $to);
        }
    }

    protected function applySort(Builder $query): bool
    {
        $key = (string) $this->state('sort', '');

        if ($key === '') {
            return false;
        }

        $column = collect($this->config['columns'] ?? [])
            ->first(fn ($column) => ($column['key'] ?? null) === $key && ($column['sortable'] ?? false));

        if (!is_array($column)) {
            return false;
        }

        $field = (string) ($column['sort_field'] ?? $column['value'] ?? $column['key'] ?? '');

        if ($field === '') {
            return false;
        }

        $direction = $this->state('direction') === 'desc' ? 'desc' : 'asc';

        if ($field === 'sat.pagetitle') {
            $query->orderByRaw('LOWER(' . $query->getGrammar()->wrap($field) . ') ' . $direction);

            return true;
        }

        $query->orderBy($field, $direction);

        return true;
    }

    protected function formatDate(mixed $value): string
    {
        if (!$value) {
            return '-';
        }

        return Carbon::parse($value)->format('d.m.Y H:i');
    }

    protected function normalizeFilterDate(string $value): string
    {
        $value = trim($value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    protected function filterIds(string $key): array
    {
        return collect((array) $this->filterState($key, []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function activeType(): string
    {
        $types = $this->availableTypes();
        $selected = (string) $this->filterState('type', $this->type);

        if ($selected !== '' && in_array($selected, $types, true)) {
            return $selected;
        }

        if (in_array($this->type, $types, true)) {
            return $this->type;
        }

        return $types[0] ?? 'article';
    }

    protected function availableTypes(): array
    {
        $types = \sArticles::config('types', []);

        if (!is_array($types) || $types === []) {
            return ['article'];
        }

        return collect(array_keys($types))
            ->map(fn ($type) => trim((string) $type))
            ->filter(fn ($type) => $type !== '')
            ->unique()
            ->values()
            ->all() ?: ['article'];
    }

    protected function usesTypeFilter(): bool
    {
        return (int) \sArticles::config('general.filter_types_on', 1) === 1
            && count($this->availableTypes()) > 1;
    }

    protected function typeLabel(string $type): string
    {
        return (string) \sArticles::config(
            'types.' . $type . '.list',
            \sArticles::config('types.' . $type . '.name', $type)
        );
    }

    protected function duplicateArticleTranslations(int $sourceId, int $copyId): void
    {
        DB::table('s_article_translates')
            ->where('article', $sourceId)
            ->orderBy('tid')
            ->get()
            ->each(function ($translation) use ($copyId) {
                $data = (array) $translation;
                unset($data['tid']);

                $data['article'] = $copyId;
                $data['pagetitle'] = trim((string) ($data['pagetitle'] ?? '')) . ' ' . __('global.duplicate');
                $data['created_at'] = now();
                $data['updated_at'] = now();

                DB::table('s_article_translates')->insert($data);
            });
    }

    protected function uniqueArticleAlias(string $alias, int $articleId): string
    {
        $base = trim($alias) !== '' ? $alias : 'article';
        $suffix = '-copy';
        $candidate = $base . $suffix;
        $index = 1;

        while (sArticle::withoutGlobalScope('translate')
            ->where('id', '<>', $articleId)
            ->where('alias', $candidate)
            ->exists()) {
            $candidate = $base . $suffix . '-' . $index;
            $index++;
        }

        return $candidate;
    }

    protected function resourceUrl(int $id): string
    {
        return UrlProcessor::makeUrl($id > 1 ? $id : (int) evo()->getConfig('site_start', 1));
    }

    protected function taxonomyLabels(Collection $items): array
    {
        return $items
            ->map(fn ($item) => $this->taxonomyLabel($item))
            ->filter()
            ->values()
            ->all();
    }

    protected function taxonomyLabel(object $item): string
    {
        $column = $this->baseColumn();

        return trim((string) ($item->{$column} ?? '')) ?: (trim((string) ($item->base ?? '')) ?: trim((string) ($item->alias ?? '')));
    }

    protected function baseColumn(): string
    {
        return (new sArticlesController())->langDefault();
    }

    protected function state(string $key, mixed $default = null): mixed
    {
        return $this->state[$key] ?? $default;
    }

    protected function filterState(string $key, mixed $default = null): mixed
    {
        return data_get($this->state, 'filters.' . $key, $default);
    }
}
