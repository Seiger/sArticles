<?php namespace Seiger\sArticles\Tables;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticlesAuthor;
use Seiger\sArticles\Tables\Concerns\HandlesLanguageFields;

/**
 * AuthorsTableData package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class AuthorsTableData
{
    use HandlesLanguageFields;

    protected string $moduleUrl;
    protected sArticlesController $controller;

    /**
     * Initialize AuthorsTableData with evo-ui table context.
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
        $this->controller->setModifyTables('authors');
    }

    /**
     * Total for AuthorsTableData.
     *
     * This method keeps the total responsibility inside AuthorsTableData, so callers can rely on
     * a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    public function total(): int
    {
        return (clone $this->authorsQuery())->toBase()->getCountForPagination();
    }

    /**
     * Rows for AuthorsTableData.
     *
     * This method keeps the rows responsibility inside AuthorsTableData, so callers can rely on
     * a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function rows(int $page, int $perPage): array
    {
        return $this->authorRows(
            $this->authorsQuery()
                ->forPage(max(1, $page), max(1, $perPage))
                ->get()
        );
    }

    /**
     * Filter groups for AuthorsTableData.
     *
     * This method keeps the filter groups responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function filterGroups(): array
    {
        return [
            [
                'key' => 'office',
                'items' => sArticlesAuthor::query()
                    ->select($this->authorSortField('office') . ' as office_value')
                    ->where($this->authorSortField('office'), '<>', '')
                    ->distinct()
                    ->orderBy($this->authorSortField('office'))
                    ->get()
                    ->map(fn (sArticlesAuthor $author) => [
                        'id' => crc32((string) $author->office_value),
                        'label' => (string) $author->office_value,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    /**
     * Delete name data from the manager flow.
     *
     * This method keeps the delete name responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    public function deleteName(int $authorId): string
    {
        $name = $this->authorName($authorId);

        return $name !== '' ? $name : (string) $authorId;
    }

    /**
     * Delete row data from the manager flow.
     *
     * This method keeps the delete row responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function deleteRow(int $authorId): void
    {
        if (!isset($_SESSION['mgrValidated'])) {
            return;
        }

        sArticlesAuthor::where('autid', $authorId)->delete();
    }

    /**
     * Modal defaults for AuthorsTableData.
     *
     * This method keeps the modal defaults responsibility inside AuthorsTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalDefaults(): array
    {
        $defaults = [
            'image' => '',
            'name' => '',
            'lastname' => '',
            'alias' => '',
            'gender' => 'man',
            'office' => '',
        ];

        foreach ($this->languageCodes() as $language) {
            $defaults['translations'][$language] = [
                'name' => '',
                'lastname' => '',
                'office' => '',
            ];
        }

        return $defaults;
    }

    /**
     * Modal data for AuthorsTableData.
     *
     * This method keeps the modal data responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalData(int $authorId): array
    {
        $author = sArticlesAuthor::find($authorId);

        if (!$author) {
            return $this->modalDefaults();
        }

        $data = [
            'image' => (string) $author->image,
            'name' => $this->authorField($author, 'name'),
            'lastname' => $this->authorField($author, 'lastname'),
            'alias' => (string) $author->alias,
            'gender' => $this->normalizeGender((string) $author->gender),
            'office' => $this->authorField($author, 'office'),
        ];

        foreach ($this->languageCodes() as $language) {
            foreach (['name', 'lastname', 'office'] as $field) {
                $value = (string) data_get($author, $this->languageAuthorField($language, $field), '');
                $data['translations'][$language][$field] = $value !== '' || $language !== $this->defaultLanguage()
                    ? $value
                    : (string) data_get($author, 'base_' . $field, '');
            }
        }

        return $data;
    }

    /**
     * Modal fields for AuthorsTableData.
     *
     * This method keeps the modal fields responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalFields(array $fields, array $data = [], ?int $authorId = null, string $mode = 'create'): array
    {
        if (!$this->hasLanguageFields()) {
            return $fields;
        }

        $dynamicFields = collect($fields)
            ->reject(fn (array $field) => in_array((string) ($field['name'] ?? ''), ['name', 'lastname', 'office'], true))
            ->values()
            ->all();

        foreach ($this->languageCodes() as $language) {
            $dynamicFields[] = [
                'name' => 'translations.' . $language . '.name',
                'type' => 'text',
                'label' => __('sArticles::global.author_name') . ' (' . $this->languageLabel($language) . ')',
                'rules' => [$language === $this->defaultLanguage() ? 'required' : 'nullable', 'string', 'max:255'],
                'live' => $language === $this->defaultLanguage(),
            ];
            $dynamicFields[] = [
                'name' => 'translations.' . $language . '.lastname',
                'type' => 'text',
                'label' => __('sArticles::global.author_lastname') . ' (' . $this->languageLabel($language) . ')',
                'rules' => ['nullable', 'string', 'max:255'],
                'live' => $language === $this->defaultLanguage(),
            ];
            $dynamicFields[] = [
                'name' => 'translations.' . $language . '.office',
                'type' => 'text',
                'label' => __('sArticles::global.office') . ' (' . $this->languageLabel($language) . ')',
                'rules' => ['nullable', 'string', 'max:255'],
            ];
        }

        return collect($dynamicFields)
            ->map(function (array $field) {
                if (($field['name'] ?? '') === 'alias') {
                    $field['source'] = [
                        'translations.' . $this->defaultLanguage() . '.name',
                        'translations.' . $this->defaultLanguage() . '.lastname',
                        'name',
                        'lastname',
                    ];
                }

                return $field;
            })
            ->values()
            ->all();
    }

    /**
     * Modal alias for AuthorsTableData.
     *
     * This method keeps the modal alias responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    public function modalAlias(string $source, ?int $authorId = null): string
    {
        return $this->controller->validateAlias($source, (int) $authorId, 'author');
    }

    /**
     * Persist author modal data from the manager.
     *
     * Single-language installs render simple top-level fields (`name`, `lastname`, `office`), while
     * multilingual installs render language-scoped fields under `translations.*`. The save flow must
     * honor the fields the editor actually sees; otherwise stale hidden translation data can win over
     * the edited value and make a successful save look like it was ignored.
     *
     * @param array<string, mixed> $data Submitted evo-ui modal payload.
     * @param int|null $authorId Existing author ID or null when creating a new author.
     * @param string $mode Modal mode supplied by evo-ui (`create` or `edit`).
     * @return int Saved author identifier.
     * @since 2.0.0
     */
    public function saveModal(array $data, ?int $authorId = null, string $mode = 'create'): int
    {
        $author = $authorId ? sArticlesAuthor::find($authorId) : null;

        if (!$author) {
            $author = new sArticlesAuthor();
        }

        $usesLanguageFields = $this->hasLanguageFields();
        $language = $this->defaultLanguage();
        $name = $this->modalAuthorTextValue($data, $language, 'name', $usesLanguageFields);
        $lastname = $this->modalAuthorTextValue($data, $language, 'lastname', $usesLanguageFields);
        $office = $this->modalAuthorTextValue($data, $language, 'office', $usesLanguageFields);
        $alias = trim((string) data_get($data, 'alias', ''));

        $author->image = $this->normalizeImagePath((string) data_get($data, 'image', ''));
        $author->alias = $this->modalAlias($alias !== '' ? $alias : trim($name . ' ' . $lastname), (int) $author->autid);
        $author->gender = $this->normalizeGender((string) data_get($data, 'gender', 'man'));

        foreach ($this->languageCodes() as $lang) {
            $values = [
                'name' => $this->modalAuthorTextValue($data, $lang, 'name', $usesLanguageFields),
                'lastname' => $this->modalAuthorTextValue($data, $lang, 'lastname', $usesLanguageFields),
                'office' => $this->modalAuthorTextValue($data, $lang, 'office', $usesLanguageFields),
            ];

            foreach ($values as $field => $value) {
                $author->{$this->languageAuthorField($lang, $field)} = $value;
            }

            if ($lang === $this->controller->langDefault() || $lang === 'base') {
                $author->base_name = $values['name'] !== '' ? $values['name'] : $name;
                $author->base_lastname = $values['lastname'] !== '' ? $values['lastname'] : $lastname;
                $author->base_office = $values['office'] !== '' ? $values['office'] : $office;
            }
        }

        if (trim((string) $author->base_name) === '') {
            $author->base_name = $name;
            $author->base_lastname = $lastname;
            $author->base_office = $office;
        }

        $author->save();

        return (int) $author->autid;
    }

    /**
     * Resolve an editable author text value from modal payload.
     *
     * In multilingual mode values are read from `translations.{lang}.{field}`. In the default
     * single-language mode evo-ui shows top-level fields only, so those fields must override any
     * stale translation payload that may still be present in Livewire state.
     *
     * @param array<string, mixed> $data Submitted evo-ui modal payload.
     * @param string $language Language code currently being persisted.
     * @param string $field Author text field (`name`, `lastname`, or `office`).
     * @param bool $usesLanguageFields True when multilingual fields are visible in the modal.
     * @return string Trimmed value ready for author storage.
     * @since 2.1.0
     */
    protected function modalAuthorTextValue(array $data, string $language, string $field, bool $usesLanguageFields): string
    {
        if (!$usesLanguageFields && ($language === $this->defaultLanguage() || $language === 'base')) {
            return trim((string) data_get($data, $field, ''));
        }

        return trim((string) data_get($data, 'translations.' . $language . '.' . $field, ''));
    }

    /**
     * Authors query for AuthorsTableData.
     *
     * This method keeps the authors query responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return Builder Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function authorsQuery(): Builder
    {
        $query = sArticlesAuthor::query();

        $this->applySearch($query);
        $this->applyOfficeFilter($query);

        if (!$this->applySort($query)) {
            $query->orderBy($this->authorSortField('name'))->orderBy($this->authorSortField('lastname'));
        }

        return $query->orderBy('autid');
    }

    /**
     * Author rows for AuthorsTableData.
     *
     * This method keeps the author rows responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function authorRows(Collection $authors): array
    {
        return $authors
            ->map(function (sArticlesAuthor $author) {
                $name = $this->authorField($author, 'name');
                $lastname = $this->authorField($author, 'lastname');
                $fullName = trim($name . ' ' . $lastname) ?: __('sArticles::global.no_text');

                $image = [
                    'src' => $this->imageSrc((string) $author->image),
                    'alt' => $fullName,
                ];

                return [
                    'id' => (int) $author->autid,
                    'wire_key' => 'author-row-' . (int) $author->autid,
                    'delete_url' => $this->deleteUrl((int) $author->autid),
                    'delete_name' => $fullName,
                    'image' => $image,
                    'cover' => $image,
                    'name' => $name ?: '-',
                    'lastname' => $lastname ?: '-',
                    'full_name' => $fullName,
                    'alias' => trim((string) $author->alias) ?: '-',
                    'gender' => $this->genderIcon((string) $author->gender),
                    'office' => $this->authorField($author, 'office') ?: '-',
                    'created_at' => $this->formatDate($author->created_at),
                    'updated_at' => $this->formatDate($author->updated_at),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Apply search rules to the current workflow.
     *
     * This method keeps the apply search responsibility inside AuthorsTableData, so callers can
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

        $query->where(function ($where) use ($query, $like) {
            foreach (array_unique([
                $this->authorSortField('name'),
                $this->authorSortField('lastname'),
                $this->authorSortField('office'),
                'base_name',
                'base_lastname',
                'base_office',
                'alias',
                'gender',
            ]) as $field) {
                $where->orWhereRaw($this->likeSql($query, $field), [$like]);
            }

            if (ctype_digit(trim((string) $this->state('search', '')))) {
                $where->orWhere('autid', (int) $this->state('search'));
            }
        });
    }

    /**
     * Apply office filter rules to the current workflow.
     *
     * This method keeps the apply office filter responsibility inside AuthorsTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    protected function applyOfficeFilter(Builder $query): void
    {
        $selected = $this->filterIds('office');

        if ($selected === []) {
            return;
        }

        $offices = collect($this->filterGroups()[0]['items'] ?? [])
            ->filter(fn ($item) => in_array((int) $item['id'], $selected, true))
            ->pluck('label')
            ->values()
            ->all();

        if ($offices !== []) {
            $query->whereIn($this->authorSortField('office'), $offices);
        }
    }

    /**
     * Apply sort rules to the current workflow.
     *
     * This method keeps the apply sort responsibility inside AuthorsTableData, so callers can
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

        $field = (string) ($column['sort_field'] ?? $column['value'] ?? $column['key'] ?? '');

        $field = match ($field) {
            'base_name' => $this->authorSortField('name'),
            'base_lastname' => $this->authorSortField('lastname'),
            'base_office' => $this->authorSortField('office'),
            default => $field,
        };

        if ($field === '') {
            return false;
        }

        $direction = $this->state('direction') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($field, $direction);

        return true;
    }

    /**
     * Like sql for AuthorsTableData.
     *
     * This method keeps the like sql responsibility inside AuthorsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
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
     * Filter ids for AuthorsTableData.
     *
     * This method keeps the filter ids responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
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
     * State for AuthorsTableData.
     *
     * This method keeps the state responsibility inside AuthorsTableData, so callers can rely on
     * a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return mixed Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function state(?string $key = null, mixed $default = null): mixed
    {
        return $key ? data_get($this->state, $key, $default) : $this->state;
    }

    /**
     * Filter state for AuthorsTableData.
     *
     * This method keeps the filter state responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return mixed Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function filterState(string $key, mixed $default = null): mixed
    {
        return data_get($this->state('filters', []), $key, $default);
    }

    /**
     * Author name for AuthorsTableData.
     *
     * This method keeps the author name responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function authorName(int $authorId): string
    {
        $author = sArticlesAuthor::find($authorId);

        if (!$author) {
            return '';
        }

        return trim($this->authorField($author, 'name') . ' ' . $this->authorField($author, 'lastname'));
    }

    /**
     * Delete url data from the manager flow.
     *
     * This method keeps the delete url responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function deleteUrl(int $authorId): string
    {
        return $this->moduleUrl . '&get=authorDelete&i=' . $authorId;
    }

    /**
     * Image src for AuthorsTableData.
     *
     * This method keeps the image src responsibility inside AuthorsTableData, so callers can
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
     * Gender label for AuthorsTableData.
     *
     * This method keeps the gender label responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function genderLabel(string $gender): string
    {
        $gender = mb_strtolower(trim($gender));

        return match ($gender) {
            'woman', 'female', 'f' => __('sArticles::global.gender_woman'),
            'man', 'male', 'm' => __('sArticles::global.gender_man'),
            default => $gender ?: '-',
        };
    }

    /**
     * Gender icon for AuthorsTableData.
     *
     * This method keeps the gender icon responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function genderIcon(string $gender): array
    {
        $gender = $this->normalizeGender($gender);

        return [
            'icon' => match ($gender) {
                'woman' => 'gender-female',
                'man' => 'gender-male',
                default => 'gender-bigender',
            },
            'tone' => match ($gender) {
                'woman' => 'female',
                'man' => 'male',
                default => 'neutral',
            },
            'label' => $this->genderLabel($gender),
        ];
    }

    /**
     * Normalize gender for package-safe usage.
     *
     * This method keeps the normalize gender responsibility inside AuthorsTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function normalizeGender(string $gender): string
    {
        return match (mb_strtolower(trim($gender))) {
            'woman', 'female', 'f' => 'woman',
            default => 'man',
        };
    }

    /**
     * Normalize image path for package-safe usage.
     *
     * This method keeps the normalize image path responsibility inside AuthorsTableData, so
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
     * Default language for AuthorsTableData.
     *
     * This method keeps the default language responsibility inside AuthorsTableData, so callers
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
     * Author field for AuthorsTableData.
     *
     * This method keeps the author field responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function authorField(sArticlesAuthor $author, string $field): string
    {
        $language = $this->defaultLanguage();
        $value = $language !== 'base' ? trim((string) data_get($author, $this->languageAuthorField($language, $field), '')) : '';
        $fallback = trim((string) data_get($author, 'base_' . $field, ''));

        return $value !== '' ? $value : $fallback;
    }

    /**
     * Author sort field for AuthorsTableData.
     *
     * This method keeps the author sort field responsibility inside AuthorsTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function authorSortField(string $field): string
    {
        $language = $this->defaultLanguage();

        return $this->languageAuthorField($language, $field);
    }

    /**
     * Format date for display.
     *
     * This method keeps the format date responsibility inside AuthorsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function formatDate(mixed $value): string
    {
        if (!$value) {
            return '-';
        }

        return Carbon::parse($value)->format('d.m.Y H:i');
    }
}
