<?php namespace Seiger\sArticles;

use Illuminate\Support\Arr;
use EvolutionCMS\Models\UserAttribute;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticle;
use Seiger\sArticles\Models\sArticleComment;
use Seiger\sArticles\Models\sArticlesPoll;

class sArticles
{
    public $url = '';

    public function __construct()
    {
        if (IN_MANAGER_MODE) {
            Paginator::defaultView('sArticles::partials.pagination');
            $this->url = $this->moduleUrl();
        } else {
            Paginator::defaultView('partials.pagination');
        }
    }

    /**
     * Get all offers
     *
     * @return object
     */
    public function all($paginate = 30): object
    {
        $order = 's_articles.published_at';
        $direc = 'desc';
        $query = sArticle::search()
            ->with(['categories', 'tags', 'features'])
            ->orderBy($order, $direc);
        if (!IN_MANAGER_MODE) {
            $query->active();
        } else {
            $availability = request()->input('availability', '');

            if ($availability === 'published') {
                $query->where('s_articles.published', 1);
            } elseif ($availability === 'unpublished') {
                $query->where('s_articles.published', 0);
            }
        }
        if (request()->has('type') && trim(request()->input('type', ''))) {
            $query->whereType(request()->input('type', ''));
        }
        $sections = $this->filterIds('section');
        $categories = $this->filterIds('category');
        $tags = $this->filterIds('tag');
        $features = $this->filterIds('feature');

        if (IN_MANAGER_MODE && count($sections)) {
            $query->where(function ($sectionsQuery) use ($sections) {
                if (in_array(1, $sections, true)) {
                    $sectionsQuery->orWhere('s_articles.parent', '<=', 1);
                }

                $parentIds = array_values(array_filter($sections, fn($id) => $id > 1));
                if (count($parentIds)) {
                    $sectionsQuery->orWhereIn('s_articles.parent', $parentIds);
                }
            });
        }
        if (IN_MANAGER_MODE && count($categories)) {
            $query->whereHas('categories', fn($categoriesQuery) => $categoriesQuery->whereIn('s_articles_categories.catid', $categories));
        }
        if (IN_MANAGER_MODE && count($tags)) {
            $query->whereHas('tags', fn($tagsQuery) => $tagsQuery->whereIn('s_articles_tags.tagid', $tags));
        }
        if (IN_MANAGER_MODE && count($features)) {
            $query->whereHas('features', fn($featuresQuery) => $featuresQuery->whereIn('s_articles_features.fid', $features));
        }
        $articles = $query->paginate($paginate);
        return $articles;
    }

    /**
     * Read comma separated manager filter ids from request.
     */
    protected function filterIds(string $key): array
    {
        $value = request()->input($key, '');
        $values = is_array($value) ? $value : explode(',', (string) $value);

        return collect($values)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     *  Get all comments
     *
     *  @return object
     */
    public function comments($paginate = 30, $artids = [])
    {
        $order = 's_article_comments.created_at';
        $direc = 'desc';
        $query = sArticleComment::orderBy($order, $direc);
        if ($artids)
        {
            $query->whereIn('article_id', $artids);
        }
        if (request()->has('search') && trim(request()->input('search', '')) !== '') {
            $search = '%' . addcslashes(mb_strtolower(trim(strip_tags(request()->input('search', '')))), '\\%_') . '%';
            $query->whereRaw("LOWER(`comment`) LIKE ? ESCAPE '\\\\'", [$search]);
        }
        return $query->paginate($paginate);
    }

    /**
     * Get article object with translation by ID
     *
     * @param int $articleId
     * @return object
     */
    public function getArticle(int $articleId): object
    {
        return sArticle::where('s_articles.id', $articleId)->first() ?? new sArticle();
    }

    /**
     * Get article object with translation by Alias
     *
     * @param string $articleAlias
     * @return object
     */
    public function getArticleByAlias(string $articleAlias): object
    {
        return sArticle::where('s_articles.alias', $articleAlias)->first() ?? new sArticle();
    }

    /**
     * Determine whether the package should use legacy blank resource mode.
     *
     * @return bool
     */
    public function isLegacyMode(): bool
    {
        return (int)evo()->getConfig('sart_blank', 1) > 1;
    }

    /**
     * Resolve article by request URI segments.
     *
     * @param array $segments
     * @return sArticle|null
     */
    public function resolveArticleByUri(array $segments): ?sArticle
    {
        if (isset($segments[0]) && $segments[0] === evo()->getConfig('lang', 'uk')) {
            unset($segments[0]);
        }

        $segments = array_values($segments);
        $alias = implode('/', $segments);
        $articleId = sArticles::documentListing()[$alias] ?? 0;

        if ($articleId > 0) {
            $article = sArticles::getArticle((int)$articleId);
            if ($article->id ?? 0) {
                return $article;
            }
        }

        $articleAlias = Arr::last($segments);
        if (!$articleAlias) {
            return null;
        }

        $article = sArticles::getArticleByAlias($articleAlias);
        if (!($article->id ?? 0)) {
            return null;
        }

        if (
            trim($article->link, '/') === trim($alias, '/') ||
            trim($article->link, '/') === trim('/' . evo()->getConfig('lang', 'uk') . '/' . $alias, '/')
        ) {
            return $article;
        }

        return null;
    }

    /**
     * Increment article views once per session.
     *
     * @param sArticle $article
     * @return void
     */
    public function trackView(sArticle $article): void
    {
        if (sArticles::config('general.views_on', 1) == 1) {
            if (!in_array($article->id, $_SESSION['s_articles_article_views'] ?? [])) {
                $article->increment('views');
                $_SESSION['s_articles_article_views'][] = $article->id;
            }
        }
    }

    /**
     * List articles aliases
     *
     * @return array
     */
    public function documentListing(): array
    {
        $articlesListing = Cache::get('articlesListing');

        if (!$articlesListing) {
            $sArticlesController = new sArticlesController();
            $sArticlesController->setArticlesListing();
            $articlesListing = Cache::get('articlesListing');
        }

        return $articlesListing ?? [];
    }

    /**
     * Show Poll or result votes
     *
     * @param $id
     * @return void
     */
    public function showPoll($id)
    {
        $result = '';
        $poll = sArticlesPoll::find($id);
        if ($poll) {
            if (request()->isMethod('POST') && request()->post('poll')) {
                $vote = explode('-', request()->post('poll'));
                if ($vote[0] == $poll->pollid && isset($vote[1])) {
                    $vote = strval($vote[1]);
                    $votes = data_is_json($poll->votes, true);
                    $votes[$vote] = $votes[$vote] + 1;
                    $votes['total'] = $votes['total'] + 1;
                    $poll->votes = json_encode($votes);
                    $poll->update();
                    $_SESSION['polls'][] = $poll->pollid;
                }
            }
            if (in_array($poll->pollid, ($_SESSION['polls'] ?? []))) {
                $result = view('partials.articlePollVotes', ['poll' => $poll])->render();
            } else {
                $result = view('partials.articlePoll', ['poll' => $poll])->render();
            }
        }
        return $result;
    }

    /**
     * Rating of Article votes
     *
     * @param $id
     * @return void
     */
    public function ratingVotes($id)
    {
        $result = '';
        $article = sArticle::find($id);
        if ($article) {
            if (!in_array($article->id, ($_SESSION['article-rating'] ?? []))) {
                if (request()->isMethod('POST') && request()->post('vote')) {
                    $rating = 5;
                    $vote = strval(request()->post('vote'));
                    $votes = data_is_json($article->votes, true);
                    $votes[$vote] = $votes[$vote] + 1;
                    $votes['total'] = $votes['total'] + 1;
                    $sum = 0;
                    foreach ($votes as $k => $v) {
                        if ($k != 'total') {
                            $sum = ($k * $v) + $sum;
                        }
                    }
                    $rating = round($sum / $votes['total']);
                    $article->rating = $rating;
                    $article->votes = json_encode($votes);
                    $article->update();
                    $_SESSION['article-rating'][] = $article->id;
                    if (evo()->isLoggedIn() && evo()->getLoginUserID()) {
                        $user = UserAttribute::where('internalKey', evo()->getLoginUserID())->first();
                        if ($user) {
                            if (is_null($user->vote_articles)) {
                                $query = "ALTER TABLE `".evo()->getDatabase()->getFullTableName('user_attributes')."` ADD `vote_articles` json";
                                evo()->getDatabase()->query($query);
                            }
                            $votes = data_is_json($user->vote_articles ?? '', true) ?: [];
                            $votes[] = $article->id;
                            $user->vote_articles = json_encode($votes);
                            $user->update();
                            $_SESSION['article-rating'] = $votes;
                        }
                    }
                    $result = $rating;
                }
            }
        }
        return $result;
    }

    /**
     * Rating of Article votes
     *
     * @param $id
     * @return void
     */
    public function setComment($id)
    {
        $result = [];
        $uid = evo()->getLoginUserID('web') ?: evo()->getLoginUserID('mgr');
        $message = request()->get('comment', '');
        if ($id && $uid && trim($message)) {
            $commentId = sArticleComment::insertGetId([
                'article_id' => $id,
                'user_id' => $uid,
                'lang' => request()->get('lang', 'uk'),
                'comment' => trim($message),
                'created_at' => now()
            ]);
            $comment = sArticleComment::where('comid', $commentId)->first();
            $user = UserAttribute::where('internalKey', $uid)->first();
            $usersComments[$uid] = $user;
            $result['count'] = sArticleComment::where('article_id', $id)->get()->count();
            $result['comment'] = view(request()->get('render', ''), ['comment' => $comment, 'usersComments' => $usersComments])->render();
        }
        return json_encode($result);
    }

    /**
     * Approve user comment
     *
     * @return void
     */
    public function approveComment()
    {
        if (!isset($_SESSION['mgrValidated'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comid = request()->get('comid', 0);
        $comment = sArticleComment::find($comid);
        $result = [];

        if ($comment) {
            $message = request()->get('comment', $comment->comment);
            $approved = (int) request()->get('approved', 0);

            sArticleComment::where('comid', $comid)
                ->update([
                    'comment' => trim((string) $message),
                    'approved' => $approved,
                ]);
            $result['comment'] = sArticleComment::where('comid', $comid)->first();
        }

        return response()->json($result);
    }

    /**
     * Publish or unpublish an article from the manager list.
     */
    public function publishArticle()
    {
        if (!isset($_SESSION['mgrValidated'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $articleId = (int) request()->get('article_id', 0);
        $published = (int) request()->get('published', 0);
        $article = sArticle::find($articleId);

        if (!$article) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $article->published = $published ? 1 : 0;
        $article->save();

        return response()->json(['article' => $article->fresh()]);
    }

    /**
     * Module url
     *
     * @return string
     */
    public function moduleUrl(): string
    {
        $controller = new sArticlesController();
        return $controller->url;
    }

    /**
     * Retrieves the value from the config file based on the given key.
     *
     * @param string $key The key to retrieve the value from the config file.
     * @param mixed $default (optional) The default value to return if the key does not exist. Default is null.
     * @return mixed The value retrieved from the config file or the default value if the key does not exist.
     */
    public function config(string $key, mixed $default = null): mixed
    {
        return config('seiger.settings.sArticles.' . $key, $default);
    }
}
