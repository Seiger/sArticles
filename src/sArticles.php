<?php namespace Seiger\sArticles;

use Illuminate\Support\Arr;
use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Models\SiteTemplate;
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
use Seiger\sArticles\Support\LikeSearch;

/**
 * sArticles package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class sArticles
{
    public $url = '';

    /**
     * Initialize sArticles with its runtime dependencies.
     *
     * This method keeps the construct responsibility inside sArticles, so callers can rely on a
     * stable package boundary while the manager UI, frontend runtime, or legacy storage details
     * evolve.
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        if (IN_MANAGER_MODE) {
            Paginator::defaultView('sarticles::partials.pagination');
            $this->url = $this->moduleUrl();
        } else {
            Paginator::defaultView('partials.pagination');
        }
    }

    /**
     * Return paginated articles for frontend or manager use.
     *
     * Frontend calls only expose active articles, while manager calls may include availability
     * and relation filters.
     *
     * @param mixed $paginate Number of items per pagination page.
     * @return object Paginated or model-like object returned by the package query.
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
     * Read and sanitize manager filter IDs.
     *
     * Accepts array or comma-separated input and returns unique positive integer IDs for
     * relation filters.
     *
     * @param string $key Configuration or request key to read.
     * @return array<string, mixed> Structured array payload consumed by the manager UI or package runtime.
     * @since 2.0.0
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
     * Return paginated article comments.
     *
     * Optional article ID and manager search filters can narrow the result set for frontend
     * widgets or manager tables.
     *
     * @param mixed $paginate Number of items per pagination page.
     * @param mixed $artids Optional article IDs used to limit the comment query.
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
            $search = LikeSearch::needle(mb_strtolower(trim(strip_tags(request()->input('search', '')))));
            $query->whereRaw(LikeSearch::lowerExpression($query, 'comment'), [$search]);
        }
        return $query->paginate($paginate);
    }

    /**
     * Load one translated article by ID.
     *
     * When no article exists, an empty model is returned so templates can avoid null checks.
     *
     * @param int $articleId Internal article identifier.
     * @return object Paginated or model-like object returned by the package query.
     */
    public function getArticle(int $articleId): object
    {
        return sArticle::where('s_articles.id', $articleId)->first() ?? new sArticle();
    }

    /**
     * Load one translated article by alias.
     *
     * When no article exists, an empty model is returned so frontend templates have a
     * consistent object shape.
     *
     * @param string $articleAlias Public article alias.
     * @return object Paginated or model-like object returned by the package query.
     */
    public function getArticleByAlias(string $articleAlias): object
    {
        return sArticle::where('s_articles.alias', $articleAlias)->first() ?? new sArticle();
    }

    /**
     * Check whether legacy blank-resource routing is enabled.
     *
     * Legacy mode controls whether articles are rendered through the historical blank resource
     * strategy.
     *
     * @return bool True when the condition is met, false otherwise.
     */
    public function isLegacyMode(): bool
    {
        return (int) evo()->getConfig('sart_blank', 1) > 1;
    }

    /**
     * Return the resource used to render resolved public article URLs.
     *
     * Articles must render through the current domain start page so multisite projects do not
     * depend on one global blank resource that may belong to another domain tree.
     *
     * @return int Current site start resource ID.
     */
    public function articleForwardResource(): int
    {
        return max(1, (int) evo()->getConfig('site_start', 1));
    }

    /**
     * Return the template alias used to render resolved public article URLs.
     *
     * The public route no longer forwards to the legacy blank resource, but existing projects may
     * still keep their article template assigned there. Reading the template alias keeps those
     * projects compatible while the render resource remains domain-local.
     *
     * @return string Blade template alias for article pages.
     */
    public function articleTemplateAlias(): string
    {
        $configured = trim((string) evo()->getConfig('sart_template_alias', ''));
        if ($configured !== '') {
            return $configured;
        }

        $blankId = (int) evo()->getConfig('sart_blank', 0);
        if ($blankId > 0) {
            $templateId = (int) (SiteContent::query()->whereKey($blankId)->value('template') ?? 0);
            if ($templateId > 0) {
                $templateAlias = trim((string) (SiteTemplate::query()->whereKey($templateId)->value('templatealias') ?? ''));
                if ($templateAlias !== '') {
                    return $templateAlias;
                }
            }
        }

        return 's_articles_article';
    }

    /**
     * Return the template ID for an article template alias.
     *
     * @param string|null $templateAlias Optional already-resolved template alias.
     * @return int Evolution template ID or 0 when the Blade alias is used without a DB template.
     */
    public function articleTemplateId(?string $templateAlias = null): int
    {
        $templateAlias = trim((string) ($templateAlias ?: sArticles::articleTemplateAlias()));
        if ($templateAlias === '') {
            return 0;
        }

        return (int) (SiteTemplate::query()->whereTemplatealias($templateAlias)->value('id') ?? 0);
    }

    /**
     * Resolve an article from request URI segments.
     *
     * The method checks cached article aliases and validates the resolved article link before
     * returning a model.
     *
     * @param array<string, mixed> $segments Request URI segments after Evolution routing parsing.
     * @return ?sArticle Resolved article model or null when no article matches the URI.
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
            $article = sArticles::getArticle((int) $articleId);
            if (($article->id ?? 0) && sArticles::articleBelongsToCurrentSite($article)) {
                return $article;
            }
        }

        $articleAlias = Arr::last($segments);
        if (!$articleAlias) {
            return null;
        }

        $articles = sArticle::where('s_articles.alias', $articleAlias)->get();

        $aliasPath = $this->normalizeArticlePath($alias);
        $localizedAliasPath = $this->normalizeArticlePath('/' . evo()->getConfig('lang', 'uk') . '/' . $alias);

        foreach ($articles as $article) {
            if (!sArticles::articleBelongsToCurrentSite($article)) {
                continue;
            }

            $articlePath = $this->normalizeArticlePath((string) $article->link);
            if ($articlePath === $aliasPath || $articlePath === $localizedAliasPath) {
                return $article;
            }
        }

        return null;
    }

    /**
     * Check whether an article parent belongs to the current multisite resource tree.
     *
     * @param sArticle $article Article being resolved from the public URL.
     * @return bool True when the article can be rendered on the current site.
     */
    public function articleBelongsToCurrentSite(sArticle $article): bool
    {
        if (!evo()->getConfig('check_sMultisite', false)) {
            return true;
        }

        $parent = (int) ($article->parent ?? 0);
        if ($parent <= 0) {
            return true;
        }

        $siteKey = evo()->getConfig('site_key', 'default');
        $siteResources = Cache::get('sMultisite-' . $siteKey . '-resources');
        if (is_array($siteResources) && $siteResources !== []) {
            return in_array($parent, array_map('intval', $siteResources), true);
        }

        $siteRoot = (int) evo()->getConfig('site_root', 0);
        if ($siteRoot <= 0) {
            return true;
        }

        $parents = array_map('intval', array_values(evo()->getParentIds($parent, 20)));
        return $parent === $siteRoot || in_array($siteRoot, $parents, true);
    }

    /**
     * Normalize a relative or absolute article URL into a comparable path.
     *
     * @param string $path Article path or absolute URL.
     * @return string Normalized path without leading or trailing slashes.
     */
    protected function normalizeArticlePath(string $path): string
    {
        $urlPath = parse_url($path, PHP_URL_PATH);
        if (is_string($urlPath) && $urlPath !== '') {
            $path = $urlPath;
        }

        return trim($path, '/');
    }

    /**
     * Increment frontend view counters for an article.
     *
     * Only persisted article models should be passed here so statistics remain tied to real
     * records.
     *
     * @param sArticle $article Article model being read or persisted.
     * @return void No value is returned.
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
     * Return the cached article URL map.
     *
     * If the cache is missing, the manager controller rebuilds it from published article links
     * before returning the mapping.
     *
     * @return array<string, mixed> Structured array payload consumed by the manager UI or package runtime.
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
     * Render a poll and optionally process a submitted vote.
     *
     * The method handles visitor vote cookies and returns the appropriate poll or results
     * partial.
     *
     * @param mixed $id Internal record identifier.
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
     * Read or update rating votes for an article.
     *
     * Votes are tracked for visitors or logged-in users and converted into response data for
     * frontend interactions.
     *
     * @param mixed $id Internal record identifier.
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
     * Store a frontend article comment.
     *
     * The method creates the comment, loads user metadata when available, and renders the
     * requested comment partial for the response.
     *
     * @param mixed $id Internal record identifier.
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
     * Approve or unapprove a comment from the manager.
     *
     * The AJAX action validates the manager session and returns the updated comment state.
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
     * Publish or unpublish an article from the manager.
     *
     * This AJAX endpoint validates the manager session, updates the article state, and returns
     * the fresh model payload.
     *
     * @since 2.0.0
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
     * Build the Evolution manager module URL.
     *
     * The URL is reused by table row actions, modal actions, and links back into the package
     * manager module.
     *
     * @return string String value ready for manager or frontend use.
     */
    public function moduleUrl(): string
    {
        $controller = new sArticlesController();
        return $controller->url;
    }

    /**
     * Read one merged sArticles configuration value.
     *
     * Provides a small facade-friendly wrapper around package settings with a caller-provided
     * fallback.
     *
     * @param string $key Configuration or request key to read.
     * @param mixed $default Fallback value used when input is missing or invalid.
     * @return mixed Value returned for the caller.
     */
    public function config(string $key, mixed $default = null): mixed
    {
        return config('seiger.settings.sArticles.' . $key, $default);
    }
}
