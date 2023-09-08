<?php namespace Seiger\sArticles;

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
        $query = sArticle::search()->orderBy($order, $direc);
        if (!IN_MANAGER_MODE) {
            $query->active();
        }
        $articles = $query->paginate($paginate);
        return $articles;
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
        $result = [];
        $message = request()->get('comment', '');
        $approved = request()->get('approved');
        $comid = request()->get('comid', 0);
        $comment = sArticleComment::find($comid);
        if ($comment && $message)
        {
            sArticleComment::where('comid', $comid)
                ->update([
                    'comment' => trim($message),
                    'approved' => (int)$approved,
                ]);
            $result['comment'] =  sArticleComment::where('comid', $comid)->first();
        }
        return json_encode($result);
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
}
