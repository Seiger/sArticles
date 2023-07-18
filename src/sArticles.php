<?php namespace Seiger\sArticles;

use EvolutionCMS\Models\UserAttribute;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticle;
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
