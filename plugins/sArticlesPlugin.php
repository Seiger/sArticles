<?php
/**
 * Plugin for Seiger Offers Management Module for Evolution CMS admin panel.
 */

use EvolutionCMS\Models\SiteTemplate;
use Illuminate\Support\Arr;
use Seiger\sArticles\Models\sArticle;

/**
 * Catch the Article by alias
 */
Event::listen('evolution.OnPageNotFound', function($params) {
    $goTo = false;
    $article = sArticles::resolveArticleByUri(request()->segments());
    if ($article && (($article->published ?? 0) == 1 || evo()->getLoginUserID('mgr'))) {
        if (!sArticles::isLegacyMode()) {
            evo()->setPlaceholder('article', (int)$article->id);
        }
        $goTo = true;
    }
    if ($goTo) {
        evo()->sendForward(sArticles::isLegacyMode() ? evo()->getConfig('sart_blank', 1) : evo()->getConfig('site_start', 1));
        exit();
    }

    $aliasArr = request()->segments();
    $find = Arr::last($aliasArr);
    $check = implode('/', $aliasArr);
    if ($check == 'sarticles/rating/'.$find && evo()->getConfig('sart_rating_on', 1) == 1) {
        die(sArticles::ratingVotes((int)$find));
    }
    if ($check == 'sarticles/poll/'.$find && evo()->getConfig('sart_polls_on', 1) == 1) {
        die(sArticles::showPoll((int)$find));
    }
    if ($check == 'sarticles/comment/'.$find && evo()->getConfig('sart_comments_on', 1) == 1) {
        die(sArticles::setComment((int)$find));
    }
    if ($check == 'sarticles/comment-approve' && evo()->getConfig('sart_comments_on', 1) == 1) {
        die(sArticles::approveComment());
    }
});

/**
 * Get document fields and add to array of resource fields
 */
Event::listen('evolution.OnBeforeLoadDocumentObject', function($params) {
    if (sArticles::isLegacyMode()) {
        return;
    }

    $requestId = (int)evo()->getPlaceholder('article');
    if ($requestId) {
        $article = sArticles::getArticle($requestId);
        if (!($article->id ?? 0)) {
            return;
        }

        $article->constructor = data_is_json($article->constructor ?? '', true);
        $article->tmplvars = data_is_json($article->tmplvars ?? '', true);
        if ($article->tmplvars && count($article->tmplvars)) {
            foreach ($article->tmplvars as $name => $value) {
                if (isset($params['documentObject'][$name]) && is_array($params['documentObject'][$name])) {
                    $params['documentObject'][$name][1] = $value;
                }
            }
        }

        $template = SiteTemplate::whereTemplatealias('s_articles_article')->first();
        $article->type = 'article';
        $article->template = $template->id ?? 0;
        $article->hide_from_tree = false;
        $article->content_dispo = false;
        $article->deleted = 0;
        $article->cacheable = 1;

        sArticles::trackView($article);

        unset($article->tmplvars);
        $params['documentObject'] = Arr::dot($article->toArray());
        $params['documentObject']['article'] = $article;

        return $params['documentObject'];
    }
});

/**
 * Legacy article resource mode.
 */
Event::listen('evolution.OnAfterLoadDocumentObject', function($params) {
    if (!sArticles::isLegacyMode()) {
        return;
    }

    $article = sArticles::resolveArticleByUri(request()->segments());
    if ($article && ($article->id ?? 0)) {
        $article->constructor = data_is_json($article->constructor ?? '', true);
        $article->tmplvars = data_is_json($article->tmplvars ?? '', true);
        if ($article->tmplvars && count($article->tmplvars)) {
            foreach ($article->tmplvars as $name => $value) {
                if (isset($params['documentObject'][$name]) && is_array($params['documentObject'][$name])) {
                    $params['documentObject'][$name][1] = $value;
                }
            }
        }
        sArticles::trackView($article);
        unset($article->tmplvars);
        return array_merge($params['documentObject'], Arr::dot($article->toArray()));
    }
});

/**
 * Add Menu item
 */
Event::listen('evolution.OnManagerMenuPrerender', function($params) {
    if (evo()->getConfig('sart_in_main_menu', 0) == 1) {
        $menu['sarticles'] = [
            'sarticles',
            'main',
            '<i class="' . __('sArticles::global.articles_icon') . '"></i>' . __('sArticles::global.articles'),
            sArticles::moduleUrl(),
            __('sArticles::global.articles'),
            "",
            "",
            "main",
            0,
            evo()->getConfig('sart_main_menu_order', 11),
            '',
        ];

        return serialize(array_merge($params['menu'], $menu));
    }
});
