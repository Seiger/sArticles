<?php namespace Seiger\sArticles\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticlesFeature;
use Seiger\sArticles\Support\LikeSearch;
use Seiger\sArticles\Tables\Concerns\HandlesLanguageFields;

/**
 * FeaturesTableData package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class FeaturesTableData
{
    use HandlesLanguageFields;

    protected string $moduleUrl;
    protected sArticlesController $controller;

    /**
     * Initialize FeaturesTableData with evo-ui table context.
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
        $this->controller->setModifyTables('features');
    }

    /**
     * Total for FeaturesTableData.
     *
     * This method keeps the total responsibility inside FeaturesTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    public function total(): int
    {
        return (clone $this->featuresQuery())->toBase()->getCountForPagination();
    }

    /**
     * Rows for FeaturesTableData.
     *
     * This method keeps the rows responsibility inside FeaturesTableData, so callers can rely on
     * a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function rows(int $page, int $perPage): array
    {
        return $this->featureRows(
            $this->featuresQuery()
                ->forPage(max(1, $page), max(1, $perPage))
                ->get()
        );
    }

    /**
     * Filter groups for FeaturesTableData.
     *
     * This method keeps the filter groups responsibility inside FeaturesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
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
     * This method keeps the delete name responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    public function deleteName(int $featureId): string
    {
        $feature = sArticlesFeature::find($featureId);

        return $feature ? $this->featureName($feature) : (string) $featureId;
    }

    /**
     * Modal defaults for FeaturesTableData.
     *
     * This method keeps the modal defaults responsibility inside FeaturesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
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
            'color' => '',
            'badge' => '',
        ];

        foreach ($this->languageCodes() as $language) {
            $defaults['translations'][$language]['name'] = '';
        }

        return $defaults;
    }

    /**
     * Modal data for FeaturesTableData.
     *
     * This method keeps the modal data responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
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

    /**
     * Modal fields for FeaturesTableData.
     *
     * This method keeps the modal fields responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
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

    /**
     * Modal alias for FeaturesTableData.
     *
     * This method keeps the modal alias responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    public function modalAlias(string $source, ?int $featureId = null): string
    {
        return $this->controller->validateAlias($source, (int) $featureId, 'feature');
    }

    /**
     * Persist feature modal data from the manager.
     *
     * Feature records use the same evo-ui modal shape split as tags and topics. Single-language
     * installs submit the visible editable name as top-level `name`, while multilingual installs
     * submit one `translations.*.name` value per language. The save path must respect the active
     * shape so old hidden translation state cannot make a successful save appear ignored.
     *
     * @param array<string, mixed> $data Submitted evo-ui modal payload.
     * @param int|null $featureId Existing feature ID or null when creating a new feature.
     * @param string $mode Modal mode supplied by evo-ui (`create` or `edit`).
     * @return int Saved feature identifier.
     * @since 2.0.0
     */
    public function saveModal(array $data, ?int $featureId = null, string $mode = 'create'): int
    {
        $usesLanguageFields = $this->hasLanguageFields();
        $language = $this->defaultLanguage();
        $name = $this->modalFeatureTextValue($data, $language, $usesLanguageFields);

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
            $value = $this->modalFeatureTextValue($data, $lang, $usesLanguageFields);
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

    /**
     * Resolve an editable feature name from modal payload.
     *
     * In the default single-language manager UI the top-level `name` field is the only visible
     * source of truth. Multilingual mode renders translation-specific inputs instead. This helper
     * keeps those two payload contracts explicit and prevents hidden component state from
     * overriding the value the editor actually changed.
     *
     * @param array<string, mixed> $data Submitted evo-ui modal payload.
     * @param string $language Language code currently being persisted.
     * @param bool $usesLanguageFields True when multilingual fields are visible in the modal.
     * @return string Trimmed feature name ready for storage.
     * @since 2.0.0
     */
    protected function modalFeatureTextValue(array $data, string $language, bool $usesLanguageFields): string
    {
        if (!$usesLanguageFields && ($language === $this->defaultLanguage() || $language === 'base')) {
            return trim((string) data_get($data, 'name', ''));
        }

        return trim((string) data_get($data, 'translations.' . $language . '.name', ''));
    }

    /**
     * Move row for FeaturesTableData.
     *
     * This method keeps the move row responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
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

    /**
     * Reorder row for FeaturesTableData.
     *
     * This method keeps the reorder row responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
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

    /**
     * Delete row data from the manager flow.
     *
     * This method keeps the delete row responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function deleteRow(int $featureId): void
    {
        DB::table('s_article_features')->where('feature', $featureId)->delete();
        sArticlesFeature::where('fid', $featureId)->delete();
        $this->normalizePositions();
    }

    /**
     * Features query for FeaturesTableData.
     *
     * This method keeps the features query responsibility inside FeaturesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return Builder Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function featuresQuery(): Builder
    {
        $query = sArticlesFeature::query();

        $this->applySearch($query);

        if (!$this->applySort($query)) {
            $query->orderBy('position')->orderBy($this->nameSortField())->orderBy('fid');
        }

        return $query;
    }

    /**
     * Feature rows for FeaturesTableData.
     *
     * This method keeps the feature rows responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
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

    /**
     * Apply search rules to the current workflow.
     *
     * This method keeps the apply search responsibility inside FeaturesTableData, so callers can
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

        $like = LikeSearch::needle(mb_strtolower($search));
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

    /**
     * Apply sort rules to the current workflow.
     *
     * This method keeps the apply sort responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
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
     * Feature name for FeaturesTableData.
     *
     * This method keeps the feature name responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function featureName(sArticlesFeature $feature): string
    {
        $language = $this->defaultLanguage();
        $name = $language !== 'base' ? trim((string) data_get($feature, $language, '')) : '';
        $fallback = trim((string) $feature->base);

        return $name !== '' ? $name : ($fallback !== '' ? $fallback : __('sArticles::global.no_text'));
    }

    /**
     * Ordered ids for FeaturesTableData.
     *
     * This method keeps the ordered ids responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
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

    /**
     * Apply order rules to the current workflow.
     *
     * This method keeps the apply order responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    protected function applyOrder(array $ids): void
    {
        foreach (array_values($ids) as $position => $id) {
            sArticlesFeature::where('fid', (int) $id)->update(['position' => $position]);
        }
    }

    /**
     * Normalize positions for package-safe usage.
     *
     * This method keeps the normalize positions responsibility inside FeaturesTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    protected function normalizePositions(): void
    {
        $this->applyOrder($this->orderedIds());
    }

    /**
     * Delete url data from the manager flow.
     *
     * This method keeps the delete url responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function deleteUrl(int $featureId): string
    {
        return $this->moduleUrl . '&get=featureDelete&i=' . $featureId;
    }

    /**
     * Default language for FeaturesTableData.
     *
     * This method keeps the default language responsibility inside FeaturesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
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
     * Name sort field for FeaturesTableData.
     *
     * This method keeps the name sort field responsibility inside FeaturesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
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
     * Like sql for FeaturesTableData.
     *
     * This method keeps the like sql responsibility inside FeaturesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function likeSql(Builder $query, string $field): string
    {
        return LikeSearch::lowerExpression($query, $field);
    }

    /**
     * State for FeaturesTableData.
     *
     * This method keeps the state responsibility inside FeaturesTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return mixed Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function state(?string $key = null, mixed $default = null): mixed
    {
        return $key ? data_get($this->state, $key, $default) : $this->state;
    }
}
