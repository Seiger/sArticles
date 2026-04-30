@extends('manager::template.page')
@section('content')
    @php
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
            'categories' => 'table',
            'features' => 'highlight',
            'settings' => 'adjustments-horizontal',
            'tvs' => 'adjustments-horizontal',
        ];
        $sArticlesCurrentGet = $get;
        $sArticlesCurrentLang = request()->get('lang', 'base');
        $sArticlesTabLabel = function ($tab) use ($checkType) {
            if (is_array($value = evo()->invokeEvent('sArticlesManagerValueEvent', ['field' => 'tabname', 'item' => $tab, 'type' => $checkType, 'tab' => 'generaltabs']))) {
                return implode('', $value);
            }

            if ($tab == 'articles' && sArticles::config('types.'.$checkType.'.list')) {
                return e(sArticles::config('types.'.$checkType.'.list', ''));
            }

            if ($tab == 'article' && sArticles::config('types.'.$checkType.'.name')) {
                return e(sArticles::config('types.'.$checkType.'.name', ''));
            }

            return e(__('sArticles::global.'.$tab));
        };
    @endphp
    <div class="sarticles-admin" data-sarticles-admin>
        <div class="notifier"><div class="notifier-txt"></div></div>
        <div class="sarticles-admin__header">
            <h1 class="sarticles-admin__title">
                <a class="sarticles-admin__title-link" data-sarticles-tab-link data-tab-back="&get=articles&type={{$checkType}}" href="{!!$url!!}&get=articles&type={{$checkType}}">
                    {!! svg('tabler-rss', 'sarticles-icon sarticles-admin__title-icon', ['data-tooltip' => '<b>sArticles</b> - ' . __('sArticles::global.description')])->toHtml() !!}
                    <span>@lang('sArticles::global.articles')</span>
                </a>
            </h1>
        </div>

        <div class="sectionBody sarticles-panel">
            <div class="sarticles-tabs" id="resourcesPane">
                <div class="sarticles-tabbar" data-sarticles-tabs>
                    <button type="button" class="sarticles-tab-arrow sarticles-tab-arrow--prev" data-sarticles-tab-prev aria-label="Previous tabs">
                        {!! svg('tabler-chevron-left', 'sarticles-tab-arrow__icon')->toHtml() !!}
                    </button>
                    <div class="sarticles-tabbar__scroller" data-sarticles-tabs-scroller>
                        @foreach($tabs as $tab)
                            @if($tab == 'content')
                                @foreach($sArticlesController->langList() as $idx => $lang)
                                    <h2 class="sarticles-tabbar__tab {{$sArticlesCurrentGet == 'content' && $lang == $sArticlesCurrentLang ? 'selected' : ''}}">
                                        <a data-sarticles-tab-link data-tab-back="&get={{$tab}}&lang={{$lang}}{{${$tab.'_url'} ?? ''}}" href="{!!$url!!}&get={{$tab}}&lang={{$lang}}{{${$tab.'_url'} ?? ''}}">
                                            {!! svg('tabler-flag', 'sarticles-icon sarticles-tab__icon')->toHtml() !!}
                                            @lang('sArticles::global.content')
                                            @if($lang != 'base')
                                                <span class="badge bg-seigerit">{{$lang}}</span>
                                            @endif
                                        </a>
                                    </h2>
                                @endforeach
                            @else
                                <h2 class="sarticles-tabbar__tab {{$sArticlesCurrentGet == $tab ? 'selected' : ''}}">
                                    <a data-sarticles-tab-link data-tab-back="&get={{$tab}}{{${$tab.'_url'} ?? ''}}{{$linkType ?? ''}}" href="{!!$url!!}&get={{$tab}}{{${$tab.'_url'} ?? ''}}{{$linkType ?? ''}}">
                                        <span>
                                            {!! svg('tabler-' . ($sArticlesTablerIcons[$tab] ?? 'circle'), 'sarticles-icon sarticles-tab__icon', ['data-tooltip' => __('sArticles::global.'.$tab.'_help')])->toHtml() !!}
                                            {!! $sArticlesTabLabel($tab) !!}
                                        </span>
                                    </a>
                                </h2>
                            @endif
                        @endforeach
                    </div>
                    <button type="button" class="sarticles-tab-arrow sarticles-tab-arrow--next" data-sarticles-tab-next aria-label="Next tabs">
                        {!! svg('tabler-chevron-right', 'sarticles-tab-arrow__icon')->toHtml() !!}
                    </button>
                </div>
                <div class="sarticles-tab-content">
                    @if($sArticlesCurrentGet == 'content')
                        @include('sArticles::contentTab')
                    @elseif(in_array($sArticlesCurrentGet, $tabs, true))
                        @include('sArticles::'.$sArticlesCurrentGet.'Tab')
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts.top')
    @php
        $sArticlesAssetsUrl = rtrim(evo()->getConfig('site_url', MODX_SITE_URL), '/') . '/assets/modules/sarticles/';
        $sArticlesAssetsPath = rtrim(MODX_BASE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'assets/modules/sarticles' . DIRECTORY_SEPARATOR;
        $sArticlesThemesVersion = is_file($sArticlesAssetsPath . 'css/themes.css') ? filemtime($sArticlesAssetsPath . 'css/themes.css') : time();
        $sArticlesAdminVersion = is_file($sArticlesAssetsPath . 'css/admin.css') ? filemtime($sArticlesAssetsPath . 'css/admin.css') : time();
        $sArticlesAdminJsVersion = is_file($sArticlesAssetsPath . 'js/admin.js') ? filemtime($sArticlesAssetsPath . 'js/admin.js') : time();
    @endphp
    <script>
        window.sArticlesThemeConfig = {
            themes: ['evolight', 'evolightness', 'evodark', 'evodarkness'],
            lightThemes: ['evolight', 'evolightness'],
            darkThemes: ['evodark', 'evodarkness'],
            defaultLight: 'evolight',
            defaultDark: 'evodark'
        };
        window.sArticlesAdminConfig = {
            siteUrl: @json(rtrim(evo()->getConfig('site_url', MODX_SITE_URL), '/') . '/'),
            confirmDelete: @json(__('sArticles::global.confirm_delete')),
            youSure: @json(__('sArticles::global.you_sure')),
            withId: @json(__('sArticles::global.with_id')),
            deleted: @json(__('sArticles::global.deleted')),
            deleteLabel: @json(__('global.delete')),
            cancelLabel: @json(__('global.cancel')),
            datePicker: {
                yearOffset: -10,
                format: 'YYYY-mm-dd hh:mm:00',
                dayNames: @lang('global.dp_dayNames'),
                monthNames: @lang('global.dp_monthNames'),
                startDay: 1
            },
            routes: {
                commentApprove: @json(rtrim(evo()->getConfig('site_url', MODX_SITE_URL), '/') . '/sarticles/comment-approve'),
                articlePublish: @json(rtrim(evo()->getConfig('site_url', MODX_SITE_URL), '/') . '/sarticles/article-publish')
            }
        };
    </script>
    <link href="{{$sArticlesAssetsUrl}}css/themes.css?v={{$sArticlesThemesVersion}}" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    @include('sArticles::partials.style')
    <link href="{{$sArticlesAssetsUrl}}css/admin.css?v={{$sArticlesAdminVersion}}" rel="stylesheet"/>
    <script src="{{$sArticlesAssetsUrl}}js/admin.js?v={{$sArticlesAdminJsVersion}}" defer></script>
@endpush

@push('scripts.bot')
    {!!$editor!!}
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>
    <script src="media/script/jquery.quicksearch.js"></script>
    <script src="media/script/jquery.nucontextmenu.js"></script>
    <script src="media/script/bootstrap/js/bootstrap.min.js"></script>
    <script src="media/script/resources-functions.js"></script>
    <script src="media/calendar/datepicker.js"></script>
    <img src="{{evo()->getConfig('site_url', '/')}}assets/images/noimage.png" id="img-preview" style="display: none;" class="post-thumbnail">
    <div id="copyright"><a href="https://seigerit.com/" target="_blank"><img src="{{evo()->getConfig('site_url', '/')}}assets/site/seigerit-blue.svg"/></a></div>
@endpush
