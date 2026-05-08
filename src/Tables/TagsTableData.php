<?php

namespace Seiger\sArticles\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticlesTag;
use Seiger\sArticles\Tables\Concerns\HandlesLanguageFields;

class TagsTableData
{
    use HandlesLanguageFields;

    protected string $moduleUrl;
    protected sArticlesController $controller;

    public function __construct(
        protected array $context = [],
        protected array $state = [],
        protected array $config = [],
    ) {
        $this->moduleUrl = (string) ($context['moduleUrl'] ?? '');
        $this->controller = new sArticlesController();
        $this->controller->setModifyTables('tags');
    }

    public function total(): int
    {
        return (clone $this->tagsQuery())->toBase()->getCountForPagination();
    }

    public function rows(int $page, int $perPage): array
    {
        return $this->tagRows(
            $this->tagsQuery()
                ->forPage(max(1, $page), max(1, $perPage))
                ->get()
        );
    }

    public function filterGroups(): array
    {
        return [];
    }

    public function deleteName(int $tagId): string
    {
        return $this->tagNameById($tagId);
    }

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

    public function modalOptions(array $config, array $data = [], ?int $tagId = null, string $mode = 'create'): array
    {
        if ($this->hasLanguageFields() && in_array($mode, ['create', 'edit'], true)) {
            $config['size'] = 'content';
        }

        return $config;
    }

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

    public function tagTextEditorHtml(string $fieldId, array $field = []): string
    {
        return $this->controller->textEditor(
            $fieldId,
            (string) ($field['height'] ?? '420px'),
            $this->configuredEditor()
        );
    }

    protected function configuredEditor(): string
    {
        $configured = trim((string) \sArticles::config('general.editor', 'system'));

        if ($configured === '' || $configured === 'system') {
            return (string) evo()->getConfig('which_editor', 'eTinyMCE');
        }

        return $configured;
    }

    public function deleteRow(int $tagId): void
    {
        sArticlesTag::where('tagid', $tagId)->delete();
    }

    protected function tagsQuery(): Builder
    {
        $query = sArticlesTag::query();

        $this->applySearch($query);

        if (!$this->applySort($query)) {
            $query->orderBy($this->nameSortField())->orderBy('tagid');
        }

        return $query;
    }

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

    protected function tagNameById(int $tagId): string
    {
        $tag = sArticlesTag::find($tagId);

        return $tag ? $this->tagName($tag) : '';
    }

    protected function existingTag(string $name): ?sArticlesTag
    {
        $language = $this->defaultLanguage();

        return sArticlesTag::query()
            ->where('base', $name)
            ->when($language !== 'base', fn ($query) => $query->orWhere($language, $name))
            ->first();
    }

    protected function tagName(sArticlesTag $tag): string
    {
        $language = $this->defaultLanguage();
        $name = $language !== 'base' ? trim((string) data_get($tag, $language, '')) : '';

        return $name !== '' ? $name : trim((string) $tag->base);
    }

    protected function deleteUrl(int $tagId): string
    {
        return $this->moduleUrl . '&get=tagDelete&i=' . $tagId;
    }

    protected function defaultLanguage(): string
    {
        return $this->controller->langDefault();
    }

    protected function contentColumn(): string
    {
        $language = $this->defaultLanguage();

        return ($language !== '' ? $language : 'base') . '_content';
    }

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

    protected function tagHasContent(sArticlesTag $tag): bool
    {
        foreach ($this->languageCodes() as $language) {
            if ($this->hasRichContent((string) data_get($tag, $this->languageContentField($language), ''))) {
                return true;
            }
        }

        return $this->hasRichContent((string) data_get($tag, 'base_content', ''));
    }

    protected function tagContent(sArticlesTag $tag, string $language): string
    {
        $content = (string) data_get($tag, $this->languageContentField($language), '');

        if ($content === '' && $language === $this->defaultLanguage()) {
            return (string) data_get($tag, 'base_content', '');
        }

        return $content;
    }

    protected function nameSortField(): string
    {
        $language = $this->defaultLanguage();

        return $language !== 'base' ? $language : 'base';
    }

    protected function likeSql(Builder $query, string $field): string
    {
        $sql = 'LOWER(' . $query->getGrammar()->wrap($field) . ') LIKE ?';

        return DB::connection()->getDriverName() === 'sqlite' ? $sql : $sql . " ESCAPE '\\\\'";
    }

    protected function state(?string $key = null, mixed $default = null): mixed
    {
        return $key ? data_get($this->state, $key, $default) : $this->state;
    }
}
