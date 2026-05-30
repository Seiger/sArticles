<?php namespace Seiger\sArticles\Tables;

use EvolutionCMS\Facades\UrlProcessor;
use EvolutionCMS\Models\SiteContent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticle;
use Seiger\sArticles\Models\sArticlesAuthor;
use Seiger\sArticles\Models\sArticlesCategory;
use Seiger\sArticles\Models\sArticlesFeature;
use Seiger\sArticles\Models\sArticlesPoll;
use Seiger\sArticles\Models\sArticlesTag;
use Seiger\sArticles\Models\sArticleTranslate;
use Seiger\sArticles\Support\BuilderRenderer;
use Seiger\sArticles\Support\LangIntegration;
use Seiger\sArticles\Support\LikeSearch;

/**
 * Articles manager table data provider.
 *
 * Builds row payloads, filter definitions, modal schemas, and persistence bridges used by
 * the sArticles evo-ui manager. The class keeps UI-specific data shaping away from Eloquent
 * models while preserving compatibility with legacy article content rows.
 */
class ArticlesTableData
{
    protected string $moduleUrl;
    protected string $type;

    /**
     * Initialize the article table data provider.
     *
     * Stores module URL, active type, table state, and resolved configuration so later row, modal,
     * and query methods can operate from a consistent manager context.
     *
     * @param array<string, mixed> $context Runtime context passed by the manager module or evo-ui table.
     * @param array<string, mixed> $state Current evo-ui table state, including filters, sorting, and pagination context.
     * @param array<string, mixed> $config Resolved table or form configuration for the current manager section.
     * @since 2.0.0
     */
    public function __construct(
        protected array $context = [],
        protected array $state = [],
        protected array $config = [],
    ) {
        $this->moduleUrl = (string) ($context['moduleUrl'] ?? '');
        $this->type = (string) ($context['type'] ?? 'article') ?: 'article';
    }

    /**
     * Count rows for the current article table state.
     *
     * The count uses the same filtered query as row loading, which keeps evo-ui pagination aligned
     * with search terms, taxonomy filters, publication state, and selected article type.
     *
     * @return int Integer identifier or count used by the manager workflow.
     * @since 2.0.0
     */
    public function total(): int
    {
        return (clone $this->articlesQuery())->toBase()->getCountForPagination();
    }

    /**
     * Build one page of article rows for evo-ui.
     *
     * Loads the requested page, resolves parent resource labels, and returns normalized row arrays
     * so the manager table does not depend on raw Eloquent model details.
     *
     * @param int $page One-based page number requested by evo-ui.
     * @param int $perPage Number of rows requested for the current page.
     * @return array<int, array<string, mixed>> Row payload consumed by the manager table.
     * @since 2.0.0
     */
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

    /**
     * Resolve the create button label for the active article type.
     *
     * Type-specific text is read from package configuration and combined with the global add label,
     * letting custom content types use more natural manager wording.
     *
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    public function addArticleLabel(): string
    {
        $text = \sArticles::config('types.' . $this->activeType() . '.add_button_text', __('sArticles::global.add_article'));

        return __('global.add') . ' ' . $text;
    }

    /**
     * Resolve the delete confirmation label for an article.
     *
     * A translated title is preferred because destructive confirmations should show a human-readable
     * name. The numeric ID remains a safe fallback for incomplete records.
     *
     * @param int $articleId Internal article identifier.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    public function deleteName(int $articleId): string
    {
        $name = $this->articleTitle($articleId);

        return $name !== '' ? $name : (string) $articleId;
    }

    /**
     * Delete an article and its package-owned relations.
     *
     * The method is guarded by the manager session and removes translation, taxonomy, feature, tag,
     * and comment rows before refreshing the generated article listing.
     *
     * @param int $articleId Internal article identifier.
     * @return void No value is returned; the relevant query, model, or storage state is updated in place.
     * @since 2.0.0
     */
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

    /**
     * Toggle publication state from a manager row action.
     *
     * Only validated manager sessions may flip the state. Missing article IDs are ignored so stale
     * table actions do not trigger exceptions.
     *
     * @param int $articleId Internal article identifier.
     * @return void No value is returned; the relevant query, model, or storage state is updated in place.
     * @since 2.0.0
     */
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

    /**
     * Create an unpublished copy of an article.
     *
     * The copied record keeps relations and translations but resets mutable values such as
     * publication state, views, timestamps, and alias so editors can safely prepare a new article.
     *
     * @param int $articleId Internal article identifier.
     * @return void No value is returned; the relevant query, model, or storage state is updated in place.
     * @since 2.0.0
     */
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

    /**
     * Build default data for the article create modal.
     *
     * The payload includes base article fields, relation placeholders, a starter builder block, and
     * optional language or SEO integration defaults in the same shape used by edit mode.
     *
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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
        }

        $data = array_merge($data, $this->managerEventPayload('sArticlesManagerModalDefaultsEvent', [
            'data' => $data,
            'languages' => $this->lang()->enabled() ? $this->lang()->languages() : [],
        ]));

        return $data;
    }

    /**
     * Load existing article data for the edit modal.
     *
     * Stored article fields, relations, translations, SEO data, and builder content are normalized
     * into the modal payload so create and edit flows can share the same evo-ui schema.
     *
     * @param int $articleId Internal article identifier.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

            $data = array_merge($data, $this->managerEventPayload('sArticlesManagerModalDataEvent', [
                'article' => $article,
                'articleId' => $articleId,
                'data' => $data,
                'languages' => $languages,
            ]));

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

        $data = array_merge($data, $this->managerEventPayload('sArticlesManagerModalDataEvent', [
            'article' => $article,
            'articleId' => $articleId,
            'data' => $data,
            'languages' => [],
        ]));

        return $data;
    }

    /**
     * Adjust modal tabs and option metadata for integrations.
     *
     * When language integration is enabled, the modal receives language-specific main and content
     * tabs. Otherwise a standalone SEO tab is appended when the sSeo integration requires it.
     *
     * @param array $modal Modal configuration being adjusted before rendering.
     * @param array $data Submitted or hydrated modal payload.
     * @param ?int $articleId Internal article identifier.
     * @param string $mode Current modal mode, usually create or edit.
     * @return array<int, array<string, mixed>> Option payload consumed by evo-ui controls.
     * @since 2.0.0
     */
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

        $tabs = collect((array) ($modal['tabs'] ?? []));
        foreach ($this->managerEventList('sArticlesManagerModalTabsEvent', [
            'modal' => $modal,
            'data' => $data,
            'articleId' => $articleId,
            'mode' => $mode,
            'multilingual' => $this->lang()->enabled(),
            'languages' => $this->lang()->enabled() ? $this->lang()->languages() : [],
        ]) as $tab) {
            if (is_array($tab) && !$tabs->contains(fn ($item) => is_array($item) && ($item['name'] ?? '') === ($tab['name'] ?? null))) {
                $tabs->push($tab);
            }
        }

        $modal['tabs'] = $tabs->values()->all();

        return $modal;
    }

    /**
     * Resolve the title displayed by an article modal.
     *
     * The title combines the create/edit verb with the active content type label, using submitted
     * data or the stored article type when available.
     *
     * @param array $data Submitted or hydrated modal payload.
     * @param ?int $articleId Internal article identifier.
     * @param string $mode Current modal mode, usually create or edit.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
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

    /**
     * Build read-only metadata for the article modal header.
     *
     * Header metadata gives editors quick context such as record ID, article type, and view count
     * without adding those values as editable form fields.
     *
     * @param array $data Submitted or hydrated modal payload.
     * @param ?int $articleId Internal article identifier.
     * @param string $mode Current modal mode, usually create or edit.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Filter and enrich article modal field definitions.
     *
     * The schema is adapted to package settings, active type configuration, language mode, visual
     * editor choices, and standalone SEO integration before evo-ui renders it.
     *
     * @param array $fields Field definitions being prepared for evo-ui.
     * @param array $data Submitted or hydrated modal payload.
     * @param ?int $articleId Internal article identifier.
     * @param string $mode Current modal mode, usually create or edit.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

        $fields = array_merge($fields, $this->managerEventList('sArticlesManagerModalFieldsEvent', [
            'fields' => $fields,
            'data' => $data,
            'articleId' => $articleId,
            'mode' => $mode,
            'type' => $type,
            'multilingual' => false,
            'languages' => [],
        ]));

        return $fields;
    }

    /**
     * Build option lists for article modal controls.
     *
     * The method routes field names to parent resources, authors, taxonomies, related articles, SEO
     * enum values, or type choices so field definitions can stay declarative.
     *
     * @param array $field Single field definition or column descriptor.
     * @param array $data Submitted or hydrated modal payload.
     * @param ?int $articleId Internal article identifier.
     * @param string $mode Current modal mode, usually create or edit.
     * @return array<int, array<string, mixed>> Option payload consumed by evo-ui controls.
     * @since 2.0.0
     */
    public function articleModalOptions(array $field, array $data = [], ?int $articleId = null, string $mode = 'create'): array
    {
        $name = (string) ($field['name'] ?? '');

        foreach ($this->managerEventList('sArticlesManagerModalOptionsEvent', [
            'field' => $field,
            'data' => $data,
            'articleId' => $articleId,
            'mode' => $mode,
        ]) as $options) {
            if (is_array($options) && $options !== []) {
                return $options;
            }
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
            default => [],
        };
    }

    /**
     * Build the main tab key for a language.
     *
     * Language-specific tab names keep multilingual fields grouped consistently while avoiding
     * collisions with the shared article relation fields.
     *
     * @param string $language Language code being rendered or persisted.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    protected function languageMainTab(string $language): string
    {
        return $this->lang()->tabName($language) . '_main';
    }

    /**
     * Build the content tab key for a language.
     *
     * The separate content tab gives the visual builder enough space while still keeping it tied to
     * the language currently being edited.
     *
     * @param string $language Language code being rendered or persisted.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    protected function languageContentTab(string $language): string
    {
        return $this->lang()->tabName($language) . '_content';
    }

    /**
     * Build modal fields for multilingual editing.
     *
     * For each configured language the method creates main fields, content builder fields, shared
     * relation fields, and SEO fields in the tab layout expected by evo-ui.
     *
     * @param array $commonFields Shared field definitions available to every language tab.
     * @param string $type Article type or builder block identifier.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Build the main editable fields for one language.
     *
     * The field list combines localized article text, shared article fields, optional package
     * settings, and language-aware validation for default and secondary languages.
     *
     * @param string $type Article type or builder block identifier.
     * @param string $language Language code being rendered or persisted.
     * @param string $tab Target evo-ui tab key.
     * @param Collection $common Shared field definitions keyed by field name.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Clone a shared field into a language tab.
     *
     * Shared fields keep their original configuration while receiving language-specific tab,
     * section, suffix, and alias source metadata needed by evo-ui.
     *
     * @param array $field Single field definition or column descriptor.
     * @param string $language Language code being rendered or persisted.
     * @param string $tab Target evo-ui tab key.
     * @param string $section Target evo-ui section key.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Build SEO fields for one language tab.
     *
     * When sSeo is unavailable, legacy SEO fields are rendered directly from article translations.
     * When sSeo is enabled, the package delegates to the integration field schema.
     *
     * @param string $language Language code being rendered or persisted.
     * @param string $tab Target evo-ui tab key.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
    protected function languageSeoFields(string $language, string $tab): array
    {
        if (!$this->managerEventList('sArticlesManagerModalFieldsEvent', [
            'language' => $language,
            'tab' => $tab,
            'multilingual' => true,
            'languages' => $this->lang()->languages(),
        ])) {
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

        return $this->managerEventList('sArticlesManagerModalFieldsEvent', [
            'language' => $language,
            'tab' => $tab,
            'section' => 'relations',
            'prefix' => 'seo.' . $language . '.',
            'multilingual' => true,
            'languages' => $this->lang()->languages(),
        ]);
    }

    /**
     * Build available content builder block definitions.
     *
     * Active builder configs are discovered, sorted, expanded into evo-ui block definitions, and
     * filtered so the modal only exposes usable blocks with fields.
     *
     * @param array $field Single field definition or column descriptor.
     * @param array $data Submitted or hydrated modal payload.
     * @param ?int $articleId Internal article identifier.
     * @param string $mode Current modal mode, usually create or edit.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Persist article modal data from evo-ui.
     *
     * The method normalizes article fields, relations, translated content, SEO payloads, and builder
     * output before refreshing the generated listing used on the frontend.
     *
     * @param array $data Submitted or hydrated modal payload.
     * @param ?int $articleId Internal article identifier.
     * @param string $mode Current modal mode, usually create or edit.
     * @return int Integer identifier or count used by the manager workflow.
     * @since 2.0.0
     */
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

                evo()->invokeEvent('sArticlesAfterContentSave', compact('article', 'content', 'data'));
            }

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

        evo()->invokeEvent('sArticlesAfterContentSave', compact('article', 'content', 'data'));
        $controller->setArticlesListing();

        return (int) $article->id;
    }

    /**
     * Prepare table filter definitions for articles.
     *
     * The configured filters are adapted to the active package settings, including an optional
     * article type selector when multiple content types are available.
     *
     * @param array $filters Filters value used by this manager flow.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Build grouped filter options for the article table.
     *
     * Groups are derived from articles of the active type and expose only sections, categories,
     * tags, and features that are actually present in the current content set.
     *
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Resolve the language integration service.
     *
     * The language service owns package multilingual rules, available languages, labels, and tab
     * names used throughout modal construction and persistence.
     *
     * @return LangIntegration Language integration service resolved from the container.
     * @since 2.0.0
     */
    protected function lang(): LangIntegration
    {
        return app(LangIntegration::class);
    }

    /**
     * Collect flat arrays returned by manager integration listeners.
     *
     * sArticles owns only the extension point. Packages such as sSeo own their field schemas,
     * defaults, options, and save payload interpretation through these events.
     *
     * @param string $event Evolution event name without the `evolution.` prefix.
     * @param array<string, mixed> $params Event payload.
     * @return array<int, mixed> Flattened event items.
     * @since 2.0.0
     */
    protected function managerEventList(string $event, array $params = []): array
    {
        $items = [];

        if (!is_array($events = evo()->invokeEvent($event, $params))) {
            return $items;
        }

        foreach ($events as $eventResult) {
            if (!is_array($eventResult)) {
                continue;
            }

            if (array_is_list($eventResult)) {
                foreach ($eventResult as $item) {
                    $items[] = $item;
                }
            } else {
                $items[] = $eventResult;
            }
        }

        return $items;
    }

    /**
     * Merge associative arrays returned by manager integration listeners.
     *
     * @param string $event Evolution event name without the `evolution.` prefix.
     * @param array<string, mixed> $params Event payload.
     * @return array<string, mixed> Merged associative payload.
     * @since 2.0.0
     */
    protected function managerEventPayload(string $event, array $params = []): array
    {
        $payload = [];

        foreach ($this->managerEventList($event, $params) as $item) {
            if (is_array($item)) {
                $payload = array_replace_recursive($payload, $item);
            }
        }

        return $payload;
    }

    /**
     * Determine whether an integration listener supplied any data for a hook.
     *
     * @param string $event Evolution event name without the `evolution.` prefix.
     * @param array<string, mixed> $params Event payload.
     * @return bool True when at least one listener returned a non-empty array.
     * @since 2.0.0
     */
    protected function hasManagerEventOutput(string $event, array $params = []): bool
    {
        return $this->managerEventList($event, $params) !== [];
    }

    /**
     * Build the article listing query for the current table state.
     *
     * The query is the single source for row loading and pagination counts. It applies active type,
     * search, availability, taxonomy, publication date, and sorting rules before the table consumes
     * it.
     *
     * @return Builder<sArticle> Filtered article query ready for pagination or counting.
     * @since 2.0.0
     */
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

    /**
     * Resolve editable content for a single-language article flow.
     *
     * Existing content is loaded for the current content language with a base fallback for legacy
     * records. If no row exists, an unsaved translation model is prepared with empty builder
     * metadata.
     *
     * @param int $articleId Internal article identifier.
     * @return sArticleTranslate Translation content model used by article editing flows.
     * @since 2.0.0
     */
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

    /**
     * Resolve content for a specific article language.
     *
     * Multilingual modals need a stable content object for every configured language. This helper
     * can reuse legacy base content for the default language or prepare a new translation row.
     *
     * @param int $articleId Internal article identifier.
     * @param string $language Language code being rendered or persisted.
     * @param bool $allowDefaultFallback Whether the default language may reuse legacy base content.
     * @return sArticleTranslate Translation content model used by article editing flows.
     * @since 2.0.0
     */
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

    /**
     * Build default values for a language tab.
     *
     * Defaults mirror the evo-ui field shape, including legacy SEO fields and a starter rich-text
     * builder block, so every language tab begins with a complete payload.
     *
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Convert stored translation content into modal data.
     *
     * Persisted values are overlaid onto translation defaults so older records missing optional
     * constructor or builder fields still hydrate cleanly in the new interface.
     *
     * @param int $articleId Internal article identifier.
     * @param string $language Language code being rendered or persisted.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Persist localized article content from the multilingual modal.
     *
     * The method stores localized text, cover title metadata, and builder output for one language,
     * and keeps legacy SEO fields in sync when the sSeo integration is disabled.
     *
     * @param sArticle $article Article model that owns the content being saved.
     * @param string $language Language code being rendered or persisted.
     * @param array $data Submitted or hydrated modal payload.
     * @return sArticleTranslate Translation content model used by article editing flows.
     * @since 2.0.0
     */
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

        if (!$this->hasManagerEventOutput('sArticlesManagerModalFieldsEvent', [
            'multilingual' => true,
            'languages' => $this->lang()->languages(),
        ])) {
            $content->seotitle = trim((string) data_get($data, 'seotitle', ''));
            $content->seodescription = trim((string) data_get($data, 'seodescription', ''));
            $content->seorobots = in_array((string) data_get($data, 'seorobots', 'index,follow'), ['index,follow', 'noindex,nofollow'], true)
                ? (string) data_get($data, 'seorobots', 'index,follow')
                : 'index,follow';
        }

        $content->save();

        return $content;
    }

    /**
     * Resolve the canonical title used while saving an article.
     *
     * Multilingual saves prefer the default language title because it drives aliases and listing
     * labels. Single-language saves use the flat pagetitle field.
     *
     * @param array $data Submitted or hydrated modal payload.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
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

    /**
     * Resolve the legacy content language for non-multilingual articles.
     *
     * The package keeps compatibility with installations that store content under a configured
     * language key and falls back to base when no default can be resolved.
     *
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    protected function contentLanguage(): string
    {
        $language = (new sArticlesController())->langDefault();

        return trim($language) !== '' ? $language : 'base';
    }

    /**
     * Normalize an HTML datetime-local value for storage.
     *
     * Empty or invalid input returns an empty string so the caller can decide whether to keep an
     * existing value or apply the current timestamp as fallback.
     *
     * @param string $value Raw value that needs package-specific normalization.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    protected function modalDateTime(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : '';
    }

    /**
     * Merge main and secondary tags into one relation list.
     *
     * The modal exposes the primary tag separately for editorial convenience, but the relation table
     * expects one deduplicated list of positive identifiers.
     *
     * @param array $data Submitted or hydrated modal payload.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Normalize submitted relation identifiers.
     *
     * Select controls may submit strings, numbers, or empty placeholders. This helper keeps only
     * positive unique integers before relation sync calls.
     *
     * @param array $items Raw submitted or related items to normalize.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
    protected function integerIds(array $items): array
    {
        return collect($items)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Build parent resource options for the article modal.
     *
     * The site root is included as a synthetic option, followed by Evolution resources, so articles
     * can be attached to either a root bucket or a concrete page.
     *
     * @return array<int, array<string, mixed>> Option payload consumed by evo-ui controls.
     * @since 2.0.0
     */
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

    /**
     * Build author options for the article modal.
     *
     * A blank option supports optional authors, while real authors are labelled from base name
     * fields with an ID fallback for incomplete records.
     *
     * @return array<int, array<string, mixed>> Option payload consumed by evo-ui controls.
     * @since 2.0.0
     */
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

    /**
     * Convert taxonomy records into select options.
     *
     * Categories, tags, and features share the same option shape, so this helper extracts the
     * configured identifier and localized label while dropping incomplete rows.
     *
     * @param Collection $items Raw submitted or related items to normalize.
     * @param string $key State, filter, or model attribute key.
     * @return array<int, array<string, mixed>> Option payload consumed by evo-ui controls.
     * @since 2.0.0
     */
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

    /**
     * Build article options for relation and builder selectors.
     *
     * The current article can be excluded to prevent self-references, and labels prefer translated
     * titles with an ID fallback for records without content.
     *
     * @param ?int $excludeId Article identifier that should not appear in the option list.
     * @return array<int, array<string, mixed>> Option payload consumed by evo-ui controls.
     * @since 2.0.0
     */
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

    /**
     * Transform article models into evo-ui table rows.
     *
     * Rows include display labels, links, media previews, relation chips, dates, views, and action
     * metadata so table rendering stays independent from model internals.
     *
     * @param Collection $articles Article models loaded for the current table page.
     * @param array $parents Parent resource titles keyed by resource ID.
     * @return array<int, array<string, mixed>> Row payload consumed by the manager table.
     * @since 2.0.0
     */
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

    /**
     * Convert stored builder JSON into evo-ui builder blocks.
     *
     * Legacy articles without structured builder data are wrapped into a rich-text block so editors
     * can continue editing old content in the new visual interface.
     *
     * @param string $builderJson Stored builder JSON from the translation row.
     * @param string $content Legacy rendered content fallback.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Normalize one stored builder block for modal editing.
     *
     * Older block formats may use scalar values or parallel arrays. This method reshapes them into
     * named data arrays expected by evo-ui builder fields.
     *
     * @param string $type Article type or builder block identifier.
     * @param mixed $value Raw value that needs package-specific normalization.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Convert evo-ui builder blocks back to the compact storage format.
     *
     * The database keeps the historic one-key-per-block structure while the modal uses explicit
     * type/data keys; this bridge preserves compatibility with existing render templates.
     *
     * @param array $blocks Submitted evo-ui builder blocks.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Expand stored slider arrays into editable item rows.
     *
     * Slider blocks store source and alt values as parallel arrays. The modal needs aligned row
     * objects and at least one empty starter row.
     *
     * @param array $value Raw value that needs package-specific normalization.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Expand stored accordion arrays into editable item rows.
     *
     * Accordion blocks store titles, icons, and rich text as parallel arrays. This method aligns
     * them and keeps an empty starter row for new or incomplete blocks.
     *
     * @param array $value Raw value that needs package-specific normalization.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Render builder blocks into the legacy content field.
     *
     * Builder JSON remains the editable source of truth. The shared renderer materializes it into
     * the historical `content` column through `sarticles::render.*` package views, allowing
     * Laravel-style overrides in `views/vendor/sarticles/render`.
     *
     * @param array $builder Builder data in storage format.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    protected function renderBuilderContent(array $builder): string
    {
        return $this->builderRenderer()->renderContent($builder);
    }

    /**
     * Discover available content builder block configurations.
     *
     * The shared renderer scans package builder configs so manager fields, modal persistence, and
     * CLI re-rendering all agree on the same block IDs and render view names.
     *
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
    protected function builderConfigs(): array
    {
        return $this->builderRenderer()->configs();
    }

    /**
     * Resolve the shared builder renderer.
     *
     * Keeping the renderer behind a helper lets tests instantiate this provider directly while
     * normal Evolution runtime still receives the singleton registered by the service provider.
     *
     * @return BuilderRenderer Builder rendering service.
     * @since 2.0.0
     */
    protected function builderRenderer(): BuilderRenderer
    {
        return app()->bound(BuilderRenderer::class)
            ? app(BuilderRenderer::class)
            : new BuilderRenderer();
    }

    /**
     * Build the evo-ui definition for a builder block type.
     *
     * Definitions describe labels, icons, defaults, validation rules, nested item fields, and
     * article-aware options for blocks that reference package records.
     *
     * @param string $type Article type or builder block identifier.
     * @param ?int $articleId Internal article identifier.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Apply editor configuration to all editor fields in a block.
     *
     * Nested item fields are handled as well, keeping complex builder blocks consistent with the
     * package editor setting without duplicating metadata.
     *
     * @param array $definition Builder block definition to normalize.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Attach package editor settings to one evo-ui field.
     *
     * Non-editor fields are returned unchanged. Editor fields receive the resolved editor name and
     * disable the runtime switcher for a stable authoring experience.
     *
     * @param array $field Single field definition or column descriptor.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
    protected function withConfiguredEditor(array $field): array
    {
        if (($field['type'] ?? '') !== 'editor') {
            return $field;
        }

        $field['editor'] = $this->configuredEditor();
        $field['editor_switcher'] = false;

        return $field;
    }

    /**
     * Resolve the visual editor used by sArticles manager fields.
     *
     * A package-specific editor setting wins when configured. Otherwise the method falls back to
     * Evolution CMS which_editor so the package follows the site-wide preference.
     *
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    protected function configuredEditor(): string
    {
        $editor = trim((string) \sArticles::config('general.editor', 'system'));

        if ($editor === '' || $editor === 'system') {
            return (string) evo()->getConfig('which_editor', 'eTinyMCE');
        }

        return $editor;
    }

    /**
     * Build poll options for article builder blocks.
     *
     * Poll questions may be localized arrays or plain strings; labels prefer the current locale,
     * then fallback/base values, then the poll ID.
     *
     * @return array<int, array<string, mixed>> Option payload consumed by evo-ui controls.
     * @since 2.0.0
     */
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

    /**
     * Resolve an article title for manager labels.
     *
     * The lookup prefers the current application locale and then base content so confirmations and
     * duplicated rows can show a useful name.
     *
     * @param int $articleId Internal article identifier.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
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

    /**
     * Apply weighted search constraints to the listing query.
     *
     * Search terms are sanitized, matched against translated title and content columns, and scored
     * so exact phrase matches appear before looser per-word matches.
     *
     * @param Builder $query Article listing query being mutated.
     * @return void No value is returned; the relevant query, model, or storage state is updated in place.
     * @since 2.0.0
     */
    protected function applySearch(Builder $query): void
    {
        $words = Str::of((string) $this->state('search', ''))
            ->stripTags()
            ->replaceMatches('/[^\p{L}\p{N}\@\.!#$%&\'*+-\/=?^_`{|}~]/iu', ' ')
            ->replaceMatches('/(\s){2,}/', '$1')
            ->trim()
            ->explode(' ')
            ->map(fn ($word) => trim((string) $word))
            ->filter(fn ($word) => mb_strlen($word) > 0)
            ->values();

        if (!$words->count()) {
            return;
        }

        $fields = collect(['sat.pagetitle', 'sat.longtitle', 'sat.introtext', 'sat.description', 'sat.content']);
        $select = collect([0]);
        $bindings = [];
        $exactNeedles = $this->searchNeedles($words->implode(' '));

        $fields->each(function ($field) use ($query, $select, $exactNeedles, &$bindings) {
            foreach ($exactNeedles as $exact) {
                $select->push('(CASE WHEN ' . $this->likeSql($query, $field) . ' THEN 10 ELSE 0 END)');
                $bindings[] = $exact;
            }
        });

        $words->each(function ($word) use ($fields, $query, $select, &$bindings) {
            $needles = $this->searchNeedles($word);
            $fields->each(function ($field) use ($query, $select, $needles, &$bindings) {
                foreach ($needles as $like) {
                    $select->push('(CASE WHEN ' . $this->likeSql($query, $field) . ' THEN 1 ELSE 0 END)');
                    $bindings[] = $like;
                }
            });
        });

        $query
            ->select([
                's_articles.*',
                'sat.pagetitle',
                'sat.longtitle',
                'sat.introtext',
                'sat.description',
                'sat.content',
            ])
            ->selectRaw('(' . $select->implode(' + ') . ') as points', $bindings)
            ->where(function ($where) use ($words, $fields, $query) {
                $words->each(function ($word) use ($fields, $where, $query) {
                    $needles = $this->searchNeedles($word);
                    $fields->each(function ($field) use ($where, $query, $needles) {
                        foreach ($needles as $like) {
                            $where->orWhereRaw($this->likeSql($query, $field), [$like]);
                        }
                    });
                });
            })
            ->orderByDesc('points');
    }

    /**
     * Build a database-aware lowercase LIKE expression.
     *
     * MySQL-compatible drivers can compare lowercased values for case-insensitive matching. SQLite
     * lowercases ASCII only, so Cyrillic searches keep the field unchanged and rely on explicit
     * needle variants generated by {@see searchNeedles()}.
     *
     * @param Builder $query Article listing query being mutated.
     * @param string $field Single field definition or column descriptor.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    protected function likeSql(Builder $query, string $field): string
    {
        return LikeSearch::expression($query, $field, DB::connection()->getDriverName() !== 'sqlite');
    }

    /**
     * Build wildcard search needles for the active database driver.
     *
     * Production databases can use a single lowercased needle against `LOWER(column)`. SQLite is
     * used by the local demo and does not lowercase Ukrainian letters, so it receives a small set of
     * original/lower/upper/title-case variants to keep manager search useful during development.
     *
     * @param string $value User-entered search phrase or token.
     * @return array<int, string> LIKE-ready values with escaped wildcards.
     * @since 2.0.0
     */
    protected function searchNeedles(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return [];
        }

        $variants = DB::connection()->getDriverName() === 'sqlite'
            ? [$value, mb_strtolower($value), mb_strtoupper($value), mb_convert_case($value, MB_CASE_TITLE, 'UTF-8')]
            : [mb_strtolower($value)];

        return collect($variants)
            ->map(fn ($variant) => trim((string) $variant))
            ->filter(fn ($variant) => $variant !== '')
            ->unique()
            ->map(fn ($variant) => LikeSearch::needle($variant))
            ->values()
            ->all();
    }

    /**
     * Apply the published or unpublished filter.
     *
     * Only explicit published states constrain the query; the default all value leaves both
     * published and unpublished articles visible.
     *
     * @param Builder $query Article listing query being mutated.
     * @return void No value is returned; the relevant query, model, or storage state is updated in place.
     * @since 2.0.0
     */
    protected function applyAvailability(Builder $query): void
    {
        $availability = (string) $this->filterState('availability', 'all');

        if ($availability === 'published') {
            $query->where('s_articles.published', 1);
        } elseif ($availability === 'unpublished') {
            $query->where('s_articles.published', 0);
        }
    }

    /**
     * Apply section, category, tag, and feature filters.
     *
     * Section filters map to Evolution parent resources while taxonomy filters use Eloquent
     * relations, keeping table semantics aligned with visible filter groups.
     *
     * @param Builder $query Article listing query being mutated.
     * @return void No value is returned; the relevant query, model, or storage state is updated in place.
     * @since 2.0.0
     */
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

    /**
     * Apply publication date range filters.
     *
     * Dates are accepted only in HTML date input format before they are passed to whereDate,
     * avoiding ambiguous query input from malformed manager state.
     *
     * @param Builder $query Article listing query being mutated.
     * @return void No value is returned; the relevant query, model, or storage state is updated in place.
     * @since 2.0.0
     */
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

    /**
     * Apply a user-selected sortable column to the query.
     *
     * Sorting is allowed only for columns marked sortable in configuration. The boolean return tells
     * the caller whether the default ordering is still needed.
     *
     * @param Builder $query Article listing query being mutated.
     * @return bool Boolean flag used by the caller to choose the next manager-flow branch.
     * @since 2.0.0
     */
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

        if ($field === 'section_title') {
            $this->applySectionTitleSort($query, $direction);

            return true;
        }

        $query->orderBy($field, $direction);

        return true;
    }

    /**
     * Sort articles by the visible Evolution resource title.
     *
     * The table displays root-level article buckets as the configured site name and concrete
     * section parents as their `site_content.pagetitle`. This sort mirrors that UI contract without
     * joining `site_content` into the main select, avoiding duplicate `id` columns that would break
     * Eloquent hydration for article rows.
     *
     * @param Builder $query Article listing query being mutated.
     * @param string $direction Normalized SQL sort direction (`asc` or `desc`).
     * @return void No value is returned; the relevant query, model, or storage state is updated in place.
     * @since 2.0.0
     */
    protected function applySectionTitleSort(Builder $query, string $direction): void
    {
        $grammar = $query->getGrammar();
        $siteContent = $grammar->wrapTable((new SiteContent())->getTable());
        $siteContentId = $siteContent . '.' . $grammar->wrap('id');
        $siteContentTitle = $siteContent . '.' . $grammar->wrap('pagetitle');
        $parent = $grammar->wrap('s_articles.parent');

        $query->orderByRaw(
            'LOWER(COALESCE(CASE WHEN ' . $parent . ' <= 1 THEN ? ELSE (SELECT ' . $siteContentTitle . ' FROM ' . $siteContent . ' WHERE ' . $siteContentId . ' = ' . $parent . ' LIMIT 1) END, ?)) ' . $direction,
            [
                (string) evo()->getConfig('site_name'),
                '',
            ]
        );
    }

    /**
     * Format a publication date for the manager table.
     *
     * Empty values become a dash to keep table cells stable, while valid date-like values use the
     * compact day-month-year manager format.
     *
     * @param mixed $value Raw value that needs package-specific normalization.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    protected function formatDate(mixed $value): string
    {
        if (!$value) {
            return '-';
        }

        return Carbon::parse($value)->format('d.m.Y H:i');
    }

    /**
     * Validate a date filter value from evo-ui state.
     *
     * Only canonical YYYY-MM-DD values are accepted. Invalid values become empty strings so filters
     * can skip them safely.
     *
     * @param string $value Raw value that needs package-specific normalization.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    protected function normalizeFilterDate(string $value): string
    {
        $value = trim($value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    /**
     * Read and normalize integer IDs from a named filter.
     *
     * The table can submit filter values as strings or arrays. This helper keeps only positive
     * unique integers before they reach query constraints.
     *
     * @param string $key State, filter, or model attribute key.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
    protected function filterIds(string $key): array
    {
        return collect((array) $this->filterState($key, []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve the currently active article type.
     *
     * The selected filter wins when valid, then the constructor type, and finally the first
     * configured type or article keeps the manager functional.
     *
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
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

    /**
     * List configured article type keys.
     *
     * Empty or invalid configuration is normalized to the historic article type so older
     * installations continue to load without a type configuration block.
     *
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
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

    /**
     * Determine whether the type filter should be shown.
     *
     * The filter is useful only when enabled in package settings and more than one article type is
     * configured, avoiding unnecessary UI chrome.
     *
     * @return bool Boolean flag used by the caller to choose the next manager-flow branch.
     * @since 2.0.0
     */
    protected function usesTypeFilter(): bool
    {
        return (int) \sArticles::config('general.filter_types_on', 1) === 1
            && count($this->availableTypes()) > 1;
    }

    /**
     * Resolve the manager label for an article type.
     *
     * List-specific labels are preferred, then the configured type name, and finally the raw type
     * key so custom types always remain identifiable.
     *
     * @param string $type Article type or builder block identifier.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    protected function typeLabel(string $type): string
    {
        return (string) \sArticles::config(
            'types.' . $type . '.list',
            \sArticles::config('types.' . $type . '.name', $type)
        );
    }

    /**
     * Copy translation rows from one article to another.
     *
     * Copied rows receive the new article ID, fresh timestamps, and a duplicated-title suffix so
     * editors can identify the clone before editing.
     *
     * @param int $sourceId Source article identifier.
     * @param int $copyId Newly created article identifier.
     * @return void No value is returned; the relevant query, model, or storage state is updated in place.
     * @since 2.0.0
     */
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
                $data['pagetitle'] = trim((string) ($data['pagetitle'] ?? '')) . ' ' . __('sArticles::global.duplicate_suffix');
                $data['created_at'] = now();
                $data['updated_at'] = now();

                DB::table('s_article_translates')->insert($data);
            });
    }

    /**
     * Generate a unique alias for a duplicated article.
     *
     * The method starts with a -copy suffix and increments it until no other article uses the
     * candidate alias, excluding the current article when needed.
     *
     * @param string $alias Source alias or fallback base value.
     * @param int $articleId Internal article identifier.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
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

    /**
     * Build a frontend URL for a parent resource.
     *
     * Root-level article buckets link to the configured site start resource, while concrete parent
     * IDs link directly to that Evolution resource.
     *
     * @param int $id Resource or record identifier.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    protected function resourceUrl(int $id): string
    {
        return UrlProcessor::makeUrl($id > 1 ? $id : (int) evo()->getConfig('site_start', 1));
    }

    /**
     * Extract display labels from related taxonomy models.
     *
     * Empty labels are removed so table chips do not render blank taxonomy badges for incomplete
     * category, tag, or feature records.
     *
     * @param Collection $items Raw submitted or related items to normalize.
     * @return array<string, mixed> Structured payload consumed by evo-ui or the package runtime.
     * @since 2.0.0
     */
    protected function taxonomyLabels(Collection $items): array
    {
        return $items
            ->map(fn ($item) => $this->taxonomyLabel($item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Resolve the best display label for a taxonomy model.
     *
     * The localized base column is preferred, then generic base, then alias, matching translated and
     * legacy taxonomy records.
     *
     * @param object $item Item value used by this manager flow.
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    protected function taxonomyLabel(object $item): string
    {
        $column = $this->baseColumn();

        return trim((string) ($item->{$column} ?? '')) ?: (trim((string) ($item->base ?? '')) ?: trim((string) ($item->alias ?? '')));
    }

    /**
     * Resolve the localized base column used by taxonomy records.
     *
     * The controller owns the default language decision, so table labels stay aligned with the
     * package locale and stored taxonomy columns.
     *
     * @return string String value ready for manager display, storage, or URL generation.
     * @since 2.0.0
     */
    protected function baseColumn(): string
    {
        return (new sArticlesController())->langDefault();
    }

    /**
     * Read a value from the current evo-ui table state.
     *
     * This centralizes default handling for search, sorting, direction, and other top-level state
     * values used while composing table queries.
     *
     * @param string $key State, filter, or model attribute key.
     * @param mixed $default Value returned when the requested state key is missing.
     * @return mixed Value read from the current table or filter state.
     * @since 2.0.0
     */
    protected function state(string $key, mixed $default = null): mixed
    {
        return $this->state[$key] ?? $default;
    }

    /**
     * Read a value from nested evo-ui filter state.
     *
     * Filters live under filters.* in table state. This helper keeps callers compact and makes
     * missing filters resolve to explicit defaults.
     *
     * @param string $key State, filter, or model attribute key.
     * @param mixed $default Value returned when the requested state key is missing.
     * @return mixed Value read from the current table or filter state.
     * @since 2.0.0
     */
    protected function filterState(string $key, mixed $default = null): mixed
    {
        return data_get($this->state, 'filters.' . $key, $default);
    }
}
