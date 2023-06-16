<div class="input-group">
    <input type="text" class="form-control" name="search" value="{{request()->search ?? ''}}" placeholder="@lang('global.resource_title') @lang('sArticles::global.or') @lang('global.long_title')" />
    <span class="input-group-btn">
        <button class="btn btn-light js_search" type="button" title="@lang('global.search')" style="padding:0 5px;color:#0275d8;">
            <i class="fa fa-search" style="font-size:large;margin:5px;"></i>
        </button>
    </span>
</div>
<div class="split my-1"></div>
<div class="table-responsive">
    <table class="table table-condensed table-hover sectionTrans">
        <thead>
        <tr>
            <th style="text-align:center;">@lang('global.name')</th>
            <th style="width:110px;text-align:center;">@lang('sArticles::global.section')</th>
            <th style="width:70px;text-align:center;">@lang('sArticles::global.availability')</th>
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
                    <a href="{{$article->link}}" target="_blank"><b>{{$article->pagetitle ?? __('sArticles::global.no_text')}}</b> <small>({{$article->id}})</small></a>
                </td>
                <td>
                    @if($article->parent > 1)
                        <a href="@makeUrl($article->parent)" target="_blank">{{$parents[$article->parent]}} <small>({{$article->parent}})</small></a>
                    @else
                        <a href="@makeUrl(1)" target="_blank">{{evo()->getConfig('site_name')}} <small>({{$article->parent}})</small></a>
                    @endif
                </td>
                <td>
                    @if($article->published)
                        <span class="badge badge-success">@lang('global.page_data_published') <small>({{$article->views}})</small></span>
                    @else
                        <span class="badge badge-dark">@lang('global.page_data_unpublished') <small>({{$article->views}})</small></span>
                    @endif
                </td>
                <td style="text-align:center;">
                    <div class="btn-group">
                        <a href="{{$url}}&get=article&i={{$article->id}}" class="btn btn-outline-success">
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
            <a id="Button2" href="{!!$url!!}&get=article&i=0" class="btn btn-primary" title="@lang('sArticles::global.add_help')">
                <i class="fa fa-plus"></i> <span>@lang('global.add')</span>
            </a>
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
