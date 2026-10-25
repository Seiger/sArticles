<?php

/**
 * Support isolated package assertions without a site bootstrap.
 *
 * @since 2.1.0
 */
function evo()
{
    return $GLOBALS['evo'];
}
require __DIR__ . '/bootstrap.php';
use Seiger\sArticles\Support\LegacyArticleUriCache as C;
use Seiger\sArticles\sArticles as S;
use Illuminate\Support\Facades\Cache;
/**
 * Provide a controlled fixture for package behavior comparison.
 *
 * @since 2.1.0
 */
class ArticleTestApp extends Illuminate\Container\Container
{
    public string $locale = 'en';
    /**
     * Expose the current fixture locale for real translation scopes.
     *
     * @since 2.1.0
     */
    public function getLocale()
    {
        return $this->locale;
    }
}
$app = new ArticleTestApp();
Illuminate\Container\Container::setInstance($app);
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
$events = new Illuminate\Events\Dispatcher($app);
$app->instance('events', $events);
$app->instance('cache', new Illuminate\Cache\Repository(new Illuminate\Cache\FileStore(new Illuminate\Filesystem\Filesystem(), sys_get_temp_dir() . '/ewt-article-uri-' . getmypid())));
$db = new Illuminate\Database\Capsule\Manager();
$db->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'evo_']);
$db->setEventDispatcher($events);
$db->setAsGlobal();
$db->bootEloquent();
$app->instance('db', $db->getDatabaseManager());
$c = $db->getConnection();
$GLOBALS['evo'] = new class
{
    public array $config = ['lang' => 'en', 'check_sMultisite' => true, 'site_key' => 'a', 'site_root' => 10, 'site_start' => 10, 'friendly_url_suffix' => ''];
    /**
     * Expose the current fixture locale for real translation scopes.
     *
     * @since 2.1.0
     */
    function getLocale()
    {
        return $GLOBALS['app']->locale;
    }
    /**
     * Expose only the controlled configuration used by this isolated regression.
     *
     * @since 2.1.0
     */
    function getConfig($k, $default = null)
    {
        return $this->config[$k] ?? $default;
    }
    /**
     * Support isolated package assertions without a site bootstrap.
     *
     * @since 2.1.0
     */
    function getParentIds($p, $depth = 20)
    {
        return [$p];
    }
};
$app->instance('UrlProcessor', new class
{
    /**
     * Provide stable parent URLs for exact legacy URI validation.
     *
     * @since 2.1.0
     */
    function makeUrl($id)
    {
        return 'https://a.test/' . ($id == 10 ? 'news' : 'other');
    }
});
foreach (['CREATE TABLE evo_s_articles (id INTEGER PRIMARY KEY, alias TEXT, parent INTEGER, published INTEGER, created_at TEXT, updated_at TEXT)', 'CREATE TABLE evo_s_article_translates (article INTEGER, lang TEXT, pagetitle TEXT)', 'CREATE TABLE evo_site_content (id INTEGER PRIMARY KEY, alias TEXT)', 'CREATE TABLE evo_s_multisites (id INTEGER PRIMARY KEY, active INTEGER)'] as $sql) {
    $c->statement($sql);
}
$c->table('s_articles')->insert([['id' => 1, 'alias' => 'story', 'parent' => 10, 'published' => 1], ['id' => 2, 'alias' => 'story', 'parent' => 20, 'published' => 1]]);
$c->table('s_article_translates')->insert([['article' => 1, 'lang' => 'base', 'pagetitle' => 'Base'], ['article' => 1, 'lang' => 'en', 'pagetitle' => 'English'], ['article' => 1, 'lang' => 'uk', 'pagetitle' => 'Ukrainian'], ['article' => 2, 'lang' => 'base', 'pagetitle' => 'Other']]);
Cache::put('articlesListing', ['listed' => 1], 300);
Cache::put('sMultisite-a-resources', [10], 300);
Cache::put('sMultisite-b-resources', [20], 300);
C::listen($events);
$resolver = (new ReflectionClass(S::class))->newInstanceWithoutConstructor();
$c->enableQueryLog();
/**
 * Support isolated package assertions without a site bootstrap.
 *
 * @since 2.1.0
 */
function ck($ok, $label)
{
    if (!$ok) {
        throw new RuntimeException($label);
    }
    echo "PASS {$label}\n";
}
ck($resolver->resolveArticleByUri(['listed'])->id === 1, 'listing wins even when generated path differs');
$c->flushQueryLog();
ck($resolver->resolveArticleByUri(['listed'])->pagetitle === 'English' && count($c->getQueryLog()) === 0, 'warm positive zero SQL with translated attributes');
$decorate = true;
Seiger\sArticles\Models\sArticle::retrieved(static function ($model) use (&$decorate): void {
    if ($decorate) {
        $model->pagetitle = 'decorated:' . $model->pagetitle;
    }
});
$c->table('s_article_translates')->where('article', 1)->where('lang', 'en')->update(['pagetitle' => 'English']);
$decoratedTitle = $resolver->resolveArticleByUri(['listed'])->pagetitle;
ck($decoratedTitle === 'decorated:English' && $resolver->resolveArticleByUri(['listed'])->pagetitle === $decoratedTitle, 'retrieved observers receive original attributes on cold and warm lookups');
$decorate = false;
$events->forget('eloquent.retrieved: ' . Seiger\sArticles\Models\sArticle::class);
$scopes = Seiger\sArticles\Models\sArticle::getAllGlobalScopes();
Seiger\sArticles\Models\sArticle::addGlobalScope('deny', static fn($query) => $query->whereRaw('0 = 1'));
ck($resolver->resolveArticleByUri(['listed']) === null, 'custom scopes bypass shared article entries');
Seiger\sArticles\Models\sArticle::setAllGlobalScopes($scopes);
Seiger\sArticles\Models\sArticle::addGlobalScope('translate', static fn($query) => $query->whereRaw('0 = 1'));
ck($resolver->resolveArticleByUri(['listed']) === null, 'replaced native translation scope bypasses shared entries');
Seiger\sArticles\Models\sArticle::setAllGlobalScopes($scopes);
$c->statement("/* editor save */ UPDATE evo_s_article_translates SET pagetitle = 'Commented' WHERE article = 1 AND lang = 'en'");
ck($resolver->resolveArticleByUri(['listed'])->pagetitle === 'Commented', 'comment-prefixed translation writes invalidate');
$c->statement("WITH chosen AS (SELECT 1 AS id) UPDATE evo_s_article_translates SET pagetitle = 'English' WHERE article IN (SELECT id FROM chosen) AND lang = 'en'");
ck($resolver->resolveArticleByUri(['listed'])->pagetitle === 'English', 'CTE-prefixed translation writes invalidate');
ck($resolver->resolveArticleByUri(['news', 'story'])->id === 1, 'fallback exact path');
ck($resolver->resolveArticleByUri(['en', 'news', 'story'])->id === 1, 'locale prefix');
ck($resolver->resolveArticleByUri(['wrong', 'story']) === null, 'wrong parent path rejected');
ck($resolver->resolveArticleByUri(['baltics']) === null, 'cold negative');
$c->flushQueryLog();
ck($resolver->resolveArticleByUri(['baltics']) === null && count($c->getQueryLog()) === 0, 'warm negative zero SQL');
$app->locale = 'uk';
$GLOBALS['evo']->config['lang'] = 'uk';
ck($resolver->resolveArticleByUri(['listed'])->pagetitle === 'Ukrainian', 'locale isolation');
$app->locale = 'fr';
ck($resolver->resolveArticleByUri(['listed'])->pagetitle === 'Base', 'base translation fallback');
$app->locale = 'en';
$GLOBALS['evo']->config['lang'] = 'en';
$GLOBALS['evo']->config['site_key'] = 'b';
$GLOBALS['evo']->config['site_root'] = 20;
ck($resolver->resolveArticleByUri(['listed']) === null, 'foreign listing article rejected');
ck($resolver->resolveArticleByUri(['other', 'story'])->id === 2, 'same alias resolved within other site');
$GLOBALS['evo']->config['site_key'] = 'a';
$GLOBALS['evo']->config['site_root'] = 10;
Cache::put('articlesListing', ['listed' => 2], 300);
ck($resolver->resolveArticleByUri(['listed']) === null, 'listing changes invalidate identity');
Cache::put('articlesListing', ['listed' => 1], 300);
Cache::put('sMultisite-a-resources', [20], 300);
ck($resolver->resolveArticleByUri(['listed']) === null, 'resource membership cache changes honored');
Cache::put('sMultisite-a-resources', [10], 300);
$c->table('s_article_translates')->where('article', 1)->where('lang', 'en')->update(['pagetitle' => 'Edited']);
ck($resolver->resolveArticleByUri(['listed'])->pagetitle === 'Edited', 'translation write invalidates');
$c->table('s_articles')->where('id', 1)->update(['alias' => 'renamed']);
ck($resolver->resolveArticleByUri(['news', 'story']) === null && $resolver->resolveArticleByUri(['news', 'renamed'])->id === 1, 'rename old and new paths');
$c->beginTransaction();
$c->table('s_article_translates')->where('article', 1)->where('lang', 'en')->update(['pagetitle' => 'Pending']);
ck($resolver->resolveArticleByUri(['listed'])->pagetitle === 'Pending', 'transaction bypass');
$c->rollBack();
ck($resolver->resolveArticleByUri(['listed'])->pagetitle === 'Edited', 'rollback invalidation');
$c->beginTransaction();
$c->table('s_article_translates')->where('article', 1)->where('lang', 'en')->update(['pagetitle' => 'Committed']);
$c->commit();
ck($resolver->resolveArticleByUri(['listed'])->pagetitle === 'Committed', 'commit invalidation');
$c->table('s_articles')->insert(['id' => 3, 'alias' => 'baltics', 'parent' => 10, 'published' => 1]);
ck($resolver->resolveArticleByUri(['news', 'baltics'])->id === 3, 'new article');
ck($resolver->resolveArticleByUri(['news', 'new-miss']) === null, 'cache absence before create');
$c->table('s_articles')->insert(['id' => 4, 'alias' => 'new-miss', 'parent' => 10, 'published' => 0]);
ck($resolver->resolveArticleByUri(['news', 'new-miss'])->id === 4, 'create clears exact negative, publication policy unchanged');
$c->table('s_articles')->where('id', 4)->update(['published' => 1]);
ck((int) $resolver->resolveArticleByUri(['news', 'new-miss'])->published === 1, 'publication attributes invalidated');
$c->beginTransaction();
$c->beginTransaction();
$c->table('s_article_translates')->where('article', 1)->where('lang', 'en')->update(['pagetitle' => 'Nested']);
$c->commit();
ck($resolver->resolveArticleByUri(['listed'])->pagetitle === 'Nested', 'nested commit remains transaction-local');
$c->rollBack();
ck($resolver->resolveArticleByUri(['listed'])->pagetitle === 'Committed', 'outer rollback discards nested changes');
$m = $resolver->resolveArticleByUri(['news', 'new-miss']);
$m->timestamps = false;
$m->alias = 'model-renamed';
$m->save();
ck($resolver->resolveArticleByUri(['news', 'new-miss']) === null && $resolver->resolveArticleByUri(['news', 'model-renamed'])->id === 4, 'model alias save invalidates');
$c->table('s_articles')->where('id', 1)->delete();
ck($resolver->resolveArticleByUri(['listed']) === null, 'delete invalidation');
Cache::flush();
$GLOBALS['evo']->config['site_root'] = 20;
Cache::put('articlesListing', ['listed' => 2], 300);
ck($resolver->resolveArticleByUri(['other', 'story'])->id === 2, 'cache flush preserves resolver behavior');
echo "ALL ARTICLE URI CACHE CHECKS PASSED\n";
$other = new Illuminate\Database\SQLiteConnection(new PDO('sqlite::memory:'), ':memory:', 'evo_', $c->getConfig());
$keyMethod = new ReflectionMethod(C::class, 'key');
$originalKey = $keyMethod->invoke(null, $c);
// Normalize the connection name as well: identity must still follow the ephemeral database.

ck($originalKey !== $keyMethod->invoke(null, $other), 'identical in-memory SQLite configurations have isolated cache identities');
