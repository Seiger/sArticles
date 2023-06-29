<?php
/**
 * Plugin for Seiger Offers Management Module for Evolution CMS admin panel.
 */

use Illuminate\Support\Arr;
use Seiger\sArticles\Models\sArticle;

/**
 * Catch the Article by alias
 */
Event::listen('evolution.OnPageNotFound', function($params) {
    $goTo = false;
    $aliasArr = request()->segments();
    if ($aliasArr[0] == evo()->getConfig('lang', 'uk')) {
        unset($aliasArr[0]);
    }
    $alias = implode('/', $aliasArr);
    $goTo = Arr::exists(sArticles::documentListing(), $alias);
    if (!$goTo && evo()->getLoginUserID('mgr')) {
        $alias = Arr::last($aliasArr);
        $article = sArticles::getArticleByAlias($alias ?? '');
        if ($article && isset($article->article) && (int)$article->article > 0) {
            $goTo = true;
        }
    }
    if ($goTo) {
        evo()->sendForward(evo()->getConfig('s_articles_resource', 1));
        exit();
    }

    $find = Arr::last($aliasArr);
    $check = implode('/', $aliasArr);
    if ($check == 'sarticles/rating/'.$find && evo()->getConfig('s_articles_rating_on', 1) == 1) {
        die(sArticles::ratingVotes((int)$find));
    }
    if ($check == 'sarticles/poll/'.$find && evo()->getConfig('s_articles_polls_on', 1) == 1) {
        die(sArticles::showPoll((int)$find));
    }
});

/*
 * Get document fields and add to array of resource fields
 */
Event::listen('evolution.OnAfterLoadDocumentObject', function($params) {
    $aliasArr = request()->segments();
    if (isset($aliasArr[0]) && $aliasArr[0] == evo()->getConfig('lang', 'uk')) {
        unset($aliasArr[0]);
    }
    $alias = implode('/', $aliasArr);
    $document = sArticles::documentListing()[$alias] ?? false;
    if (!$document && evo()->getLoginUserID('mgr')) {
        $alias = Arr::last($aliasArr);
        $article = sArticles::getArticleByAlias($alias ?? '');
        if ($article && isset($article->article) && (int)$article->article > 0) {
            $document = (int)$article->article;
        }
    }
    if ($document) {
        $article = sArticle::find($document);
        $article->constructor = data_is_json($article->constructor, true);
        $article->tmplvars = data_is_json($article->tmplvars, true);
        if ($article->tmplvars && count($article->tmplvars)) {
            foreach ($article->tmplvars as $name => $value) {
                if (isset($params['documentObject'][$name]) && is_array($params['documentObject'][$name])) {
                    $params['documentObject'][$name][1] = $value;
                }
            }
        }
        unset($article->tmplvars);
        return array_merge($params['documentObject'], Arr::dot($article->toArray()));
    }
});
