<?php namespace Seiger\sArticles\Tables;

use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Models\SiteTemplate;
use EvolutionCMS\Models\SiteTmplvar;
use EvolutionCMS\Models\SiteTmplvarTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Seiger\sArticles\Models\sArticle;
use Seiger\sArticles\Support\LikeSearch;

/**
 * TvParametersTableData package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class TvParametersTableData
{
    protected string $moduleUrl;
    protected bool $legacyFieldsEnsured = false;

    /**
     * Initialize TvParametersTableData with evo-ui table context.
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
    }

    /**
     * Total for TvParametersTableData.
     *
     * This method keeps the total responsibility inside TvParametersTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    public function total(): int
    {
        $this->ensureLegacyConfigFields();

        return $this->templateId() > 0
            ? (clone $this->tvsQuery())->toBase()->getCountForPagination()
            : 0;
    }

    /**
     * Rows for TvParametersTableData.
     *
     * This method keeps the rows responsibility inside TvParametersTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function rows(int $page, int $perPage): array
    {
        $this->ensureLegacyConfigFields();

        if ($this->templateId() < 1) {
            return [];
        }

        return $this->tvRows(
            $this->tvsQuery()
                ->forPage(max(1, $page), max(1, $perPage))
                ->get()
        );
    }

    /**
     * Filter groups for TvParametersTableData.
     *
     * This method keeps the filter groups responsibility inside TvParametersTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function filterGroups(): array
    {
        return [];
    }

    /**
     * Modal defaults for TvParametersTableData.
     *
     * This method keeps the modal defaults responsibility inside TvParametersTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalDefaults(): array
    {
        return [
            'caption' => '',
            'name' => '',
            'type' => 'text',
            'description' => '',
            'elements' => '',
            'default_text' => '',
            'rank' => $this->nextRank(),
        ];
    }

    /**
     * Modal data for TvParametersTableData.
     *
     * This method keeps the modal data responsibility inside TvParametersTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalData(int $tvId): array
    {
        $tv = $this->findTv($tvId);

        if (!$tv) {
            return $this->modalDefaults();
        }

        return [
            'caption' => (string) $tv->caption,
            'name' => (string) $tv->name,
            'type' => (string) $tv->type,
            'description' => (string) $tv->description,
            'elements' => (string) $tv->elements,
            'default_text' => (string) $tv->default_text,
            'rank' => (int) ($tv->tvrank ?? $tv->rank ?? 0),
        ];
    }

    /**
     * Modal alias for TvParametersTableData.
     *
     * This method keeps the modal alias responsibility inside TvParametersTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    public function modalAlias(string $source, ?int $ignoreId = null): string
    {
        return $this->uniqueTvName($source, $ignoreId);
    }

    /**
     * Persist modal data.
     *
     * This method keeps the save modal responsibility inside TvParametersTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    public function saveModal(array $data, ?int $tvId = null, string $mode = 'create'): int
    {
        $templateId = $this->templateId();

        if ($templateId < 1) {
            return 0;
        }

        $caption = trim((string) data_get($data, 'caption', ''));
        $name = trim((string) data_get($data, 'name', ''));

        if ($caption === '') {
            $caption = __('sArticles::global.new_field');
        }

        $name = $this->uniqueTvName($name !== '' ? $name : $caption, $tvId);
        $type = $this->normalizeType((string) data_get($data, 'type', 'text'));
        $rank = max(0, (int) data_get($data, 'rank', $this->nextRank()));
        $tv = $mode === 'edit' && $tvId ? $this->findTv($tvId) : null;

        if (!$tv) {
            $tv = new SiteTmplvar();
            $tv->rank = $rank;
        }

        $tv->fill([
            'type' => $type,
            'name' => $name,
            'caption' => $caption,
            'description' => trim((string) data_get($data, 'description', '')),
            'elements' => trim((string) data_get($data, 'elements', '')),
            'default_text' => trim((string) data_get($data, 'default_text', '')),
            'display' => 'default',
            'display_params' => '',
            'properties' => '',
        ]);
        $tv->rank = $rank;
        $tv->save();

        DB::table('site_tmplvar_templates')->updateOrInsert(
            ['tmplvarid' => (int) $tv->id, 'templateid' => $templateId],
            ['rank' => $rank]
        );

        $this->normalizeRanks();

        return (int) $tv->id;
    }

    /**
     * Delete name data from the manager flow.
     *
     * This method keeps the delete name responsibility inside TvParametersTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    public function deleteName(int $tvId): string
    {
        $tv = $this->findTv($tvId);

        if (!$tv) {
            return '';
        }

        return trim((string) $tv->caption) ?: (string) $tv->name;
    }

    /**
     * Delete row data from the manager flow.
     *
     * This method keeps the delete row responsibility inside TvParametersTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function deleteRow(int $tvId): void
    {
        $tv = $this->findTv($tvId);

        if (!$tv) {
            return;
        }

        $templateId = $this->templateId();
        $name = (string) $tv->name;
        $links = SiteTmplvarTemplate::query()->where('tmplvarid', $tvId)->count();

        if ($links <= 1) {
            $tv->delete();
        } else {
            SiteTmplvarTemplate::query()
                ->where('tmplvarid', $tvId)
                ->where('templateid', $templateId)
                ->delete();
        }

        $this->removeArticleTvValues($name);
        $this->normalizeRanks();
    }

    /**
     * Move row for TvParametersTableData.
     *
     * This method keeps the move row responsibility inside TvParametersTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function moveRow(int $tvId, string $direction = 'up'): void
    {
        $ordered = $this->orderedIds();
        $index = array_search($tvId, $ordered, true);

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
     * Reorder row for TvParametersTableData.
     *
     * This method keeps the reorder row responsibility inside TvParametersTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function reorderRow(int $tvId, int $targetId, string $placement = 'before'): void
    {
        if ($tvId === $targetId) {
            return;
        }

        $ordered = collect($this->orderedIds())
            ->reject(fn (int $id) => $id === $tvId)
            ->values()
            ->all();
        $targetIndex = array_search($targetId, $ordered, true);

        if ($targetIndex === false) {
            return;
        }

        $insertAt = $placement === 'after' ? $targetIndex + 1 : $targetIndex;
        array_splice($ordered, $insertAt, 0, [$tvId]);
        $this->applyOrder($ordered);
    }

    /**
     * Tvs query for TvParametersTableData.
     *
     * This method keeps the tvs query responsibility inside TvParametersTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return Builder Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function tvsQuery(): Builder
    {
        $query = $this->baseTvsQuery();

        $this->applySearch($query);

        if (!$this->applySort($query)) {
            $query->orderBy('site_tmplvar_templates.rank')
                ->orderBy('site_tmplvars.rank')
                ->orderBy('site_tmplvars.id');
        }

        return $query;
    }

    /**
     * Base tvs query for TvParametersTableData.
     *
     * This method keeps the base tvs query responsibility inside TvParametersTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return Builder Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function baseTvsQuery(): Builder
    {
        return SiteTmplvar::query()
            ->select('site_tmplvars.*', 'site_tmplvar_templates.rank as tvrank')
            ->join('site_tmplvar_templates', 'site_tmplvar_templates.tmplvarid', '=', 'site_tmplvars.id')
            ->where('site_tmplvar_templates.templateid', $this->templateId())
            ->whereNotIn('site_tmplvars.name', ['menu_footer', 'menu_main']);
    }

    /**
     * Tv rows for TvParametersTableData.
     *
     * This method keeps the tv rows responsibility inside TvParametersTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function tvRows(Collection $tvs): array
    {
        return $tvs
            ->map(fn (SiteTmplvar $tv) => [
                'id' => (int) $tv->id,
                'wire_key' => 'tv-row-' . (int) $tv->id,
                'delete_name' => trim((string) $tv->caption) ?: (string) $tv->name,
                'caption' => trim((string) $tv->caption) ?: (string) $tv->name,
                'name' => (string) $tv->name,
                'type' => (string) $tv->type,
                'description' => (string) $tv->description,
                'default_text' => (string) $tv->default_text,
                'position' => (int) ($tv->tvrank ?? $tv->rank ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * Apply search rules to the current workflow.
     *
     * This method keeps the apply search responsibility inside TvParametersTableData, so callers
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

        $like = LikeSearch::needle(mb_strtolower($search));

        $query->where(function ($where) use ($query, $like, $search) {
            $where->orWhereRaw($this->likeSql($query, 'site_tmplvars.name'), [$like])
                ->orWhereRaw($this->likeSql($query, 'site_tmplvars.caption'), [$like])
                ->orWhereRaw($this->likeSql($query, 'site_tmplvars.description'), [$like])
                ->orWhereRaw($this->likeSql($query, 'site_tmplvars.type'), [$like]);

            if (ctype_digit($search)) {
                $where->orWhere('site_tmplvars.id', (int) $search);
            }
        });
    }

    /**
     * Apply sort rules to the current workflow.
     *
     * This method keeps the apply sort responsibility inside TvParametersTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
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

        if ($field === '') {
            return false;
        }

        $direction = $this->state('direction') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($field, $direction);

        return true;
    }

    /**
     * Find tv for TvParametersTableData.
     *
     * This method keeps the find tv responsibility inside TvParametersTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return ?SiteTmplvar Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function findTv(int $tvId): ?SiteTmplvar
    {
        if ($this->templateId() < 1) {
            return null;
        }

        return $this->baseTvsQuery()
            ->where('site_tmplvars.id', $tvId)
            ->first();
    }

    /**
     * Template id for TvParametersTableData.
     *
     * This method keeps the template id responsibility inside TvParametersTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    protected function templateId(): int
    {
        static $templateId;

        if ($templateId !== null) {
            return $templateId;
        }

        $blankId = (int) evo()->getConfig('sart_blank', 0);
        $templateId = $blankId > 0
            ? (int) (SiteContent::find($blankId)->template ?? 0)
            : 0;

        if ($templateId > 0) {
            return $templateId;
        }

        $type = trim((string) ($this->context['type'] ?? 'article'));
        $templateId = (int) SiteTemplate::query()
            ->where('templatename', 'sArticles ' . Str::studly($type))
            ->value('id');

        if ($templateId > 0) {
            return $templateId;
        }

        $templateId = (int) SiteTemplate::query()
            ->where('templatename', 'like', 'sArticles%')
            ->orderBy('id')
            ->value('id');

        return $templateId;
    }

    /**
     * Unique tv name for TvParametersTableData.
     *
     * This method keeps the unique tv name responsibility inside TvParametersTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function uniqueTvName(string $source, ?int $ignoreId = null): string
    {
        $base = $this->normalizeTvName($source);
        $name = $base;
        $suffix = 2;

        while (
            SiteTmplvar::query()
                ->where('name', $name)
                ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->exists()
        ) {
            $tail = '_' . $suffix++;
            $name = substr($base, 0, 50 - strlen($tail)) . $tail;
        }

        return $name;
    }

    /**
     * Normalize type for package-safe usage.
     *
     * This method keeps the normalize type responsibility inside TvParametersTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function normalizeType(string $type): string
    {
        $allowed = ['text', 'textarea', 'richtext', 'image', 'file', 'dropdown', 'listbox', 'listbox-multiple', 'checkbox'];

        return in_array($type, $allowed, true) ? $type : 'text';
    }

    /**
     * Next rank for TvParametersTableData.
     *
     * This method keeps the next rank responsibility inside TvParametersTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    protected function nextRank(): int
    {
        $templateId = $this->templateId();

        if ($templateId < 1) {
            return 0;
        }

        return ((int) SiteTmplvarTemplate::query()->where('templateid', $templateId)->max('rank')) + 1;
    }

    /**
     * Ordered ids for TvParametersTableData.
     *
     * This method keeps the ordered ids responsibility inside TvParametersTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function orderedIds(): array
    {
        if ($this->templateId() < 1) {
            return [];
        }

        return $this->baseTvsQuery()
            ->orderBy('site_tmplvar_templates.rank')
            ->orderBy('site_tmplvars.rank')
            ->orderBy('site_tmplvars.id')
            ->pluck('site_tmplvars.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Apply order rules to the current workflow.
     *
     * This method keeps the apply order responsibility inside TvParametersTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    protected function applyOrder(array $ordered): void
    {
        $templateId = $this->templateId();

        foreach (array_values($ordered) as $rank => $id) {
            SiteTmplvarTemplate::query()
                ->where('templateid', $templateId)
                ->where('tmplvarid', $id)
                ->update(['rank' => $rank]);

            SiteTmplvar::query()->where('id', $id)->update(['rank' => $rank]);
        }
    }

    /**
     * Normalize ranks for package-safe usage.
     *
     * This method keeps the normalize ranks responsibility inside TvParametersTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    protected function normalizeRanks(): void
    {
        $this->applyOrder($this->orderedIds());
    }

    /**
     * Remove article tv values for TvParametersTableData.
     *
     * This method keeps the remove article tv values responsibility inside
     * TvParametersTableData, so callers can rely on a stable package boundary while the manager
     * UI, frontend runtime, or legacy storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    protected function removeArticleTvValues(string $name): void
    {
        if ($name === '') {
            return;
        }

        sArticle::query()
            ->whereNotNull('tmplvars')
            ->chunkById(100, function ($articles) use ($name) {
                foreach ($articles as $article) {
                    $values = data_is_json($article->tmplvars ?? '', true) ?? [];

                    if (!is_array($values) || !array_key_exists($name, $values)) {
                        continue;
                    }

                    unset($values[$name]);
                    $article->tmplvars = json_encode($values);
                    $article->save();
                }
            });
    }

    /**
     * Ensure legacy config fields for TvParametersTableData.
     *
     * This method keeps the ensure legacy config fields responsibility inside
     * TvParametersTableData, so callers can rely on a stable package boundary while the manager
     * UI, frontend runtime, or legacy storage details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    protected function ensureLegacyConfigFields(): void
    {
        if ($this->legacyFieldsEnsured || $this->templateId() < 1) {
            return;
        }

        $this->legacyFieldsEnsured = true;
        $settings = config('seiger.settings.sArticles', []);

        if (!is_array($settings)) {
            return;
        }

        foreach ($settings as $key => $setting) {
            if (in_array($key, ['general', 'types'], true) || !is_array($setting)) {
                continue;
            }

            $name = $this->normalizeTvKey((string) ($setting['key'] ?? $key));
            $existing = SiteTmplvar::query()->where('name', $name)->first();

            if (!$existing) {
                $name = $this->uniqueTvName($name);
                $existing = SiteTmplvar::query()->create([
                    'type' => $this->legacyType((string) ($setting['type'] ?? 'Text')),
                    'name' => $name,
                    'caption' => (string) ($setting['name'] ?? $name),
                    'description' => '',
                    'elements' => '',
                    'rank' => $this->nextRank(),
                    'display' => 'default',
                    'display_params' => '',
                    'default_text' => '',
                    'properties' => '',
                ]);
            }

            DB::table('site_tmplvar_templates')->updateOrInsert(
                ['tmplvarid' => (int) $existing->id, 'templateid' => $this->templateId()],
                ['rank' => (int) ($existing->rank ?? $this->nextRank())]
            );
        }

        $this->normalizeRanks();
    }

    /**
     * Legacy type for TvParametersTableData.
     *
     * This method keeps the legacy type responsibility inside TvParametersTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function legacyType(string $type): string
    {
        return match (Str::lower($type)) {
            'textarea' => 'textarea',
            'richtext' => 'richtext',
            'file' => 'file',
            'image' => 'image',
            default => 'text',
        };
    }

    /**
     * Normalize tv key for package-safe usage.
     *
     * This method keeps the normalize tv key responsibility inside TvParametersTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function normalizeTvKey(string $source): string
    {
        return $this->normalizeTvName($source);
    }

    /**
     * Normalize tv name for package-safe usage.
     *
     * This method keeps the normalize tv name responsibility inside TvParametersTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function normalizeTvName(string $source): string
    {
        $name = trim(substr(Str::slug(Str::ascii($source), '_'), 0, 50), '_');

        return $name !== '' ? $name : 'blog_tv';
    }

    /**
     * Like sql for TvParametersTableData.
     *
     * This method keeps the like sql responsibility inside TvParametersTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function likeSql(Builder $query, string $field): string
    {
        $driver = $query->getConnection()->getDriverName();
        $wrapped = str_contains($field, '.')
            ? collect(explode('.', $field))->map(fn ($part) => $query->getGrammar()->wrap($part))->implode('.')
            : $query->getGrammar()->wrap($field);

        $sql = $driver === 'pgsql'
            ? 'LOWER(' . $wrapped . '::text) LIKE ?'
            : 'LOWER(' . $wrapped . ') LIKE ?';

        return $sql . " ESCAPE '!'";
    }

    /**
     * State for TvParametersTableData.
     *
     * This method keeps the state responsibility inside TvParametersTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return mixed Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function state(string $key, mixed $default = null): mixed
    {
        return data_get($this->state, $key, $default);
    }
}
