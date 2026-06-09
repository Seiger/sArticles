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
    $article = sArticles::resolveArticleByUri(request()->segments());
    if ($article && (evo()->getLoginUserID('mgr') || sArticle::where('s_articles.id', (int) $article->id)->active()->exists())) {
        evo()->setPlaceholder('article', (int) $article->id);
        $goTo = true;
    }
    if ($goTo) {
        evo()->sendForward(sArticles::articleForwardResource());
        exit();
    }

    $aliasArr = request()->segments();
    $find = Arr::last($aliasArr);
    $check = implode('/', $aliasArr);
    if ($check == 'sarticles/rating/'.$find && sArticles::config('general.rating_on', evo()->getConfig('sart_rating_on', 1)) == 1) {
        die(sArticles::ratingVotes((int) $find));
    }
    if ($check == 'sarticles/poll/'.$find && sArticles::config('general.polls_on', evo()->getConfig('sart_polls_on', 1)) == 1) {
        die(sArticles::showPoll((int) $find));
    }
    if ($check == 'sarticles/comment/'.$find && sArticles::config('general.comments_on', evo()->getConfig('sart_comments_on', 1)) == 1) {
        die(sArticles::setComment((int) $find));
    }
    if ($check == 'sarticles/comment-approve' && sArticles::config('general.comments_on', evo()->getConfig('sart_comments_on', 1)) == 1) {
        die(sArticles::approveComment());
    }
});

/**
 * Get document fields and add to array of resource fields
 */
Event::listen('evolution.OnBeforeLoadDocumentObject', function($params) {
    $requestId = (int) evo()->getPlaceholder('article');
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

        $templateAlias = sArticles::articleTemplateAlias();
        $article->type = 'article';
        $article->template = sArticles::articleTemplateId($templateAlias);
        $article->templatealias = $templateAlias;
        $article->menutitle = $article->menutitle ?? $article->pagetitle ?? '';
        $article->hide_from_tree = false;
        $article->content_dispo = false;
        $article->deleted = 0;
        $article->cacheable = 1;

        sArticles::trackView($article);

        unset($article->tmplvars);
        $documentObject = Arr::dot($article->toArray());
        if (is_array($events = evo()->invokeEvent('sArticlesOnBeforeLoadDocumentObject', [
            'article' => $article,
            'documentObject' => $documentObject,
        ]))) {
            foreach ($events as $event) {
                if (is_array($event)) {
                    $documentObject = array_merge($documentObject, $event);
                }
            }
        }

        $params['documentObject'] = $documentObject;
        $params['documentObject']['article'] = $article;
        evo()->addDataToView([
            'article' => $article,
            'constructor' => $article->constructor,
        ]);

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
    if ($article && ($article->id ?? 0) && (evo()->getLoginUserID('mgr') || sArticle::where('s_articles.id', (int) $article->id)->active()->exists())) {
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
        $article->templatealias = sArticles::articleTemplateAlias();
        $article->menutitle = $article->menutitle ?? $article->pagetitle ?? '';
        evo()->addDataToView([
            'article' => $article,
            'constructor' => $article->constructor,
        ]);
        unset($article->tmplvars);
        $documentObject = array_merge($params['documentObject'], Arr::dot($article->toArray()));
        if (is_array($events = evo()->invokeEvent('sArticlesOnBeforeLoadDocumentObject', [
            'article' => $article,
            'documentObject' => $documentObject,
        ]))) {
            foreach ($events as $event) {
                if (is_array($event)) {
                    $documentObject = array_merge($documentObject, $event);
                }
            }
        }

        return $documentObject;
    }
});

/**
 * Add the sArticles shortcut to the Evolution CMS manager top menu.
 *
 * The native manager menu renders Tabler icons as inline SVG, while older package
 * shortcuts often inject icon names into an `<i>` class. Keeping the same SVG
 * markup here makes the optional "main menu" entry visually match system items
 * such as Elements and Modules.
 */
Event::listen('evolution.OnManagerMenuPrerender', function($params) {
    if (sArticles::config('general.in_main_menu', evo()->getConfig('sart_in_main_menu', 0)) == 1) {
        $icon = __('sArticles::global.articles_icon');
        $iconHtml = '<i class="' . $icon . '"></i>';
        if (strpos($icon, 'tabler-') === 0 && function_exists('svg')) {
            $iconHtml = svg($icon, '', [
                'aria-hidden' => 'true',
                'focusable' => 'false',
                'style' => 'flex:0 0 auto;display:block;',
            ])->toHtml();
        }

        $menu['sarticles'] = [
            'sarticles',
            'main',
            $iconHtml . '<span class="menu-item-text">' . __('sArticles::global.articles') . '</span>',
            sArticles::moduleUrl(),
            __('sArticles::global.articles'),
            "",
            "",
            "main",
            0,
            sArticles::config('general.main_menu_order', evo()->getConfig('sart_main_menu_order', 11)),
            '',
        ];

        return serialize(array_merge($params['menu'], $menu));
    }
});
