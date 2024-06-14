<div class="input-group">
    <input type="text" class="form-control" name="search" value="{{request()->search ?? ''}}" placeholder="@lang('global.resource_title') @lang('sArticles::global.or') @lang('global.long_title')" />
    <span class="input-group-btn">
        <button class="btn btn-light js_search" type="button" title="@lang('global.search')" style="padding:0 5px;color:#0275d8;">
            <i class="fa fa-search" style="font-size:large;margin:5px;"></i>
        </button>
    </span>
</div>
@php($types = array_keys(sArticles::config('types', [])))
@if(count($types) == 0) @php($types = ['article']) @endif
@if(sArticles::config('general.filter_types_on', 1) == 1 && count($types) > 1)
    <div class="btn-group mt-2">
        @if((request()->type ?? "") == '')
            <a href="{{$url}}&get=articles" class="btn btn-outline-primary"><span>@lang('sArticles::global.to_list_publications')</span></a>
        @else
            <a href="{{$url}}&get=articles" class="btn btn-outline-secondary"><span>@lang('sArticles::global.to_list_publications')</span></a>
        @endif
        @foreach($types as $type)
            @if($type == request()->type ?? "")
                <a href="{{$url}}&get=articles&type={{$type}}" class="btn btn-outline-primary"><span>@lang('sArticles::global.to_list') {{sArticles::config('types.'.$type.'.to_button_text', __('sArticles::global.add_article'))}}</span></a>
            @else
                <a href="{{$url}}&get=articles&type={{$type}}" class="btn btn-outline-secondary"><span>@lang('sArticles::global.to_list') {{sArticles::config('types.'.$type.'.to_button_text', __('sArticles::global.add_article'))}}</span></a>
            @endif
        @endforeach
    </div>
@endif
<div class="split my-1"></div>
<div class="table-responsive">
    <table class="table table-condensed table-hover sectionTrans">
        <thead>
        <tr>
            <th style="text-align:center;">@lang('global.name')</th>
            <th style="width:110px;text-align:center;">@lang('sArticles::global.section')</th>
            <th style="width:30px;text-align:center;">@lang('sArticles::global.views')</th>
            @if(is_array($html = evo()->invokeEvent('sArticlesManagerAddAfterEvent', ['field' => 'views_head', 'item' => null, 'type' => $checkType, 'tab' => 'articles'])))
                {!!implode('', $html)!!}
            @endif
            <th style="width:105px;text-align:center;">@lang('sArticles::global.availability')</th>
            <th id="action-btns">@lang('global.onlineusers_action')</th>
        </tr>
        </thead>
        <tbody>
        @php($articles = sArticles::all())
        @php($parents = \EvolutionCMS\Models\SiteContent::select('id', 'pagetitle')->whereIn('id', $articles->pluck('parent')->unique()->toArray())->get()->pluck('pagetitle', 'id')->toArray())
        @foreach($articles as $article)
            <tr>
                <td>
                    <img src="{{$article->coverSrc}}" alt="{{$article->coverSrc}}" class="post-thumbnail">
                    <a href="{{$article->link}}" target="_blank"><b>{{$article->pagetitle ?? __('sArticles::global.no_text')}}</b></a>
                </td>
                <td>
                    @if($article->parent > 1)
                        <a href="@makeUrl($article->parent)" target="_blank">{{$parents[$article->parent]}}</a>
                    @else
                        <a href="@makeUrl(1)" target="_blank">{{evo()->getConfig('site_name')}}</a>
                    @endif
                </td>
                <td>
                    <span class="badge badge-dark">{{$article->views}}</span>
                </td>
                @if(is_array($html = evo()->invokeEvent('sArticlesManagerAddAfterEvent', ['field' => 'views', 'item' => $article, 'type' => $checkType, 'tab' => 'articles'])))
                    {!!implode('', $html)!!}
                @endif
                <td>
                    @if($article->published)
                        <span class="badge badge-success">@lang('global.page_data_published')</span>
                    @else
                        <span class="badge badge-dark">@lang('global.page_data_unpublished')</span>
                    @endif
                </td>
                <td style="text-align:center;">
                    <div class="btn-group">
                        <a href="{{$url}}&get=article&type={{$article->type}}&i={{$article->id}}" class="btn btn-outline-success">
                            <i class="fa fa-pencil"></i> <span>@lang('global.edit')</span>
                        </a>
                        <a href="#" data-href="{{$url}}&get=articleDelete&i={{$article->id}}" data-delete="{{$article->id}}" data-name="{{$article->pagetitle}}" class="btn btn-outline-danger">
                            <i class="fa fa-trash"></i> <span>@lang('global.remove')</span>
                        </a>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="split my-1"></div>
<div class="paginator">{{$articles->render()}}</div>

@push('scripts.bot')
    <div id="actions">
        <div class="btn-group">
            <div class="dropdown">
                <a href="{!!$url!!}&get=article&type={{$checkType}}&i=0" class="btn btn-primary" title="@lang('sArticles::global.add_help') {{sArticles::config('types.'.$checkType.'.add_button_text', __('sArticles::global.add_article'))}}">
                    <i class="fa fa-plus"></i> <span>@lang('global.add') {{sArticles::config('types.'.$checkType.'.add_button_text', __('sArticles::global.add_article'))}}</span>
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
        jQuery(document).on("click", ".js_search", function () {
            var _form = jQuery(document).find("[name=\"search\"]");
            window.location.href = window.location.href+'&'+_form.serialize();
        });
        jQuery(document).on('keypress', "[name=\"search\"]", function(e) {
            if (e.which == 13) {
                var _form = jQuery(document).find("[name=\"search\"]");
                window.location.href = window.location.href+'&'+_form.serialize();
            }
        });
    </script>
@endpush
