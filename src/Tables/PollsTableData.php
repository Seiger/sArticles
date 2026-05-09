<?php namespace Seiger\sArticles\Tables;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticlesPoll;
use Seiger\sArticles\Tables\Concerns\HandlesLanguageFields;

/**
 * PollsTableData package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class PollsTableData
{
    use HandlesLanguageFields;

    protected string $moduleUrl;
    protected sArticlesController $controller;

    /**
     * Initialize PollsTableData with evo-ui table context.
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
    }

    /**
     * Total for PollsTableData.
     *
     * This method keeps the total responsibility inside PollsTableData, so callers can rely on a
     * stable package boundary while the manager UI, frontend runtime, or legacy storage details
     * evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    public function total(): int
    {
        return $this->pollsCollection()->count();
    }

    /**
     * Rows for PollsTableData.
     *
     * This method keeps the rows responsibility inside PollsTableData, so callers can rely on a
     * stable package boundary while the manager UI, frontend runtime, or legacy storage details
     * evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function rows(int $page, int $perPage): array
    {
        return $this->pollRows(
            $this->pollsCollection()
                ->slice((max(1, $page) - 1) * max(1, $perPage), max(1, $perPage))
                ->values()
        );
    }

    /**
     * Filter groups for PollsTableData.
     *
     * This method keeps the filter groups responsibility inside PollsTableData, so callers can
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
     * This method keeps the delete name responsibility inside PollsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    public function deleteName(int $pollId): string
    {
        $poll = sArticlesPoll::find($pollId);

        return $poll ? $this->questionText($this->jsonArray($poll->question)) : (string) $pollId;
    }

    /**
     * Delete row data from the manager flow.
     *
     * This method keeps the delete row responsibility inside PollsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function deleteRow(int $pollId): void
    {
        if (!isset($_SESSION['mgrValidated'])) {
            return;
        }

        sArticlesPoll::where('pollid', $pollId)->delete();
        Cache::forget('sArticles-polls-list');
    }

    /**
     * Modal defaults for PollsTableData.
     *
     * This method keeps the modal defaults responsibility inside PollsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalDefaults(): array
    {
        $answer = ['answer' => '', 'votes' => 0];

        foreach ($this->languageCodes() as $language) {
            $answer['translations'][$language]['answer'] = '';
        }

        $defaults = [
            'question' => '',
            'answers' => [$answer, $answer],
        ];

        foreach ($this->languageCodes() as $language) {
            $defaults['translations'][$language]['question'] = '';
        }

        return $defaults;
    }

    /**
     * Modal data for PollsTableData.
     *
     * This method keeps the modal data responsibility inside PollsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalData(int $pollId): array
    {
        $poll = sArticlesPoll::find($pollId);

        if (!$poll) {
            return $this->modalDefaults();
        }

        $answers = $this->answersWithVotes(
            $this->jsonArray($poll->answers),
            $this->jsonArray($poll->votes)
        );

        $data = [
            'question' => $this->translatedValue($this->jsonArray($poll->question)),
            'answers' => $answers !== [] ? $answers : $this->modalDefaults()['answers'],
        ];

        $question = $this->jsonArray($poll->question);
        foreach ($this->languageCodes() as $language) {
            $value = (string) ($question[$language] ?? '');
            $data['translations'][$language]['question'] = $value !== '' || $language !== $this->defaultLanguage()
                ? $value
                : (string) ($question['base'] ?? '');
        }

        foreach ($data['answers'] as $index => $answer) {
            $source = (array) ($this->jsonArray($poll->answers)[$index] ?? []);

            foreach ($this->languageCodes() as $language) {
                $value = (string) ($source[$language] ?? '');
                $data['answers'][$index]['translations'][$language]['answer'] = $value !== '' || $language !== $this->defaultLanguage()
                    ? $value
                    : (string) ($source['base'] ?? '');
            }
        }

        return $data;
    }

    /**
     * Modal fields for PollsTableData.
     *
     * This method keeps the modal fields responsibility inside PollsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    public function modalFields(array $fields, array $data = [], ?int $pollId = null, string $mode = 'create'): array
    {
        if (!$this->hasLanguageFields()) {
            return $fields;
        }

        $dynamicFields = [];

        foreach ($this->languageCodes() as $language) {
            $dynamicFields[] = [
                'name' => 'translations.' . $language . '.question',
                'type' => 'text',
                'label' => __('sArticles::global.question') . ' (' . $this->languageLabel($language) . ')',
                'rules' => [$language === $this->defaultLanguage() ? 'required' : 'nullable', 'string', 'max:255'],
                'span' => 'full',
                'live' => $language === $this->defaultLanguage(),
            ];
        }

        $answerFields = collect($this->languageCodes())
            ->map(fn (string $language) => [
                'name' => 'translations.' . $language . '.answer',
                'type' => 'text',
                'label' => __('sArticles::global.answer') . ' (' . $this->languageLabel($language) . ')',
                'placeholder' => __('sArticles::global.answer') . ' (' . $this->languageLabel($language) . ')',
                'span' => 'grow',
                'hide_label' => true,
                'rules' => [$language === $this->defaultLanguage() ? 'required' : 'nullable', 'string', 'max:255'],
            ])
            ->push([
                'name' => 'votes',
                'type' => 'number',
                'label' => 'sArticles::global.votes',
                'placeholder' => 'sArticles::global.votes',
                'span' => 'compact',
                'icon' => 'chart-bar',
                'hide_label' => true,
                'rules' => ['nullable', 'integer', 'min:0'],
                'min' => 0,
            ])
            ->values()
            ->all();

        $defaultAnswer = ['answer' => '', 'votes' => 0];
        foreach ($this->languageCodes() as $language) {
            $defaultAnswer['translations'][$language]['answer'] = '';
        }

        $dynamicFields[] = [
            'name' => 'answers',
            'type' => 'repeater',
            'label' => 'sArticles::global.answers',
            'hint' => 'sArticles::global.poll_answers_help',
            'add_label' => 'sArticles::global.add_answer',
            'span' => 'full',
            'layout' => 'compact',
            'rules' => ['array', 'min:1'],
            'default_item' => $defaultAnswer,
            'fields' => $answerFields,
        ];

        return $dynamicFields;
    }

    /**
     * Persist modal data.
     *
     * This method keeps the save modal responsibility inside PollsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    public function saveModal(array $data, ?int $pollId = null, string $mode = 'create'): int
    {
        if (!isset($_SESSION['mgrValidated'])) {
            return (int) $pollId;
        }

        $poll = $pollId ? sArticlesPoll::find($pollId) : null;

        if (!$poll) {
            $poll = new sArticlesPoll();
        }

        $language = $this->defaultLanguage();
        $question = trim((string) data_get($data, 'translations.' . $language . '.question', data_get($data, 'question', '')));

        if ($question === '') {
            $question = __('sArticles::global.no_text');
        }

        $normalizedAnswers = [];
        $normalizedVotes = ['total' => 0];

        foreach (array_values((array) data_get($data, 'answers', [])) as $item) {
            $answerText = trim((string) data_get($item, 'translations.' . $language . '.answer', data_get($item, 'answer', '')));

            if ($answerText === '') {
                continue;
            }

            $index = count($normalizedAnswers);
            $votes = max(0, (int) data_get($item, 'votes', 0));
            $answer = [];

            foreach ($this->languageCodes() as $lang) {
                $value = trim((string) data_get($item, 'translations.' . $lang . '.answer', ''));
                $answer[$lang] = $value;

                if ($lang === $this->controller->langDefault() || $lang === 'base') {
                    $answer['base'] = $value !== '' ? $value : $answerText;
                }
            }

            if (!isset($answer['base']) || trim((string) $answer['base']) === '') {
                $answer['base'] = $answerText;
            }

            $normalizedAnswers[$index] = $answer;
            $normalizedVotes[(string) $index] = $votes;
            $normalizedVotes['total'] += $votes;
        }

        if ($normalizedAnswers === []) {
            $normalizedAnswers[] = ['base' => __('sArticles::global.answer')];
            $normalizedVotes['0'] = 0;
        }

        $questionValue = [];

        foreach ($this->languageCodes() as $lang) {
            $value = trim((string) data_get($data, 'translations.' . $lang . '.question', ''));
            $questionValue[$lang] = $value;

            if ($lang === $this->controller->langDefault() || $lang === 'base') {
                $questionValue['base'] = $value !== '' ? $value : $question;
            }
        }

        if (!isset($questionValue['base']) || trim((string) $questionValue['base']) === '') {
            $questionValue['base'] = $question;
        }

        $poll->question = $questionValue;
        $poll->answers = json_encode($normalizedAnswers, JSON_UNESCAPED_UNICODE);
        $poll->votes = json_encode($normalizedVotes, JSON_UNESCAPED_UNICODE);
        $poll->save();
        Cache::forget('sArticles-polls-list');

        return (int) $poll->pollid;
    }

    /**
     * Polls collection for PollsTableData.
     *
     * This method keeps the polls collection responsibility inside PollsTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return Collection Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function pollsCollection(): Collection
    {
        $polls = sArticlesPoll::query()->get();
        $polls = $this->applySearch($polls);
        $polls = $this->applyCreatedDateFilter($polls);

        return $this->sortPolls($polls)->values();
    }

    /**
     * Poll rows for PollsTableData.
     *
     * This method keeps the poll rows responsibility inside PollsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function pollRows(Collection $polls): array
    {
        return $polls
            ->map(function (sArticlesPoll $poll) {
                $id = (int) $poll->pollid;
                $answers = $this->answersWithVotes($this->jsonArray($poll->answers), $this->jsonArray($poll->votes));
                $answerLabels = collect($answers)->pluck('answer')->filter()->values();
                $answerChips = collect($answers)
                    ->filter(fn (array $answer) => trim((string) ($answer['answer'] ?? '')) !== '')
                    ->map(fn (array $answer) => [
                        'label' => (string) $answer['answer'],
                        'badge' => (int) ($answer['votes'] ?? 0),
                    ])
                    ->values();
                $votes = $this->totalVotes($this->jsonArray($poll->votes));

                return [
                    'id' => $id,
                    'wire_key' => 'poll-row-' . $id,
                    'delete_url' => $this->deleteUrl($id),
                    'delete_name' => $this->questionText($this->jsonArray($poll->question)),
                    'question' => $this->questionText($this->jsonArray($poll->question)),
                    'answers' => $answerChips->take(4)->all(),
                    'answer_summary' => $answerLabels->isNotEmpty()
                        ? $answerLabels->take(3)->implode(', ')
                        : __('sArticles::global.no_text'),
                    'answers_count' => $answerLabels->count(),
                    'votes' => $votes,
                    'created_at' => $this->formatDate($poll->created_at),
                    'updated_at' => $this->formatDate($poll->updated_at),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Apply search rules to the current workflow.
     *
     * This method keeps the apply search responsibility inside PollsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return Collection Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function applySearch(Collection $polls): Collection
    {
        $search = mb_strtolower(trim((string) $this->state('search', '')));

        if ($search === '') {
            return $polls;
        }

        return $polls
            ->filter(function (sArticlesPoll $poll) use ($search) {
                $haystack = mb_strtolower(implode(' ', array_merge(
                    [$this->questionText($this->jsonArray($poll->question)), (string) $poll->pollid],
                    collect($this->answersWithVotes($this->jsonArray($poll->answers), $this->jsonArray($poll->votes)))
                        ->pluck('answer')
                        ->all()
                )));

                return str_contains($haystack, $search);
            })
            ->values();
    }

    /**
     * Apply created date filter rules to the current workflow.
     *
     * This method keeps the apply created date filter responsibility inside PollsTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return Collection Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function applyCreatedDateFilter(Collection $polls): Collection
    {
        $value = (array) $this->filterState('created_at', []);
        $from = $this->normalizeFilterDate((string) ($value['from'] ?? ''));
        $to = $this->normalizeFilterDate((string) ($value['to'] ?? ''));

        if ($from === '' && $to === '') {
            return $polls;
        }

        return $polls
            ->filter(function (sArticlesPoll $poll) use ($from, $to) {
                if (!$poll->created_at) {
                    return false;
                }

                $date = Carbon::parse($poll->created_at)->format('Y-m-d');

                if ($from !== '' && $date < $from) {
                    return false;
                }

                if ($to !== '' && $date > $to) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    /**
     * Sort polls for PollsTableData.
     *
     * This method keeps the sort polls responsibility inside PollsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return Collection Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function sortPolls(Collection $polls): Collection
    {
        $key = (string) $this->state('sort', '');
        $column = collect($this->config['columns'] ?? [])
            ->first(fn ($column) => ($column['key'] ?? null) === $key && ($column['sortable'] ?? false));
        $field = is_array($column) ? (string) ($column['sort_field'] ?? $key) : '';

        if ($field === '') {
            $field = 'updated_at';
        }

        $direction = $this->state('direction') === 'asc' ? 'asc' : 'desc';
        $sorted = $polls->sortBy(fn (sArticlesPoll $poll) => $this->sortValue($poll, $field), SORT_NATURAL | SORT_FLAG_CASE, $direction === 'desc');

        return $sorted->values();
    }

    /**
     * Sort value for PollsTableData.
     *
     * This method keeps the sort value responsibility inside PollsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return mixed Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function sortValue(sArticlesPoll $poll, string $field): mixed
    {
        return match ($field) {
            'pollid' => (int) $poll->pollid,
            'question' => $this->questionText($this->jsonArray($poll->question)),
            'answers_count' => count($this->answersWithVotes($this->jsonArray($poll->answers), $this->jsonArray($poll->votes))),
            'votes' => $this->totalVotes($this->jsonArray($poll->votes)),
            'created_at' => $poll->created_at ? Carbon::parse($poll->created_at)->timestamp : 0,
            'updated_at' => $poll->updated_at ? Carbon::parse($poll->updated_at)->timestamp : 0,
            default => data_get($poll, $field),
        };
    }

    /**
     * Answers with votes for PollsTableData.
     *
     * This method keeps the answers with votes responsibility inside PollsTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function answersWithVotes(array $answers, array $votes): array
    {
        return collect($answers)
            ->map(function ($answer, $key) use ($votes) {
                $text = $this->translatedValue((array) $answer);

                if ($text === '') {
                    return null;
                }

                return [
                    'answer' => $text,
                    'votes' => (int) ($votes[(string) $key] ?? $votes[$key] ?? 0),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Question text for PollsTableData.
     *
     * This method keeps the question text responsibility inside PollsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function questionText(array $question): string
    {
        $text = $this->translatedValue($question);

        return $text !== '' ? Str::limit($text, 120) : __('sArticles::global.no_text');
    }

    /**
     * Translated value for PollsTableData.
     *
     * This method keeps the translated value responsibility inside PollsTableData, so callers
     * can rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function translatedValue(array $value): string
    {
        $language = $this->defaultLanguage();

        return trim((string) ($value[$language] ?? $value['base'] ?? reset($value) ?: ''));
    }

    /**
     * Total votes for PollsTableData.
     *
     * This method keeps the total votes responsibility inside PollsTableData, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return int Count, identifier, position, or status value for the package workflow.
     * @since 2.0.0
     */
    protected function totalVotes(array $votes): int
    {
        if (array_key_exists('total', $votes)) {
            return (int) $votes['total'];
        }

        return collect($votes)->sum(fn ($value) => (int) $value);
    }

    /**
     * Json array for PollsTableData.
     *
     * This method keeps the json array responsibility inside PollsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && function_exists('data_is_json')) {
            $decoded = data_is_json($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Delete url data from the manager flow.
     *
     * This method keeps the delete url responsibility inside PollsTableData, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function deleteUrl(int $pollId): string
    {
        return $this->moduleUrl . '&get=pollDelete&i=' . $pollId;
    }

    /**
     * Format date for display.
     *
     * This method keeps the format date responsibility inside PollsTableData, so callers can
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

    /**
     * Normalize filter date for package-safe usage.
     *
     * This method keeps the normalize filter date responsibility inside PollsTableData, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function normalizeFilterDate(string $value): string
    {
        $value = trim($value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    /**
     * Default language for PollsTableData.
     *
     * This method keeps the default language responsibility inside PollsTableData, so callers
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
     * State for PollsTableData.
     *
     * This method keeps the state responsibility inside PollsTableData, so callers can rely on a
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

    /**
     * Filter state for PollsTableData.
     *
     * This method keeps the filter state responsibility inside PollsTableData, so callers can
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
}
