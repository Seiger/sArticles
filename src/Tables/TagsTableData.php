<?php namespace Seiger\sArticles\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticlesTag;
use Seiger\sArticles\Tables\Concerns\HandlesLanguageFields;

/**
 * TagsTableData package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class TagsTableData
{
    use HandlesLanguageFields;

    protected string $moduleUrl;
    protected sArticlesController $controller;

    /**
     * Initialize TagsTableData with evo-ui table context.
     *
     * Stores manager context, table state, and configuration so row loading, modal
     * building, and persistence helpers operate against the same request snapshot.
     *
     * @param array<string, mixed> $context Runtime context passed by the manager module.
     * @param array<string, mixed> $state Current table state, including filters and sorting.
     * @param array<string, mixed> $config Resolved table or modal configuration.
     * @since 2.0.0
     */
    public function __construct(
        protected array $context = [],
        protected array $state = [],
        protected array $config = [],
    ) {
        $this->moduleUrl = (string) ($context['moduleUrl'] ?? '');
        $this->controller = new sArticlesController();
        $this->controller->setModifyTables('tags');
    }

    /**
     * Total for TagsTableData.
     *
     * This method keeps the total responsibility inside TagsTableData, so callers can rely on a
     * stable package boundary while the manager UI, frontend runtime, or legacy storage details
     * evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    public function total(): int
    {
        return (clone $this->tagsQuery())->toBase()->getCountForPagination();
    }

    /**
     * Rows for TagsTableData.
     *
     * This method keeps the rows responsibility inside TagsTableData, so callers can rely on a
     * stable package boundary while the manager UI, frontend runtime, or legacy storage details
     * evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function rows(int $page, int $perPage): array
    {
        return $this->tagRows(
            $this->tagsQuery()
                ->forPage(max(1, $page), max(1, $perPage))
                ->get()
        );
    }

    /**
     * Filter groups for TagsTableData.
     *
     * This method keeps the filter groups responsibility inside TagsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function filterGroups(): array
    {
        return [];
    }

    /**
     * Delete name data from the manager flow.
     *
     * This method keeps the delete name responsibility inside TagsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    public function deleteName(int $tagId): string
    {
        return $this->tagNameById($tagId);
    }

    /**
     * Modal defaults for TagsTableData.
     *
     * This method keeps the modal defaults responsibility inside TagsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalDefaults(): array
    {
        $defaults = [
            'name' => '',
            'alias' => '',
        ];

        foreach ($this->languageCodes() as $language) {
            $defaults['translations'][$language]['name'] = '';
            $defaults['translations'][$language]['content'] = '';
        }

        return $defaults;
    }

    /**
     * Modal data for TagsTableData.
     *
     * This method keeps the modal data responsibility inside TagsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalData(int $tagId): array
    {
        $tag = sArticlesTag::find($tagId);

        if (!$tag) {
            return $this->modalDefaults();
        }

        $data = [
            'name' => $this->tagName($tag),
            'alias' => (string) $tag->alias,
        ];

        foreach ($this->languageCodes() as $language) {
            $value = (string) data_get($tag, $this->languageTextField($language), '');
            $data['translations'][$language]['name'] = $value !== '' || $language !== $this->defaultLanguage()
                ? $value
                : (string) $tag->base;
            $data['translations'][$language]['content'] = $this->tagContent($tag, $language);
        }

        return $data;
    }

    /**
     * Modal options for TagsTableData.
     *
     * This method keeps the modal options responsibility inside TagsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalOptions(array $config, array $data = [], ?int $tagId = null, string $mode = 'create'): array
    {
        if ($this->hasLanguageFields() && in_array($mode, ['create', 'edit'], true)) {
            $config['size'] = 'content';
        }

        return $config;
    }

    /**
     * Row actions for TagsTableData.
     *
     * This method keeps the row actions responsibility inside TagsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function rowActions(array $actions): array
    {
        if (!$this->hasLanguageFields()) {
            return $actions;
        }

        return collect($actions)
            ->reject(fn (array $action) => (string) ($action['key'] ?? '') === 'texts')
            ->values()
            ->all();
    }

    /**
     * Modal fields for TagsTableData.
     *
     * This method keeps the modal fields responsibility inside TagsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalFields(array $fields, array $data = [], ?int $tagId = null, string $mode = 'create'): array
    {
        if ($mode === 'texts') {
            if (!$this->hasLanguageFields()) {
                return $fields;
            }

            return collect($this->languageCodes())
                ->map(fn (string $language) => [
                    'name' => 'translations.' . $language . '.content',
                    'type' => 'editor',
                    'label' => __('sArticles::global.content') . ' (' . $this->languageLabel($language) . ')',
                    'show_label' => false,
                    'rules' => ['nullable', 'string'],
                    'span' => 'full',
                    'rows' => 18,
                    'height' => '560px',
                    'editor_switcher' => false,
                    'editor_provider' => 'tagTextEditorHtml',
                ])
                ->values()
                ->all();
        }

        if (!$this->hasLanguageFields()) {
            return $fields;
        }

        $dynamicFields = [];

        foreach ($this->languageCodes() as $language) {
            $dynamicFields[] = [
                'name' => 'translations.' . $language . '.name',
                'type' => 'text',
                'label' => __('sArticles::global.tag_name') . ' (' . $this->languageLabel($language) . ')',
                'rules' => [$language === $this->defaultLanguage() ? 'required' : 'nullable', 'string', 'max:255'],
                'span' => 'full',
                'live' => $language === $this->defaultLanguage(),
            ];
        }

        $dynamicFields[] = [
            'name' => 'alias',
            'type' => 'alias',
            'label' => 'sArticles::global.alias',
            'source' => ['translations.' . $this->defaultLanguage() . '.name', 'name'],
            'rules' => ['nullable', 'string', 'max:255'],
            'span' => 'full',
        ];

        foreach ($this->languageCodes() as $language) {
            $dynamicFields[] = [
                'name' => 'translations.' . $language . '.content',
                'type' => 'editor',
                'label' => __('sArticles::global.content') . ' (' . $this->languageLabel($language) . ')',
                'show_label' => true,
                'rules' => ['nullable', 'string'],
                'span' => 'full',
                'rows' => 12,
                'height' => '360px',
                'editor_switcher' => false,
                'editor_provider' => 'tagTextEditorHtml',
            ];
        }

        return $dynamicFields;
    }

    /**
     * Persist modal data.
     *
     * This method keeps the save modal responsibility inside TagsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    public function saveModal(array $data, ?int $tagId = null, string $mode = 'create'): int
    {
        $language = $this->defaultLanguage();
        $name = trim((string) data_get($data, 'translations.' . $language . '.name', data_get($data, 'name', '')));

        if ($name === '') {
            $name = __('sArticles::global.new_tag');
        }

        if (!$tagId && $existing = $this->existingTag($name)) {
            return (int) $existing->tagid;
        }

        $tag = $tagId ? sArticlesTag::find($tagId) : null;

        if (!$tag) {
            $tag = new sArticlesTag();
            $tag->position = ((int) sArticlesTag::max('position')) + 1;
        }

        $tag->alias = $this->controller->validateAlias(
            trim((string) data_get($data, 'alias', '')) ?: $name,
            (int) $tag->tagid,
            'tag'
        );

        foreach ($this->languageCodes() as $lang) {
            $value = trim((string) data_get($data, 'translations.' . $lang . '.name', ''));
            $tag->{$this->languageTextField($lang)} = $value;

            if ($lang === $this->controller->langDefault() || $lang === 'base') {
                $tag->base = $value !== '' ? $value : $name;
            }

            if ($this->hasLanguageFields()) {
                $content = (string) data_get($data, 'translations.' . $lang . '.content', '');
                $tag->{$this->languageContentField($lang)} = $content;

                if ($lang === $this->controller->langDefault() || $lang === 'base') {
                    $tag->base_content = $content;
                }
            }
        }

        if (trim((string) $tag->base) === '') {
            $tag->base = $name;
        }

        $tag->save();

        return (int) $tag->tagid;
    }

    /**
     * Tag text modal data for TagsTableData.
     *
     * This method keeps the tag text modal data responsibility inside TagsTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function tagTextModalData(int $tagId): array
    {
        $tag = sArticlesTag::find($tagId);

        return [
            'content' => $tag ? (string) data_get($tag, $this->contentColumn(), '') : '',
            'translations' => collect($this->languageCodes())
                ->mapWithKeys(fn (string $language) => [
                    $language => [
                        'content' => $tag ? $this->tagContent($tag, $language) : '',
                    ],
                ])
                ->all(),
        ];
    }

    /**
     * Persist tag text modal data.
     *
     * This method keeps the save tag text modal responsibility inside TagsTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    public function saveTagTextModal(array $data, ?int $tagId = null): int
    {
        if (!$tagId) {
            return 0;
        }

        $tag = sArticlesTag::find($tagId);

        if (!$tag) {
            return 0;
        }

        if ($this->hasLanguageFields()) {
            foreach ($this->languageCodes() as $language) {
                $content = (string) data_get($data, 'translations.' . $language . '.content', '');
                $tag->{$this->languageContentField($language)} = $content;

                if ($language === $this->controller->langDefault() || $language === 'base') {
                    $tag->base_content = $content;
                }
            }
        } else {
            $content = (string) data_get($data, 'content', '');
            $column = $this->contentColumn();
            $tag->{$column} = $content;

            if ($column !== 'base_content' && $this->defaultLanguage() === $this->controller->langDefault()) {
                $tag->base_content = $content;
            }
        }

        $tag->save();

        return (int) $tag->tagid;
    }

    /**
     * Tag text editor html for TagsTableData.
     *
     * This method keeps the tag text editor html responsibility inside TagsTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    public function tagTextEditorHtml(string $fieldId, array $field = []): string
    {
        return $this->controller->textEditor(
            $fieldId,
            (string) ($field['height'] ?? '420px'),
            $this->configuredEditor()
        );
    }

    /**
     * Configured editor for TagsTableData.
     *
     * This method keeps the configured editor responsibility inside TagsTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function configuredEditor(): string
    {
        $configured = trim((string) \sArticles::config('general.editor', 'system'));

        if ($configured === '' || $configured === 'system') {
            return (string) evo()->getConfig('which_editor', 'eTinyMCE');
        }

        return $configured;
    }

    /**
     * Delete row data from the manager flow.
     *
     * This method keeps the delete row responsibility inside TagsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function deleteRow(int $tagId): void
    {
        sArticlesTag::where('tagid', $tagId)->delete();
    }

    /**
     * Tags query for TagsTableData.
     *
     * This method keeps the tags query responsibility inside TagsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return Builder Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function tagsQuery(): Builder
    {
        $query = sArticlesTag::query();

        $this->applySearch($query);

        if (!$this->applySort($query)) {
            $query->orderBy($this->nameSortField())->orderBy('tagid');
        }

        return $query;
    }

    /**
     * Tag rows for TagsTableData.
     *
     * This method keeps the tag rows responsibility inside TagsTableData, so callers can rely on
     * a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function tagRows(Collection $tags): array
    {
        return $tags
            ->map(fn (sArticlesTag $tag) => [
                'id' => (int) $tag->tagid,
                'wire_key' => 'tag-row-' . (int) $tag->tagid,
                'delete_url' => $this->deleteUrl((int) $tag->tagid),
                'delete_name' => $this->tagName($tag),
                'name' => $this->tagName($tag),
                'alias' => trim((string) $tag->alias),
                'has_content' => $this->tagHasContent($tag),
            ])
            ->values()
            ->all();
    }

    /**
     * Apply search rules to the current workflow.
     *
     * This method keeps the apply search responsibility inside TagsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    protected function applySearch(Builder $query): void
    {
        $search = trim((string) $this->state('search', ''));

        if ($search === '') {
            return;
        }

        $like = '%' . addcslashes(mb_strtolower($search), '\\%_') . '%';
        $language = $this->defaultLanguage();

        $query->where(function ($where) use ($query, $like, $language) {
            $where->orWhereRaw($this->likeSql($query, 'base'), [$like])
                ->orWhereRaw($this->likeSql($query, 'alias'), [$like]);

            if ($language !== 'base') {
                $where->orWhereRaw($this->likeSql($query, $language), [$like]);
            }

            if (ctype_digit(trim((string) $this->state('search', '')))) {
                $where->orWhere('tagid', (int) $this->state('search'));
            }
        });
    }

    /**
     * Apply sort rules to the current workflow.
     *
     * This method keeps the apply sort responsibility inside TagsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return bool True when the package condition is met, false otherwise.
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

        $field = (string) ($column['sort_field'] ?? $column['key'] ?? '');

        if ($key === 'name') {
            $field = $this->nameSortField();
        }

        if ($field === '') {
            return false;
        }

        $direction = $this->state('direction') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($field, $direction);

        return true;
    }

    /**
     * Tag name by id for TagsTableData.
     *
     * This method keeps the tag name by id responsibility inside TagsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function tagNameById(int $tagId): string
    {
        $tag = sArticlesTag::find($tagId);

        return $tag ? $this->tagName($tag) : '';
    }

    /**
     * Existing tag for TagsTableData.
     *
     * This method keeps the existing tag responsibility inside TagsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return ?sArticlesTag Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function existingTag(string $name): ?sArticlesTag
    {
        $language = $this->defaultLanguage();

        return sArticlesTag::query()
            ->where('base', $name)
            ->when($language !== 'base', fn ($query) => $query->orWhere($language, $name))
            ->first();
    }

    /**
     * Tag name for TagsTableData.
     *
     * This method keeps the tag name responsibility inside TagsTableData, so callers can rely on
     * a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function tagName(sArticlesTag $tag): string
    {
        $language = $this->defaultLanguage();
        $name = $language !== 'base' ? trim((string) data_get($tag, $language, '')) : '';

        return $name !== '' ? $name : trim((string) $tag->base);
    }

    /**
     * Delete url data from the manager flow.
     *
     * This method keeps the delete url responsibility inside TagsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function deleteUrl(int $tagId): string
    {
        return $this->moduleUrl . '&get=tagDelete&i=' . $tagId;
    }

    /**
     * Default language for TagsTableData.
     *
     * This method keeps the default language responsibility inside TagsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function defaultLanguage(): string
    {
        return $this->controller->langDefault();
    }

    /**
     * Content column for TagsTableData.
     *
     * This method keeps the content column responsibility inside TagsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function contentColumn(): string
    {
        $language = $this->defaultLanguage();

        return ($language !== '' ? $language : 'base') . '_content';
    }

    /**
     * Has rich content for TagsTableData.
     *
     * This method keeps the has rich content responsibility inside TagsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return bool True when the package condition is met, false otherwise.
     * @since 2.0.0
     */
    protected function hasRichContent(string $content): bool
    {
        $content = trim($content);

        if ($content === '') {
            return false;
        }

        if (preg_match('/<(img|picture|source|video|audio|iframe|embed|object)\b/i', $content) === 1) {
            return true;
        }

        $text = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);

        return trim($text) !== '';
    }

    /**
     * Tag has content for TagsTableData.
     *
     * This method keeps the tag has content responsibility inside TagsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return bool True when the package condition is met, false otherwise.
     * @since 2.0.0
     */
    protected function tagHasContent(sArticlesTag $tag): bool
    {
        foreach ($this->languageCodes() as $language) {
            if ($this->hasRichContent((string) data_get($tag, $this->languageContentField($language), ''))) {
                return true;
            }
        }

        return $this->hasRichContent((string) data_get($tag, 'base_content', ''));
    }

    /**
     * Tag content for TagsTableData.
     *
     * This method keeps the tag content responsibility inside TagsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function tagContent(sArticlesTag $tag, string $language): string
    {
        $content = (string) data_get($tag, $this->languageContentField($language), '');

        if ($content === '' && $language === $this->defaultLanguage()) {
            return (string) data_get($tag, 'base_content', '');
        }

        return $content;
    }

    /**
     * Name sort field for TagsTableData.
     *
     * This method keeps the name sort field responsibility inside TagsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function nameSortField(): string
    {
        $language = $this->defaultLanguage();

        return $language !== 'base' ? $language : 'base';
    }

    /**
     * Like sql for TagsTableData.
     *
     * This method keeps the like sql responsibility inside TagsTableData, so callers can rely on
     * a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function likeSql(Builder $query, string $field): string
    {
        $sql = 'LOWER(' . $query->getGrammar()->wrap($field) . ') LIKE ?';

        return DB::connection()->getDriverName() === 'sqlite' ? $sql : $sql . " ESCAPE '\\\\'";
    }

    /**
     * State for TagsTableData.
     *
     * This method keeps the state responsibility inside TagsTableData, so callers can rely on a
     * stable package boundary while the manager UI, frontend runtime, or legacy storage details
     * evolve.
     *
     * @return mixed Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function state(?string $key = null, mixed $default = null): mixed
    {
        return $key ? data_get($this->state, $key, $default) : $this->state;
    }
}
