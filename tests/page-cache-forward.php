<?php
$plugin = file_get_contents(dirname(__DIR__) . '/plugins/sArticlesPlugin.php');
$disable = strpos($plugin, "evo()->setConfig('enable_cache', 0);");
$forward = strpos($plugin, 'evo()->sendForward(sArticles::isLegacyMode()');

if ($disable === false || $forward === false || $disable > $forward) {
    throw new \RuntimeException('Article forward must disable the page cache before sendForward.');
}

echo "Article forward page cache checks passed.\n";
