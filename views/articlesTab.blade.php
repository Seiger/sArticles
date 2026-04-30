@php
    $searchValue = trim(request()->input('search', ''));
    $typeValue = trim(request()->input('type', ''));
    $readFilterValues = function (string $key) {
        return collect(explode(',', (string)request()->input($key, '')))
            ->map(fn($value) => (int)$value)
            ->filter(fn($value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    };
    $sectionValues = $readFilterValues('section');
    $categoryValues = $readFilterValues('category');
    $tagValues = $readFilterValues('tag');
    $featureValues = $readFilterValues('feature');
    $availabilityValue = in_array(request()->input('availability'), ['published', 'unpublished'], true) ? request()->input('availability') : 'all';
    $articleQuery = [];
    $addArticleText = sArticles::config('types.'.$checkType.'.add_button_text', __('sArticles::global.add_article'));
    $addArticleLabel = __('global.add') . ' ' . $addArticleText;

    if ($searchValue !== '') {
        $articleQuery['search'] = $searchValue;
    }
    if ($typeValue !== '') {
        $articleQuery['type'] = $typeValue;
    }
    if (count($sectionValues)) {
        $articleQuery['section'] = implode(',', $sectionValues);
    }
    if (count($categoryValues)) {
        $articleQuery['category'] = implode(',', $categoryValues);
    }
    if (count($tagValues)) {
        $articleQuery['tag'] = implode(',', $tagValues);
    }
    if (count($featureValues)) {
        $articleQuery['feature'] = implode(',', $featureValues);
    }
    if ($availabilityValue !== 'all') {
        $articleQuery['availability'] = $availabilityValue;
    }

    $buildArticleListUrl = function (array $overrides = []) use ($url, $articleQuery) {
        $query = array_merge($articleQuery, $overrides);

        foreach ($query as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
            }
        }

        if (($query['availability'] ?? '') === 'all') {
            unset($query['availability']);
        }

        return $url . '&get=articles' . (count($query) ? '&' . http_build_query($query) : '');
    };

    $availabilityOptions = [
        ['value' => 'all', 'icon' => 'list', 'label' => __('sArticles::global.availability_all')],
        ['value' => 'published', 'icon' => 'eye', 'label' => __('sArticles::global.availability_published')],
        ['value' => 'unpublished', 'icon' => 'eye-off', 'label' => __('sArticles::global.availability_unpublished')],
    ];
    $filterArticleIdsQuery = \Seiger\sArticles\Models\sArticle::query()->select('s_articles.id');
    $filterParentIdsQuery = \Seiger\sArticles\Models\sArticle::query()->select('s_articles.parent');

    if ($typeValue !== '') {
        $filterArticleIdsQuery->whereType($typeValue);
        $filterParentIdsQuery->whereType($typeValue);
    }

    $filterArticleIds = $filterArticleIdsQuery->pluck('s_articles.id')->filter()->unique()->values();
    $filterParentIds = $filterParentIdsQuery->distinct()->pluck('s_articles.parent')->filter()->unique()->values();
    $filterCategoryIds = \Illuminate\Support\Facades\DB::table('s_article_categories')
        ->whereIn('article', $filterArticleIds)
        ->distinct()
        ->pluck('category')
        ->map(fn($id) => (int)$id)
        ->filter()
        ->values();
    $filterTagIds = \Illuminate\Support\Facades\DB::table('s_article_tags')
        ->whereIn('article', $filterArticleIds)
        ->distinct()
        ->pluck('tag')
        ->map(fn($id) => (int)$id)
        ->filter()
        ->values();
    $filterFeatureIds = \Illuminate\Support\Facades\DB::table('s_article_features')
        ->whereIn('article', $filterArticleIds)
        ->distinct()
        ->pluck('feature')
        ->map(fn($id) => (int)$id)
        ->filter()
        ->values();
    $filterParents = \EvolutionCMS\Models\SiteContent::select('id', 'pagetitle')
        ->whereIn('id', $filterParentIds->filter(fn($parent) => (int)$parent > 1)->toArray())
        ->orderBy('pagetitle')
        ->get()
        ->pluck('pagetitle', 'id')
        ->toArray();
    $filterCategories = \Seiger\sArticles\Models\sArticlesCategory::whereIn('catid', $filterCategoryIds)->orderBy($sArticlesController->langDefault())->get();
    $filterTags = \Seiger\sArticles\Models\sArticlesTag::whereIn('tagid', $filterTagIds)->orderBy($sArticlesController->langDefault())->get();
    $filterFeatures = \Seiger\sArticles\Models\sArticlesFeature::whereIn('fid', $filterFeatureIds)->orderBy($sArticlesController->langDefault())->get();
    $taxonomyLabel = function ($item) use ($sArticlesController) {
        $lang = $sArticlesController->langDefault();

        return trim($item->{$lang} ?? '') ?: (trim($item->base ?? '') ?: trim($item->alias ?? ''));
    };
    $filterGroups = [
        [
            'key' => 'section',
            'icon' => 'folder',
            'label' => __('sArticles::global.all_sections'),
            'title' => __('sArticles::global.filter_by_section'),
            'values' => $sectionValues,
            'items' => collect(
                $filterParentIds->contains(fn($parent) => (int)$parent <= 1)
                    ? [['id' => 1, 'label' => evo()->getConfig('site_name')]]
                    : []
            )->merge(collect($filterParents)->map(fn($title, $id) => ['id' => (int)$id, 'label' => $title]))->values(),
        ],
        [
            'key' => 'category',
            'icon' => 'category',
            'label' => __('sArticles::global.all_categories'),
            'title' => __('sArticles::global.filter_by_category'),
            'values' => $categoryValues,
            'items' => $filterCategories->map(fn($category) => [
                'id' => (int)$category->catid,
                'label' => $taxonomyLabel($category),
            ])->filter(fn($item) => $item['label'] !== '')->values(),
        ],
        [
            'key' => 'tag',
            'icon' => 'hash',
            'label' => __('sArticles::global.all_tags'),
            'title' => __('sArticles::global.filter_by_tag'),
            'values' => $tagValues,
            'items' => $filterTags->map(fn($tag) => [
                'id' => (int)$tag->tagid,
                'label' => $taxonomyLabel($tag),
            ])->filter(fn($item) => $item['label'] !== '')->values(),
        ],
        [
            'key' => 'feature',
            'icon' => 'highlight',
            'label' => __('sArticles::global.all_features'),
            'title' => __('sArticles::global.filter_by_feature'),
            'values' => $featureValues,
            'items' => $filterFeatures->map(fn($feature) => [
                'id' => (int)$feature->fid,
                'label' => $taxonomyLabel($feature),
            ])->filter(fn($item) => $item['label'] !== '')->values(),
        ],
    ];
@endphp
<div class="sarticles-listbar">
    <a href="{!!$url!!}&get=article&type={{$checkType}}&i=0"
       class="sarticles-create-shortcut"
       title="{{$addArticleLabel}}"
       aria-label="{{$addArticleLabel}}">
        {!! svg('tabler-plus', 'sarticles-icon')->toHtml() !!}
    </a>
    <div class="sarticles-filter-strip">
        @foreach($filterGroups as $group)
            <div class="sarticles-filter-menu" data-filter-menu data-filter-key="{{$group['key']}}">
                <button type="button"
                        class="sarticles-filter-menu__toggle @if(count($group['values'])) is-active @endif"
                        title="{{$group['title']}}"
                        aria-label="{{$group['title']}}"
                        aria-expanded="false">
                    {!! svg('tabler-' . $group['icon'], 'sarticles-icon')->toHtml() !!}
                    @if(count($group['values']))
                        <span class="sarticles-filter-menu__count">{{count($group['values'])}}</span>
                    @endif
                </button>
                <div class="sarticles-filter-menu__panel" role="dialog" aria-label="{{$group['title']}}">
                    <label class="sarticles-filter-menu__search" title="@lang('global.search')">
                        {!! svg('tabler-search', 'sarticles-icon')->toHtml() !!}
                        <input type="search" autocomplete="off" data-filter-search>
                    </label>
                    <div class="sarticles-filter-menu__list">
                        @foreach($group['items'] as $item)
                            <label class="sarticles-filter-menu__option" data-filter-option>
                                <input type="checkbox"
                                       value="{{$item['id']}}"
                                       @if(in_array((int)$item['id'], $group['values'], true)) checked @endif>
                                <span>{{$item['label']}}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="sarticles-filter-menu__actions">
                        <button type="button" class="sarticles-filter-menu__action" data-filter-select-all>@lang('sArticles::global.select_all')</button>
                        <button type="button" class="sarticles-filter-menu__action" data-filter-clear>@lang('sArticles::global.clear')</button>
                        <button type="button" class="sarticles-filter-menu__apply" data-filter-apply title="@lang('sArticles::global.apply')" aria-label="@lang('sArticles::global.apply')">
                            {!! svg('tabler-check', 'sarticles-icon')->toHtml() !!}
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="sarticles-segmented" role="group" aria-label="@lang('sArticles::global.availability_filter')">
            @foreach($availabilityOptions as $option)
                <a href="{{$buildArticleListUrl(['availability' => $option['value']])}}"
                   class="sarticles-segmented__item sarticles-segmented__item--{{$option['value']}} @if($availabilityValue === $option['value']) is-active @endif"
                   title="{{$option['label']}}"
                   aria-label="{{$option['label']}}">
                    {!! svg('tabler-' . $option['icon'], 'sarticles-icon')->toHtml() !!}
                </a>
            @endforeach
        </div>
    </div>
    <label class="sarticles-search" title="@lang('global.search')">
        {!! svg('tabler-search', 'sarticles-icon sarticles-search__icon')->toHtml() !!}
        <input type="search" name="search" value="{{request()->search ?? ''}}" autocomplete="off" />
    </label>
</div>
@php $types = array_keys(sArticles::config('types', [])); @endphp
@if(count($types) == 0) @php $types = ['article']; @endphp @endif
@if(sArticles::config('general.filter_types_on', 1) == 1 && count($types) > 1)
    <div class="btn-group mt-2 sarticles-filter-group">
        @if((request()->type ?? "") == '')
            <a href="{{$buildArticleListUrl(['type' => null])}}" class="btn btn-outline-primary"><span>@lang('sArticles::global.to_list_publications')</span></a>
        @else
            <a href="{{$buildArticleListUrl(['type' => null])}}" class="btn btn-outline-secondary"><span>@lang('sArticles::global.to_list_publications')</span></a>
        @endif
        @foreach($types as $type)
            @if($type == request()->type ?? "")
                <a href="{{$buildArticleListUrl(['type' => $type])}}" class="btn btn-outline-primary"><span>@lang('sArticles::global.to_list') {{sArticles::config('types.'.$type.'.to_button_text', __('sArticles::global.add_article'))}}</span></a>
            @else
                <a href="{{$buildArticleListUrl(['type' => $type])}}" class="btn btn-outline-secondary"><span>@lang('sArticles::global.to_list') {{sArticles::config('types.'.$type.'.to_button_text', __('sArticles::global.add_article'))}}</span></a>
            @endif
        @endforeach
    </div>
@endif
<div class="table-responsive sarticles-list-table">
    <table class="table table-condensed table-hover sectionTrans">
        <thead>
        <tr>
            <th class="sarticles-th-cover" aria-label="@lang('global.image')"></th>
            <th class="sarticles-th-name">@lang('global.name')</th>
            <th class="sarticles-th-section">@lang('sArticles::global.section')</th>
            <th class="sarticles-th-meta">@lang('sArticles::global.categories')</th>
            <th class="sarticles-th-meta">@lang('sArticles::global.tags')</th>
            <th class="sarticles-th-meta">@lang('sArticles::global.features')</th>
            <th class="sarticles-th-views">@lang('sArticles::global.views')</th>
            @if(is_array($html = evo()->invokeEvent('sArticlesManagerAddAfterEvent', ['field' => 'views_head', 'item' => null, 'type' => $checkType, 'tab' => 'articles'])))
                {!!implode('', $html)!!}
            @endif
            <th id="action-btns" class="sarticles-th-actions">@lang('global.onlineusers_action')</th>
        </tr>
        </thead>
        <tbody>
        @php $articles = sArticles::all(); @endphp
        @php $parents = \EvolutionCMS\Models\SiteContent::select('id', 'pagetitle')->whereIn('id', $articles->pluck('parent')->unique()->toArray())->get()->pluck('pagetitle', 'id')->toArray(); @endphp
        @foreach($articles as $article)
            @php
                $articleCategories = $article->categories->map($taxonomyLabel)->filter()->values();
                $articleTags = $article->tags->map($taxonomyLabel)->filter()->values();
                $articleFeatures = $article->features->map($taxonomyLabel)->filter()->values();
                $renderMetaList = function ($items) {
                    if (!$items->count()) {
                        return '<span class="sarticles-meta-empty">-</span>';
                    }

                    return $items->map(fn($item) => '<span class="sarticles-meta-chip">' . e($item) . '</span>')->implode('');
                };
            @endphp
            <tr class="sarticles-editable-row" data-edit-url="{{$url}}&get=article&type={{$article->type}}&i={{$article->id}}">
                <td class="sarticles-cover-cell">
                    <img src="{{$article->coverSrc}}" alt="{{$article->coverSrc}}" class="post-thumbnail">
                </td>
                <td class="sarticles-title-cell">
                    <a href="{{$article->link}}" target="_blank"><b>{{$article->pagetitle ?? __('sArticles::global.no_text')}}</b></a>
                </td>
                <td class="sarticles-section-cell">
                    @if($article->parent > 1)
                        <a href="@makeUrl($article->parent)" target="_blank">{{$parents[$article->parent]}}</a>
                    @else
                        <a href="@makeUrl(1)" target="_blank">{{evo()->getConfig('site_name')}}</a>
                    @endif
                </td>
                <td class="sarticles-meta-cell"><div class="sarticles-meta-list">{!! $renderMetaList($articleCategories) !!}</div></td>
                <td class="sarticles-meta-cell"><div class="sarticles-meta-list">{!! $renderMetaList($articleTags) !!}</div></td>
                <td class="sarticles-meta-cell"><div class="sarticles-meta-list">{!! $renderMetaList($articleFeatures) !!}</div></td>
                <td class="sarticles-status-cell">
                    <span class="badge badge-dark">{{$article->views}}</span>
                </td>
                @if(is_array($html = evo()->invokeEvent('sArticlesManagerAddAfterEvent', ['field' => 'views', 'item' => $article, 'type' => $checkType, 'tab' => 'articles'])))
                    {!!implode('', $html)!!}
                @endif
                <td style="text-align:center;">
                    <div class="btn-group">
                        @if($article->published)
                            <button type="button"
                                    class="btn btn-outline-success btn-icon js__publish_article"
                                    data-id="{{$article->id}}"
                                    data-value="0"
                                    title="@lang('sArticles::global.unpublish_article')"
                                    aria-label="@lang('sArticles::global.unpublish_article')">
                                {!! svg('tabler-eye-off', 'sarticles-icon sarticles-icon-hide')->toHtml() !!}
                                {!! svg('tabler-eye', 'sarticles-icon sarticles-icon-show')->toHtml() !!}
                            </button>
                        @else
                            <button type="button"
                                    class="btn btn-outline-danger btn-icon js__publish_article"
                                    data-id="{{$article->id}}"
                                    data-value="1"
                                    title="@lang('sArticles::global.publish_article')"
                                    aria-label="@lang('sArticles::global.publish_article')">
                                {!! svg('tabler-eye-off', 'sarticles-icon sarticles-icon-hide')->toHtml() !!}
                                {!! svg('tabler-eye', 'sarticles-icon sarticles-icon-show')->toHtml() !!}
                            </button>
                        @endif
                        <a href="{{$url}}&get=article&type={{$article->type}}&i={{$article->id}}"
                           class="btn btn-primary btn-icon"
                           title="@lang('global.edit')"
                           aria-label="@lang('global.edit')">
                            {!! svg('tabler-edit', 'sarticles-icon')->toHtml() !!}
                        </a>
                        <a href="#"
                           data-href="{{$url}}&get=articleDelete&i={{$article->id}}"
                           data-delete="{{$article->id}}"
                           data-name="{{$article->pagetitle}}"
                           class="btn btn-outline-danger btn-icon"
                           title="@lang('global.remove')"
                           aria-label="@lang('global.remove')">
                            {!! svg('tabler-trash', 'sarticles-icon')->toHtml() !!}
                        </a>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="paginator">{{$articles->render()}}</div>

@push('scripts.bot')
    <div id="actions">
        <div class="btn-group">
            <div class="dropdown">
                <a href="{!!$url!!}&get=article&type={{$checkType}}&i=0" class="btn btn-primary" title="{{$addArticleLabel}}" aria-label="{{$addArticleLabel}}">
                    {!! svg('tabler-plus', 'sarticles-icon')->toHtml() !!} <span>{{$addArticleLabel}}</span>
                </a>
                @if(sArticles::config('general.filter_types_on', 1) == 1 && count($types) > 1)
                    <div class="dropdown-menu">
                        @foreach($types as $type)
                            @if($type != $checkType)
                                <a href="{!!$url!!}&get=article&type={{$type}}&i=0" class="btn btn-primary dropdown-item">
                                    @lang('global.add') {{sArticles::config('types.'.$type.'.add_button_text', __('sArticles::global.add_article'))}}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    <script>
        function sArticlesApplySearch() {
            var input = jQuery(document).find("[name=\"search\"]");
            var target = new URL(window.location.href);
            var value = input.val();

            if (value) {
                target.searchParams.set('search', value);
            } else {
                target.searchParams.delete('search');
            }
            target.searchParams.delete('page');

            sArticlesLoadList(target.toString());
        }

        function sArticlesReplaceNode(doc, selector) {
            var current = document.querySelector(selector);
            var fresh = doc.querySelector(selector);

            if (current && fresh) {
                current.replaceWith(fresh);
            } else if (current && !fresh) {
                current.remove();
            }
        }

        function sArticlesLoadList(targetUrl, pushState) {
            pushState = pushState !== false;

            var target = new URL(targetUrl, window.location.href);
            target.searchParams.set("get", "articles");
            var table = document.querySelector(".sarticles-list-table");
            var listbar = document.querySelector(".sarticles-listbar");

            if (table) {
                table.classList.add("is-loading");
            }
            if (listbar) {
                listbar.classList.add("is-loading");
            }

            fetch(target.toString(), {
                method: "GET",
                cache: "no-store",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            }).then(function (response) {
                return response.text();
            }).then(function (html) {
                var doc = new DOMParser().parseFromString(html, "text/html");

                if (!doc.querySelector(".sarticles-list-table")) {
                    window.location.href = target.toString();
                    return;
                }

                sArticlesReplaceNode(doc, ".sarticles-listbar");
                sArticlesReplaceNode(doc, ".sarticles-filter-group");
                sArticlesReplaceNode(doc, ".sarticles-list-table");
                sArticlesReplaceNode(doc, ".paginator");

                jQuery("[data-filter-menu]").removeClass("is-open").find(".sarticles-filter-menu__toggle").attr("aria-expanded", "false");

                if (typeof sArticlesViewKey === "function") {
                    window.sArticlesActiveViewKey = sArticlesViewKey(target.toString());
                }

                if (pushState) {
                    window.history.pushState({sarticlesView: true}, "", target.toString());
                } else {
                    window.history.replaceState({sarticlesView: true}, "", target.toString());
                }
            }).catch(function (error) {
                console.error("Request failed", error, ".");
                window.location.href = target.toString();
            });
        }

        jQuery(document).on("click", ".sarticles-search__icon", sArticlesApplySearch);
        jQuery(document).on("dblclick", ".sarticles-editable-row", function (event) {
            if (jQuery(event.target).closest("a, button, input, select, textarea, label, [role=\"button\"]").length) {
                return;
            }

            var editUrl = jQuery(this).data("edit-url");
            if (editUrl) {
                if (typeof sArticlesLoadModuleView === "function") {
                    sArticlesLoadModuleView(editUrl);
                } else {
                    window.location.href = editUrl;
                }
            }
        });
        jQuery(document).on("click", ".js__publish_article", function (event) {
            event.preventDefault();

            var button = jQuery(this);
            if (button.data("loading")) {
                return;
            }

            var formData = new FormData();
            formData.append("article_id", button.attr("data-id"));
            formData.append("published", button.attr("data-value"));

            button.data("loading", true).prop("disabled", true);

            fetch((window.sArticlesAdminConfig && window.sArticlesAdminConfig.routes && window.sArticlesAdminConfig.routes.articlePublish) || "/sarticles/article-publish", {
                method: "POST",
                cache: "no-store",
                body: formData
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (!data || !data.article) {
                    return;
                }

                if (data.article.published == 1) {
                    button.removeData("value");
                    button.attr("data-value", 0);
                    button.attr("title", "@lang('sArticles::global.unpublish_article')");
                    button.attr("aria-label", "@lang('sArticles::global.unpublish_article')");
                    button.removeClass("btn-outline-danger").addClass("btn-outline-success");
                } else {
                    button.removeData("value");
                    button.attr("data-value", 1);
                    button.attr("title", "@lang('sArticles::global.publish_article')");
                    button.attr("aria-label", "@lang('sArticles::global.publish_article')");
                    button.removeClass("btn-outline-success").addClass("btn-outline-danger");
                }
            }).catch(function (error) {
                console.error("Request failed", error, ".");
            }).finally(function () {
                button.data("loading", false).prop("disabled", false);
            });
        });
        jQuery(document).on("click", "[data-filter-menu] .sarticles-filter-menu__toggle", function (event) {
            event.preventDefault();
            event.stopPropagation();

            var menu = jQuery(this).closest("[data-filter-menu]");
            jQuery("[data-filter-menu]").not(menu).removeClass("is-open").find(".sarticles-filter-menu__toggle").attr("aria-expanded", "false");
            menu.toggleClass("is-open");
            jQuery(this).attr("aria-expanded", menu.hasClass("is-open") ? "true" : "false");
            if (menu.hasClass("is-open")) {
                menu.find("[data-filter-search]").trigger("focus");
            }
        });
        jQuery(document).on("click", ".sarticles-filter-menu__panel", function (event) {
            event.stopPropagation();
        });
        jQuery(document).on("click", function () {
            jQuery("[data-filter-menu]").removeClass("is-open").find(".sarticles-filter-menu__toggle").attr("aria-expanded", "false");
        });
        jQuery(document).on("keydown", function (event) {
            if (event.key === "Escape") {
                jQuery("[data-filter-menu]").removeClass("is-open").find(".sarticles-filter-menu__toggle").attr("aria-expanded", "false");
            }
        });
        jQuery(document).on("input", "[data-filter-search]", function () {
            var value = jQuery(this).val().toLowerCase();
            jQuery(this).closest("[data-filter-menu]").find("[data-filter-option]").each(function () {
                jQuery(this).toggle(jQuery(this).text().toLowerCase().indexOf(value) !== -1);
            });
        });
        jQuery(document).on("click", "[data-filter-select-all]", function () {
            jQuery(this).closest("[data-filter-menu]").find("[data-filter-option]:visible input[type=\"checkbox\"]").prop("checked", true);
        });
        jQuery(document).on("click", "[data-filter-clear]", function () {
            jQuery(this).closest("[data-filter-menu]").find("input[type=\"checkbox\"]").prop("checked", false);
        });
        jQuery(document).on("click", "[data-filter-apply]", function () {
            var target = new URL(window.location.href);
            var menu = jQuery(this).closest("[data-filter-menu]");
            var key = menu.data("filter-key");
            var values = menu.find("input[type=\"checkbox\"]:checked").map(function () {
                return jQuery(this).val();
            }).get();

            if (values.length) {
                target.searchParams.set(key, values.join(","));
            } else {
                target.searchParams.delete(key);
            }
            target.searchParams.delete('page');

            sArticlesLoadList(target.toString());
        });
        jQuery(document).on("click", ".articlesTab .sarticles-segmented__item, .articlesTab .sarticles-filter-group a, .articlesTab .paginator a", function (event) {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.which === 2) {
                return;
            }

            event.preventDefault();
            sArticlesLoadList(jQuery(this).attr("href"));
        });
        jQuery(document).on('keydown', "[name=\"search\"]", function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sArticlesApplySearch();
            }
        });
    </script>
@endpush
