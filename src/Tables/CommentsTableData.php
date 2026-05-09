<?php

namespace Seiger\sArticles\Tables;

use Carbon\Carbon;
use EvolutionCMS\Models\UserAttribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Seiger\sArticles\Models\sArticle;
use Seiger\sArticles\Models\sArticleComment;

class CommentsTableData
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
        return (clone $this->commentsQuery())->toBase()->getCountForPagination();
    }

    public function rows(int $page, int $perPage): array
    {
        $comments = $this->commentsQuery()
            ->forPage(max(1, $page), max(1, $perPage))
            ->get();

        return $this->commentRows(
            $comments,
            $this->articleTitles($comments->pluck('article_id')),
            $this->userNames($comments->pluck('user_id'))
        );
    }

    public function filterGroups(): array
    {
        $query = $this->commentsScope();
        $articleIds = (clone $query)
            ->select('s_article_comments.article_id')
            ->distinct()
            ->pluck('s_article_comments.article_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();
        $userIds = (clone $query)
            ->select('s_article_comments.user_id')
            ->distinct()
            ->pluck('s_article_comments.user_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();
        $articleTitles = $this->articleTitles($articleIds);
        $userNames = $this->userNames($userIds);

        return [
            [
                'key' => 'article',
                'items' => $articleIds
                    ->map(fn ($id) => ['id' => $id, 'label' => $articleTitles[$id] ?? ('#' . $id)])
                    ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all(),
            ],
            [
                'key' => 'author',
                'items' => $userIds
                    ->map(fn ($id) => ['id' => $id, 'label' => $userNames[$id] ?? ('#' . $id)])
                    ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all(),
            ],
        ];
    }

    public function deleteName(int $commentId): string
    {
        return $this->commentName($commentId);
    }

    public function deleteRow(int $commentId): void
    {
        if (!isset($_SESSION['mgrValidated'])) {
            return;
        }

        sArticleComment::where('comid', $commentId)->delete();
    }

    public function modalDefaults(): array
    {
        return [
            'comment' => '',
            'approved' => false,
        ];
    }

    public function modalData(int $commentId): array
    {
        $comment = sArticleComment::find($commentId);

        if (!$comment) {
            return $this->modalDefaults();
        }

        return [
            'comment' => (string) $comment->comment,
            'approved' => (bool) $comment->approved,
        ];
    }

    public function saveModal(array $data, ?int $commentId = null, string $mode = 'edit'): int
    {
        if (!isset($_SESSION['mgrValidated']) || !$commentId) {
            return (int) $commentId;
        }

        $comment = sArticleComment::find($commentId);

        if (!$comment) {
            return (int) $commentId;
        }

        $comment->comment = trim((string) data_get($data, 'comment', ''));
        $approved = data_get($data, 'approved', false);
        $comment->approved = in_array($approved, [true, 1, '1', 'true', 'on'], true) ? 1 : 0;
        $comment->save();

        return (int) $comment->comid;
    }

    public function togglePublished(int $commentId): void
    {
        if (!isset($_SESSION['mgrValidated'])) {
            return;
        }

        $comment = sArticleComment::find($commentId);

        if (!$comment) {
            return;
        }

        $comment->approved = $comment->approved ? 0 : 1;
        $comment->save();
    }

    protected function commentsQuery(): Builder
    {
        $query = $this->commentsScope();

        $this->applySearch($query);
        $this->applyAvailability($query);
        $this->applyArticleFilter($query);
        $this->applyAuthorFilter($query);
        $this->applyCreatedDateFilter($query);

        if (!$this->applySort($query)) {
            $query->orderBy('s_article_comments.created_at', 'desc');
        }

        return $query->orderBy('s_article_comments.comid', 'desc');
    }

    protected function commentsScope(): Builder
    {
        return sArticleComment::query()
            ->select('s_article_comments.*')
            ->whereIn(
                's_article_comments.article_id',
                sArticle::withoutGlobalScope('translate')
                    ->select('s_articles.id')
                    ->where('s_articles.type', $this->type)
            );
    }

    protected function commentRows(Collection $comments, array $articleTitles, array $userNames): array
    {
        return $comments
            ->map(function (sArticleComment $comment) use ($articleTitles, $userNames) {
                $id = (int) $comment->comid;
                $articleId = (int) $comment->article_id;
                $text = $this->commentText((string) $comment->comment);
                $articleTitle = $articleTitles[$articleId] ?? ('#' . $articleId);

                return [
                    'id' => $id,
                    'wire_key' => 'comment-row-' . $id,
                    'delete_url' => $this->deleteUrl($id),
                    'delete_name' => $text,
                    'approved' => (bool) $comment->approved,
                    'comment' => $text,
                    'article' => [
                        'label' => $articleTitle,
                        'href' => $this->moduleUrl . '&get=article_comments&type=' . rawurlencode($this->type) . '&i=' . $articleId,
                        'strong' => true,
                    ],
                    'article_title' => $articleTitle,
                    'author' => $userNames[(int) $comment->user_id] ?? ('#' . (int) $comment->user_id),
                    'created_at' => $this->formatDate($comment->created_at),
                ];
            })
            ->values()
            ->all();
    }

    protected function applySearch(Builder $query): void
    {
        $search = trim(strip_tags((string) $this->state('search', '')));

        if ($search === '') {
            return;
        }

        $like = '%' . addcslashes(mb_strtolower($search), '\\%_') . '%';
        $articleIds = $this->articleIdsByTitle($like);
        $userIds = $this->userIdsByName($like);

        $query->where(function ($where) use ($query, $search, $like, $articleIds, $userIds) {
            $where->orWhereRaw($this->likeSql($query, 's_article_comments.comment'), [$like]);

            if ($articleIds !== []) {
                $where->orWhereIn('s_article_comments.article_id', $articleIds);
            }

            if ($userIds !== []) {
                $where->orWhereIn('s_article_comments.user_id', $userIds);
            }

            if (ctype_digit($search)) {
                $where->orWhere('s_article_comments.comid', (int) $search)
                    ->orWhere('s_article_comments.article_id', (int) $search)
                    ->orWhere('s_article_comments.user_id', (int) $search);
            }
        });
    }

    protected function applyAvailability(Builder $query): void
    {
        $availability = (string) $this->filterState('availability', 'all');

        if ($availability === 'published') {
            $query->where('s_article_comments.approved', 1);
        } elseif ($availability === 'unpublished') {
            $query->where('s_article_comments.approved', 0);
        }
    }

    protected function applyArticleFilter(Builder $query): void
    {
        $selected = $this->filterIds('article');

        if ($selected !== []) {
            $query->whereIn('s_article_comments.article_id', $selected);
        }
    }

    protected function applyAuthorFilter(Builder $query): void
    {
        $selected = $this->filterIds('author');

        if ($selected !== []) {
            $query->whereIn('s_article_comments.user_id', $selected);
        }
    }

    protected function applyCreatedDateFilter(Builder $query): void
    {
        $value = (array) $this->filterState('created_at', []);
        $from = $this->normalizeFilterDate((string) ($value['from'] ?? ''));
        $to = $this->normalizeFilterDate((string) ($value['to'] ?? ''));

        if ($from !== '') {
            $query->whereDate('s_article_comments.created_at', '>=', $from);
        }

        if ($to !== '') {
            $query->whereDate('s_article_comments.created_at', '<=', $to);
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

        $field = (string) ($column['sort_field'] ?? $column['key'] ?? '');

        if ($field === '') {
            return false;
        }

        $direction = $this->state('direction') === 'desc' ? 'desc' : 'asc';
        $field = match ($field) {
            'article_title' => 's_article_comments.article_id',
            'author_name' => 's_article_comments.user_id',
            default => $field,
        };

        $query->orderBy($field, $direction);

        return true;
    }

    protected function articleTitles(Collection $ids): array
    {
        $ids = $ids->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $locale = app()->getLocale();

        return DB::table('s_article_translates')
            ->whereIn('article', $ids->all())
            ->whereIn('lang', [$locale, 'base'])
            ->orderByRaw(
                'CASE lang WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END',
                [$locale, 'base']
            )
            ->get()
            ->groupBy('article')
            ->map(fn ($items) => trim((string) data_get($items->first(), 'pagetitle', '')))
            ->filter()
            ->all();
    }

    protected function userNames(Collection $ids): array
    {
        $ids = $ids->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return UserAttribute::query()
            ->whereIn('internalKey', $ids->all())
            ->get()
            ->mapWithKeys(fn (UserAttribute $user) => [
                (int) $user->internalKey => trim((string) $user->fullname) ?: ('#' . (int) $user->internalKey),
            ])
            ->all();
    }

    protected function articleIdsByTitle(string $like): array
    {
        $grammar = DB::connection()->getQueryGrammar();
        $escape = DB::connection()->getDriverName() === 'sqlite' ? '' : " ESCAPE '\\\\'";

        return DB::table('s_article_translates')
            ->whereRaw('LOWER(' . $grammar->wrap('pagetitle') . ') LIKE ?' . $escape, [$like])
            ->distinct()
            ->pluck('article')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    protected function userIdsByName(string $like): array
    {
        $grammar = DB::connection()->getQueryGrammar();
        $escape = DB::connection()->getDriverName() === 'sqlite' ? '' : " ESCAPE '\\\\'";

        return UserAttribute::query()
            ->whereRaw('LOWER(' . $grammar->wrap('fullname') . ') LIKE ?' . $escape, [$like])
            ->pluck('internalKey')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    protected function commentName(int $commentId): string
    {
        $comment = sArticleComment::find($commentId);

        return $comment ? $this->commentText((string) $comment->comment, 72) : (string) $commentId;
    }

    protected function commentText(string $comment, int $limit = 180): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($comment)) ?: '');

        return $text !== '' ? Str::limit($text, $limit) : __('sArticles::global.no_text');
    }

    protected function deleteUrl(int $commentId): string
    {
        return $this->moduleUrl . '&get=commentDelete&type=' . rawurlencode($this->type) . '&i=' . $commentId;
    }

    protected function likeSql(Builder $query, string $field): string
    {
        $sql = 'LOWER(' . $query->getGrammar()->wrap($field) . ') LIKE ?';

        return DB::connection()->getDriverName() === 'sqlite' ? $sql : $sql . " ESCAPE '\\\\'";
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

    protected function state(?string $key = null, mixed $default = null): mixed
    {
        return $key ? data_get($this->state, $key, $default) : $this->state;
    }

    protected function filterState(string $key, mixed $default = null): mixed
    {
        return data_get($this->state('filters', []), $key, $default);
    }
}
