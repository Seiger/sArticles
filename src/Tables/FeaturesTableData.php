<?php

namespace Seiger\sArticles\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticlesFeature;
use Seiger\sArticles\Tables\Concerns\HandlesLanguageFields;

class FeaturesTableData
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
        $this->controller->setModifyTables('features');
    }

    public function total(): int
    {
        return (clone $this->featuresQuery())->toBase()->getCountForPagination();
    }

    public function rows(int $page, int $perPage): array
    {
        return $this->featureRows(
            $this->featuresQuery()
                ->forPage(max(1, $page), max(1, $perPage))
                ->get()
        );
    }

    public function filterGroups(): array
    {
        return [];
    }

    public function deleteName(int $featureId): string
    {
        $feature = sArticlesFeature::find($featureId);

        return $feature ? $this->featureName($feature) : (string) $featureId;
    }

    public function modalDefaults(): array
    {
        $defaults = [
            'name' => '',
            'alias' => '',
            'color' => '',
            'badge' => '',
        ];

        foreach ($this->languageCodes() as $language) {
            $defaults['translations'][$language]['name'] = '';
        }

        return $defaults;
    }

    public function modalData(int $featureId): array
    {
        $feature = sArticlesFeature::find($featureId);

        if (!$feature) {
            return $this->modalDefaults();
        }

        $data = [
            'name' => $this->featureName($feature),
            'alias' => (string) $feature->alias,
            'color' => (string) $feature->color,
            'badge' => (string) $feature->badge,
        ];

        foreach ($this->languageCodes() as $language) {
            $value = (string) data_get($feature, $this->languageTextField($language), '');
            $data['translations'][$language]['name'] = $value !== '' || $language !== $this->defaultLanguage()
                ? $value
                : (string) $feature->base;
        }

        return $data;
    }

    public function modalFields(array $fields, array $data = [], ?int $featureId = null, string $mode = 'create'): array
    {
        if (!$this->hasLanguageFields()) {
            return $fields;
        }

        $dynamicFields = [];

        foreach ($this->languageCodes() as $language) {
            $dynamicFields[] = [
                'name' => 'translations.' . $language . '.name',
                'type' => 'text',
                'label' => __('sArticles::global.feature_name') . ' (' . $this->languageLabel($language) . ')',
                'rules' => [$language === $this->defaultLanguage() ? 'required' : 'nullable', 'string', 'max:255'],
                'span' => 'full',
                'live' => $language === $this->defaultLanguage(),
            ];
        }

        return array_merge($dynamicFields, collect($fields)
            ->reject(fn (array $field) => ($field['name'] ?? '') === 'name')
            ->map(function (array $field) {
                if (($field['name'] ?? '') === 'alias') {
                    $field['source'] = ['translations.' . $this->defaultLanguage() . '.name', 'name'];
                }

                return $field;
            })
            ->values()
            ->all());
    }

    public function modalAlias(string $source, ?int $featureId = null): string
    {
        return $this->controller->validateAlias($source, (int) $featureId, 'feature');
    }

    public function saveModal(array $data, ?int $featureId = null, string $mode = 'create'): int
    {
        $language = $this->defaultLanguage();
        $name = trim((string) data_get($data, 'translations.' . $language . '.name', data_get($data, 'name', '')));

        if ($name === '') {
            $name = __('sArticles::global.feature_item');
        }

        $feature = $featureId ? sArticlesFeature::find($featureId) : null;

        if (!$feature) {
            $feature = new sArticlesFeature();
            $feature->position = ((int) sArticlesFeature::max('position')) + 1;
        }

        $alias = trim((string) data_get($data, 'alias', ''));

        $feature->alias = $this->modalAlias($alias !== '' ? $alias : $name, (int) $feature->fid);
        $feature->color = trim((string) data_get($data, 'color', ''));
        $feature->badge = trim((string) data_get($data, 'badge', ''));

        foreach ($this->languageCodes() as $lang) {
            $value = trim((string) data_get($data, 'translations.' . $lang . '.name', ''));
            $feature->{$this->languageTextField($lang)} = $value;

            if ($lang === $this->controller->langDefault() || $lang === 'base') {
                $feature->base = $value !== '' ? $value : $name;
            }
        }

        if (trim((string) $feature->base) === '') {
            $feature->base = $name;
        }

        $feature->save();
        $this->normalizePositions();

        return (int) $feature->fid;
    }

    public function moveRow(int $featureId, string $direction = 'up'): void
    {
        $ordered = $this->orderedIds();
        $index = array_search($featureId, $ordered, true);

        if ($index === false) {
            return;
        }

        $target = $direction === 'down' ? $index + 1 : $index - 1;

        if (!array_key_exists($target, $ordered)) {
            return;
        }

        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];
        $this->applyOrder($ordered);
    }

    public function reorderRow(int $featureId, int $targetId, string $placement = 'before'): void
    {
        if ($featureId === $targetId) {
            return;
        }

        $ordered = collect($this->orderedIds())
            ->reject(fn (int $id) => $id === $featureId)
            ->values()
            ->all();
        $targetIndex = array_search($targetId, $ordered, true);

        if ($targetIndex === false) {
            return;
        }

        $insertAt = $placement === 'after' ? $targetIndex + 1 : $targetIndex;
        array_splice($ordered, $insertAt, 0, [$featureId]);
        $this->applyOrder($ordered);
    }

    public function deleteRow(int $featureId): void
    {
        DB::table('s_article_features')->where('feature', $featureId)->delete();
        sArticlesFeature::where('fid', $featureId)->delete();
        $this->normalizePositions();
    }

    protected function featuresQuery(): Builder
    {
        $query = sArticlesFeature::query();

        $this->applySearch($query);

        if (!$this->applySort($query)) {
            $query->orderBy('position')->orderBy($this->nameSortField())->orderBy('fid');
        }

        return $query;
    }

    protected function featureRows(Collection $features): array
    {
        return $features
            ->map(fn (sArticlesFeature $feature) => [
                'id' => (int) $feature->fid,
                'wire_key' => 'feature-row-' . (int) $feature->fid,
                'delete_url' => $this->deleteUrl((int) $feature->fid),
                'delete_name' => $this->featureName($feature),
                'name' => $this->featureName($feature),
                'alias' => trim((string) $feature->alias),
                'color' => trim((string) $feature->color),
                'badge' => trim((string) $feature->badge),
                'position' => (int) $feature->position,
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
                ->orWhereRaw($this->likeSql($query, 'alias'), [$like])
                ->orWhereRaw($this->likeSql($query, 'color'), [$like])
                ->orWhereRaw($this->likeSql($query, 'badge'), [$like]);

            if ($language !== 'base') {
                $where->orWhereRaw($this->likeSql($query, $language), [$like]);
            }

            if (ctype_digit(trim((string) $this->state('search', '')))) {
                $where->orWhere('fid', (int) $this->state('search'));
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

    protected function featureName(sArticlesFeature $feature): string
    {
        $language = $this->defaultLanguage();
        $name = $language !== 'base' ? trim((string) data_get($feature, $language, '')) : '';
        $fallback = trim((string) $feature->base);

        return $name !== '' ? $name : ($fallback !== '' ? $fallback : __('sArticles::global.no_text'));
    }

    protected function orderedIds(): array
    {
        return sArticlesFeature::query()
            ->orderBy('position')
            ->orderBy($this->nameSortField())
            ->orderBy('fid')
            ->pluck('fid')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function applyOrder(array $ids): void
    {
        foreach (array_values($ids) as $position => $id) {
            sArticlesFeature::where('fid', (int) $id)->update(['position' => $position]);
        }
    }

    protected function normalizePositions(): void
    {
        $this->applyOrder($this->orderedIds());
    }

    protected function deleteUrl(int $featureId): string
    {
        return $this->moduleUrl . '&get=featureDelete&i=' . $featureId;
    }

    protected function defaultLanguage(): string
    {
        return $this->controller->langDefault();
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
