<?php
// Exercise legacy manager URLs without booting the CMS or writing to its database.
function __($key)
{
    return 'Публікації';
}

function csrf_token()
{
    return $GLOBALS['testManagerToken'];
}

require dirname(__DIR__) . '/src/Controllers/sArticlesController.php';

foreach (['initial-session', 'rotated-session', 'token+with&reserved=characters'] as $token) {
    $GLOBALS['testManagerToken'] = $token;
    $controller = new \Seiger\sArticles\Controllers\sArticlesController();
    foreach (['&get=article&type=idea&i=120', '&get=content&lang=uk&i=120', '&get=articleSave', '&get=authors', '&get=articles&page=2'] as $route) {
        parse_str(parse_url($controller->url . $route, PHP_URL_QUERY), $query);
        if (($query['_token'] ?? null) !== $token || ($query['a'] ?? null) !== '112'
            || ($query['id'] ?? null) !== md5('Публікації') || !isset($query['get'])) {
            throw new \RuntimeException('Manager URL lost its token or routing parameters.');
        }
    }
}

echo "Manager module URL checks passed.\n";
