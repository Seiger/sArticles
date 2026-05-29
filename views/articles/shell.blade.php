@php
    $manager = app(\EvoUI\Support\ManagerContext::class);
    $theme = $manager->theme();
    $themeMode = $manager->themeMode($theme);
    $themeClasses = $manager->themeClasses($theme);
    $themeBackground = $manager->themeBackground($theme);

    $sArticlesTablerIcons = [
        'articles' => 'rss',
        'article' => 'article',
        'content' => 'flag',
        'article_comments' => 'messages',
        'comments' => 'messages',
        'authors' => 'user',
        'tags' => 'hash',
        'poll' => 'chart-bar',
        'polls' => 'chart-bar',
        'categories' => 'category',
        'features' => 'highlight',
        'tvparams' => 'variable',
        'settings' => 'adjustments-horizontal',
        'tvs' => 'adjustments-horizontal',
    ];

    $sArticlesCurrentLang = request()->get('lang', 'base');
    $sArticlesPageTitle = match ($get ?? 'articles') {
        'authors' => __('sArticles::global.authors'),
        'tags' => __('sArticles::global.tags'),
        'categories' => __('sArticles::global.categories'),
        'features' => __('sArticles::global.features'),
        'comments' => __('sArticles::global.comments'),
        'polls' => __('sArticles::global.polls'),
        'tvparams' => __('sArticles::global.tvs'),
        'settings' => __('sArticles::global.settings'),
        default => __('sArticles::global.articles'),
    };
    $sArticlesTabLabel = function ($tab) use ($checkType) {
        if (is_array($value = evo()->invokeEvent('sArticlesManagerValueEvent', ['field' => 'tabname', 'item' => $tab, 'type' => $checkType, 'tab' => 'generaltabs']))) {
            return implode('', $value);
        }

        if ($tab == 'articles' && sArticles::config('types.' . $checkType . '.list')) {
            return e(sArticles::config('types.' . $checkType . '.list', ''));
        }

        if ($tab == 'article' && sArticles::config('types.' . $checkType . '.name')) {
            return e(sArticles::config('types.' . $checkType . '.name', ''));
        }

        if ($tab == 'tvparams') {
            return e(__('sArticles::global.tvs'));
        }

        return e(__('sArticles::global.' . $tab));
    };

    $sArticlesModuleTabs = [];

    foreach ($tabs as $tab) {
        if ($tab == 'content') {
            foreach ($sArticlesController->langList() as $lang) {
                $href = $url . '&get=' . $tab . '&lang=' . $lang . (${$tab . '_url'} ?? '');
                $label = e(__('sArticles::global.content'));

                if ($lang != 'base') {
                    $label .= ' <span class="badge bg-seigerit">' . e($lang) . '</span>';
                }

                $sArticlesModuleTabs[] = [
                    'key' => $tab,
                    'href' => $href,
                    'icon' => 'flag',
                    'label' => $label,
                    'active' => $get == 'content' && $lang == $sArticlesCurrentLang,
                    'data' => [
                        'data-evo-manager-link' => '',
                        'data-tab-back' => '&get=' . $tab . '&lang=' . $lang . (${$tab . '_url'} ?? ''),
                    ],
                ];
            }

            continue;
        }

        $sArticlesModuleTabs[] = [
            'key' => $tab,
            'href' => $url . '&get=' . $tab . (${$tab . '_url'} ?? '') . ($linkType ?? ''),
            'icon' => $sArticlesTablerIcons[$tab] ?? 'circle',
            'label' => $sArticlesTabLabel($tab),
            'active' => $get == $tab,
            'data' => [
                'data-evo-manager-link' => '',
                'data-tab-back' => '&get=' . $tab . (${$tab . '_url'} ?? '') . ($linkType ?? ''),
            ],
        ];
    }
@endphp
<!doctype html>
<html
    class="evo-ui-page {{ $themeClasses }}"
    lang="{{ str_replace('_', '-', app()->getLocale() ?: 'uk') }}"
    data-theme="{{ $theme }}"
    data-theme-mode="{{ $themeMode }}"
    style="background-color: var(--evo-ui-bg, {{ $themeBackground }})"
>
<head>
    <meta charset="utf-8">
    <meta name="color-scheme" content="{{ $themeMode === 'dark' ? 'dark light' : 'light dark' }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $sArticlesPageTitle }}</title>
    @include('evo::partials.assets')
</head>
<body
    class="evo-ui-page {{ $themeClasses }}"
    data-theme="{{ $theme }}"
    data-theme-mode="{{ $themeMode }}"
    style="background-color: var(--evo-ui-bg, {{ $themeBackground }})"
>
    <div
        class="evo-ui {{ $themeClasses }}"
        data-evo-ui-root
        data-theme="{{ $theme }}"
        data-theme-mode="{{ $themeMode }}"
    >
        <livewire:sarticles.module-panel
            :tabs="$sArticlesModuleTabs"
            :active-tab="$get ?? 'articles'"
            :context="['moduleUrl' => $url, 'type' => $checkType]"
        />
    </div>
    <script>window.parent?.evo?.moduleViewport?.requestHiddenTree(window);</script>
</body>
</html>
