<?php
/** Provide site scope for isolated article view markers. */
function evo() { return $GLOBALS['cookieTestApp']; }
require __DIR__ . '/bootstrap.php';
/** Minimal configuration source for view-cookie namespacing. */
$GLOBALS['cookieTestApp'] = new class {
    public string $site = 'nordic';
    /** Return the active test site, leaving unrelated options at their defaults. */
    public function getConfig($key, $default = null) { return $key === 'site_key' ? $this->site : $default; }
};
$article = (new ReflectionClass(Seiger\sArticles\sArticles::class))->newInstanceWithoutConstructor();
$read = new ReflectionMethod($article, 'articleViewCookieIds');
$write = new ReflectionMethod($article, 'writeArticleViewCookie');
$tracked = new ReflectionMethod($article, 'hasTrackedArticleView');
$remember = new ReflectionMethod($article, 'rememberArticleView');
$_COOKIE = $_SESSION = [];
for ($i = 1; $i <= 500; $i++) { $remember->invoke($article, $i); }
if (count($_COOKIE) !== 1 || strlen(http_build_query($_COOKIE, '', '; ')) > 3000) { throw new RuntimeException('View cookies must remain bounded'); }
if (count($read->invoke($article)) !== 256 || !$tracked->invoke($article, 500) || !$tracked->invoke($article, 1)) { throw new RuntimeException('Recent cookie and complete session views must be retained'); }
$_SESSION = [];
if (!$tracked->invoke($article, 500) || $tracked->invoke($article, 1)) { throw new RuntimeException('Cookie-only retention must be explicitly bounded'); }
if (!$tracked->invoke($article, 245) || $tracked->invoke($article, 244)) { throw new RuntimeException('Cookie must retain exactly the latest 256 ordinary article IDs'); }
$write->invoke($article, range(PHP_INT_MAX - 255, PHP_INT_MAX));
if (strlen(http_build_query($_COOKIE, '', '; ')) > 3600 || !$tracked->invoke($article, PHP_INT_MAX)) { throw new RuntimeException('Large IDs must respect the encoded cookie size budget and preserve the latest view'); }
$_COOKIE = ['s_articles_view_7' => hash('sha256', 'nordic|s_articles_view|7'), 's_articles_view_8' => 'invalid', 'unrelated' => 'preserve'];
if (!$tracked->invoke($article, 7) || $tracked->invoke($article, 8) || isset($_COOKIE['s_articles_view_7']) || $_COOKIE['unrelated'] !== 'preserve') { throw new RuntimeException('Legacy migration must preserve valid views and unrelated cookies'); }
$GLOBALS['cookieTestApp']->site = 'ewt';
if ($tracked->invoke($article, 7)) { throw new RuntimeException('Cookie markers must remain site scoped'); }
echo "Bounded cookie retention, session continuity, legacy migration and site isolation passed\n";
