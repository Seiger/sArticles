<?php namespace Seiger\sArticles\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticlesCategory;
use Seiger\sArticles\Tables\Concerns\HandlesLanguageFields;

/**
 * CategoriesTableData package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class CategoriesTableData
{
    use HandlesLanguageFields;

    protected string $moduleUrl;
    protected sArticlesController $controller;

    /**
     * Initialize CategoriesTableData with evo-ui table context.
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
        $this->controller->setModifyTables('categories');
    }

    /**
     * Total for CategoriesTableData.
     *
     * This method keeps the total responsibility inside CategoriesTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    public function total(): int
    {
        return (clone $this->categoriesQuery())->toBase()->getCountForPagination();
    }

    /**
     * Rows for CategoriesTableData.
     *
     * This method keeps the rows responsibility inside CategoriesTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function rows(int $page, int $perPage): array
    {
        return $this->categoryRows(
            $this->categoriesQuery()
                ->forPage(max(1, $page), max(1, $perPage))
                ->get()
        );
    }

    /**
     * Filter groups for CategoriesTableData.
     *
     * This method keeps the filter groups responsibility inside CategoriesTableData, so callers
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
     * This method keeps the delete name responsibility inside CategoriesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    public function deleteName(int $categoryId): string
    {
        return $this->categoryNameById($categoryId);
    }

    /**
     * Modal defaults for CategoriesTableData.
     *
     * This method keeps the modal defaults responsibility inside CategoriesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalDefaults(): array
    {
        $defaults = [
            'cover' => '',
            'name' => '',
            'alias' => '',
        ];

        foreach ($this->languageCodes() as $language) {
            $defaults['translations'][$language]['name'] = '';
        }

        return $defaults;
    }

    /**
     * Modal data for CategoriesTableData.
     *
     * This method keeps the modal data responsibility inside CategoriesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalData(int $categoryId): array
    {
        $category = sArticlesCategory::find($categoryId);

        if (!$category) {
            return $this->modalDefaults();
        }

        $data = [
            'cover' => (string) $category->cover,
            'name' => $this->categoryName($category),
            'alias' => (string) $category->alias,
        ];

        foreach ($this->languageCodes() as $language) {
            $value = (string) data_get($category, $this->languageTextField($language), '');
            $data['translations'][$language]['name'] = $value !== '' || $language !== $this->defaultLanguage()
                ? $value
                : (string) $category->base;
        }

        return $data;
    }

    /**
     * Modal fields for CategoriesTableData.
     *
     * This method keeps the modal fields responsibility inside CategoriesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalFields(array $fields, array $data = [], ?int $categoryId = null, string $mode = 'create'): array
    {
        if (!$this->hasLanguageFields()) {
            return $fields;
        }

        $dynamicFields = collect($fields)
            ->reject(fn (array $field) => in_array((string) ($field['name'] ?? ''), ['name', 'alias'], true))
            ->values()
            ->all();

        foreach ($this->languageCodes() as $language) {
            $dynamicFields[] = [
                'name' => 'translations.' . $language . '.name',
                'type' => 'text',
                'label' => __('sArticles::global.category_name') . ' (' . $this->languageLabel($language) . ')',
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

        return $dynamicFields;
    }

    /**
     * Persist modal data.
     *
     * This method keeps the save modal responsibility inside CategoriesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    public function saveModal(array $data, ?int $categoryId = null, string $mode = 'create'): int
    {
        $language = $this->defaultLanguage();
        $name = trim((string) data_get($data, 'translations.' . $language . '.name', data_get($data, 'name', '')));

        if ($name === '') {
            $name = __('sArticles::global.new_category');
        }

        if (!$categoryId && $existing = $this->existingCategory($name)) {
            return (int) $existing->catid;
        }

        $category = $categoryId ? sArticlesCategory::find($categoryId) : null;

        if (!$category) {
            $category = new sArticlesCategory();
            $category->position = ((int) sArticlesCategory::max('position')) + 1;
        }

        $category->alias = $this->controller->validateAlias(
            trim((string) data_get($data, 'alias', '')) ?: $name,
            (int) $category->catid,
            'category'
        );
        $category->cover = $this->normalizeImagePath((string) data_get($data, 'cover', ''));

        foreach ($this->languageCodes() as $lang) {
            $value = trim((string) data_get($data, 'translations.' . $lang . '.name', ''));
            $category->{$this->languageTextField($lang)} = $value;

            if ($lang === $this->controller->langDefault() || $lang === 'base') {
                $category->base = $value !== '' ? $value : $name;
            }
        }

        if (trim((string) $category->base) === '') {
            $category->base = $name;
        }

        $category->save();
        $this->normalizePositions();

        return (int) $category->catid;
    }

    /**
     * Delete row data from the manager flow.
     *
     * This method keeps the delete row responsibility inside CategoriesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function deleteRow(int $categoryId): void
    {
        DB::table('s_article_categories')->where('category', $categoryId)->delete();
        sArticlesCategory::where('catid', $categoryId)->delete();
        $this->normalizePositions();
    }

    /**
     * Move row for CategoriesTableData.
     *
     * This method keeps the move row responsibility inside CategoriesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function moveRow(int $categoryId, string $direction = 'up'): void
    {
        $ordered = $this->orderedIds();
        $index = array_search($categoryId, $ordered, true);

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
     * Reorder row for CategoriesTableData.
     *
     * This method keeps the reorder row responsibility inside CategoriesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function reorderRow(int $categoryId, int $targetId, string $placement = 'before'): void
    {
        if ($categoryId === $targetId) {
            return;
        }

        $ordered = collect($this->orderedIds())
            ->reject(fn (int $id) => $id === $categoryId)
            ->values()
            ->all();
        $targetIndex = array_search($targetId, $ordered, true);

        if ($targetIndex === false) {
            return;
        }

        $insertAt = $placement === 'after' ? $targetIndex + 1 : $targetIndex;
        array_splice($ordered, $insertAt, 0, [$categoryId]);
        $this->applyOrder($ordered);
    }

    /**
     * Categories query for CategoriesTableData.
     *
     * This method keeps the categories query responsibility inside CategoriesTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return Builder Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function categoriesQuery(): Builder
    {
        $query = sArticlesCategory::query();

        $this->applySearch($query);

        if (!$this->applySort($query)) {
            $query->orderBy('position')->orderBy($this->nameSortField())->orderBy('catid');
        }

        return $query;
    }

    /**
     * Category rows for CategoriesTableData.
     *
     * This method keeps the category rows responsibility inside CategoriesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function categoryRows(Collection $categories): array
    {
        return $categories
            ->map(function (sArticlesCategory $category) {
                $name = $this->categoryName($category);
                $cover = trim((string) $category->cover);

                return [
                    'id' => (int) $category->catid,
                    'wire_key' => 'category-row-' . (int) $category->catid,
                    'delete_url' => $this->deleteUrl((int) $category->catid),
                    'delete_name' => $name,
                    'cover_raw' => $cover,
                    'cover' => [
                        'src' => $this->imageSrc($cover),
                        'path' => $cover,
                        'alt' => $name,
                    ],
                    'name' => $name,
                    'alias' => trim((string) $category->alias),
                    'position' => (int) $category->position,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Apply search rules to the current workflow.
     *
     * This method keeps the apply search responsibility inside CategoriesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
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
                $where->orWhere('catid', (int) $this->state('search'));
            }
        });
    }

    /**
     * Apply sort rules to the current workflow.
     *
     * This method keeps the apply sort responsibility inside CategoriesTableData, so callers can
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
     * Category name by id for CategoriesTableData.
     *
     * This method keeps the category name by id responsibility inside CategoriesTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function categoryNameById(int $categoryId): string
    {
        $category = sArticlesCategory::find($categoryId);

        return $category ? $this->categoryName($category) : '';
    }

    /**
     * Existing category for CategoriesTableData.
     *
     * This method keeps the existing category responsibility inside CategoriesTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return ?sArticlesCategory Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function existingCategory(string $name): ?sArticlesCategory
    {
        $language = $this->defaultLanguage();

        return sArticlesCategory::query()
            ->where('base', $name)
            ->when($language !== 'base', fn ($query) => $query->orWhere($language, $name))
            ->first();
    }

    /**
     * Category name for CategoriesTableData.
     *
     * This method keeps the category name responsibility inside CategoriesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function categoryName(sArticlesCategory $category): string
    {
        $language = $this->defaultLanguage();
        $name = $language !== 'base' ? trim((string) data_get($category, $language, '')) : '';
        $fallback = trim((string) $category->base);

        return $name !== '' ? $name : ($fallback !== '' ? $fallback : __('sArticles::global.no_text'));
    }

    /**
     * Image src for CategoriesTableData.
     *
     * This method keeps the image src responsibility inside CategoriesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function imageSrc(string $image): string
    {
        $image = trim($image);

        if ($image === '') {
            return EVO_SITE_URL . 'assets/images/noimage.png';
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, '/')) {
            return $image;
        }

        return EVO_SITE_URL . ltrim($image, '/');
    }

    /**
     * Normalize image path for package-safe usage.
     *
     * This method keeps the normalize image path responsibility inside CategoriesTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function normalizeImagePath(string $image): string
    {
        $image = trim($image);

        if ($image === '') {
            return '';
        }

        $siteUrl = defined('EVO_SITE_URL') ? EVO_SITE_URL : '';

        if ($siteUrl !== '' && str_starts_with($image, $siteUrl)) {
            return ltrim(substr($image, strlen($siteUrl)), '/');
        }

        if (str_starts_with($image, '/')) {
            return ltrim($image, '/');
        }

        return $image;
    }

    /**
     * Ordered ids for CategoriesTableData.
     *
     * This method keeps the ordered ids responsibility inside CategoriesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function orderedIds(): array
    {
        return sArticlesCategory::query()
            ->orderBy('position')
            ->orderBy($this->nameSortField())
            ->orderBy('catid')
            ->pluck('catid')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Apply order rules to the current workflow.
     *
     * This method keeps the apply order responsibility inside CategoriesTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    protected function applyOrder(array $ids): void
    {
        foreach (array_values($ids) as $position => $id) {
            sArticlesCategory::where('catid', (int) $id)->update(['position' => $position]);
        }
    }

    /**
     * Normalize positions for package-safe usage.
     *
     * This method keeps the normalize positions responsibility inside CategoriesTableData, so
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
     * This method keeps the delete url responsibility inside CategoriesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function deleteUrl(int $categoryId): string
    {
        return $this->moduleUrl . '&get=сategoryDelete&i=' . $categoryId;
    }

    /**
     * Default language for CategoriesTableData.
     *
     * This method keeps the default language responsibility inside CategoriesTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function defaultLanguage(): string
    {
        return $this->controller->langDefault();
    }

    /**
     * Name sort field for CategoriesTableData.
     *
     * This method keeps the name sort field responsibility inside CategoriesTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
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
     * Like sql for CategoriesTableData.
     *
     * This method keeps the like sql responsibility inside CategoriesTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
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
     * State for CategoriesTableData.
     *
     * This method keeps the state responsibility inside CategoriesTableData, so callers can rely
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
