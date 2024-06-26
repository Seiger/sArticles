<h3>{{(request()->i ?? 0) == 0 ? __('sArticles::global.add_help') .' '. sArticles::config('types.'.$checkType.'.add_button_text', __('sArticles::global.add_article')) : ($article->pagetitle ?? __('sArticles::global.no_text'))}}</h3>
<div class="split my-3"></div>

<form id="form" name="form" method="post" enctype="multipart/form-data" action="{!!$url!!}&get=articleSave" onsubmit="documentDirty=false;">
    <input type="hidden" name="back" value="&get=article&type={{$checkType}}&i={{request()->i ?? 0}}" />
    <input type="hidden" name="type" value="{{$checkType}}" />
    <input type="hidden" name="article" value="{{request()->i ?? 0}}" />
    <div class="row form-row">
        <div class="row-col col-lg-3 col-md-3 col-12">
            <div class="row form-row form-row-checkbox">
                <div class="col-auto col-title">
                    <label for="publishedcheck" class="warning">@lang('global.resource_opt_published')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.published_help')"></i>
                </div>
                <div class="col">
                    <input type="checkbox" id="publishedcheck" class="form-checkbox form-control" name="publishedcheck" value="" onchange="documentDirty=true;" onclick="changestate(document.form.published);" @if(isset($article->published) && $article->published) checked @endif>
                    <input type="hidden" id="published" name="published" value="{{$article->published ?? 0}}" onchange="documentDirty=true;">
                    &emsp;<i class="fa fa-eye" data-tooltip="@lang('sArticles::global.article_views')"> <b>{{$article->views ?? 0}}</b></i>
                    @if(sArticles::config('general.rating_on', 1) == 1)&emsp;<i class="fa fa-star" data-tooltip="@lang('sArticles::global.rating')"> <b>{{$article->rating ?? 0}}</b></i>@endif
                </div>
            </div>
        </div>
        <div class="row-col col-lg-3 col-md-3 col-12">
            <div class="row form-row">
                <div class="col-auto col-title-6">
                    <label for="parent" class="warning">@lang('sArticles::global.section')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('global.resource_parent_help')"></i>
                </div>
                <div class="col">
                    <div>
                        @php($parentlookup = false)
                        @if(($article->parent ?? 0) == 0)
                            @php($parentname = evo()->getConfig('site_name'))
                        @else
                            @php($parentlookup = ($article->parent ?? 0))
                        @endif
                        @if($parentlookup !== false && is_numeric($parentlookup))
                            @php($parentname = \EvolutionCMS\Models\SiteContent::withTrashed()->select('pagetitle')->find($parentlookup)->pagetitle)
                            @if(!$parentname)
                                @php(evo()->webAlertAndQuit($_lang["error_no_parent"]))
                            @endif
                        @endif
                        <i id="plock" class="fa fa-folder" onclick="enableParentSelection(!allowParentSelection);"></i>
                        <b id="parentName">{{entities($parentname)}}</b>
                        <input type="hidden" name="parent" value="{{($article->parent ?? 0)}}" onchange="documentDirty=true;" />
                    </div>
                </div>
            </div>
        </div>
        @if(sArticles::config('types.'.$checkType.'.publish_date_on', 1) == 1)
            <div class="row-col col-lg-3 col-md-6 col-12">
                <div class="row form-row form-row-date">
                    <div class="col-auto col-title-9">
                        <label for="published_at">@lang('global.publish_date')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.published_at_help')"></i>
                    </div>
                    <div class="col">
                        <input id="published_at" class="form-control DatePicker" name="published_at" value="{{$article->published_at ?? ''}}" onblur="documentDirty=true;" placeholder="dd-mm-YYYY hh:mm:ss" autocomplete="off">
                        <span class="input-group-append">
                        <a class="btn text-danger" href="javascript:(0);" onclick="document.form.published_at.value='';documentDirty=true; return true;">
                            <i class="fa fa-calendar-times-o" title="@lang('global.remove_date')"></i>
                        </a>
                    </span>
                    </div>
                </div>
            </div>
        @endif
        @if(is_array($html = evo()->invokeEvent('sArticlesManagerAddAfterEvent', ['field' => 'published_at', 'item' => $article, 'type' => $checkType, 'tab' => 'article'])))
            {!!implode('', $html)!!}
        @endif
        @if(sArticles::config('general.authors_on', 1) == 1)
            <div class="row-col col-lg-3 col-md-6 col-12">
                <div class="row form-row">
                    <div class="col-auto col-title-6">
                        <label for="author_id" class="warning">@lang('sArticles::global.author')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.author_help')"></i>
                    </div>
                    <div class="col">
                        <select id="author_id" class="form-control select2" name="author_id" onchange="documentDirty=true;">
                            @foreach(\Seiger\sArticles\Models\sArticlesAuthor::orderBy('base_name')->get() as $user)
                                <option value="{{$user->autid}}" @if($article->author_id == $user->autid) selected @endif>{{$user->base_name}} {{$user->base_lastname}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @endif
        @if(is_array($html = evo()->invokeEvent('sArticlesManagerAddAfterEvent', ['field' => 'author', 'item' => $article, 'type' => $checkType, 'tab' => 'article'])))
            {!!implode('', $html)!!}
        @endif
        @if(sArticles::config('general.categories_on', 1) == 1)
            <div class="row-col col-lg-6 col-md-6 col-12">
                <div class="row form-row">
                    <div class="col-auto col-title">
                        <label for="categories">@lang('sArticles::global.categories')</label>
                    </div>
                    <div class="col">
                        @php($article->category = $article->categories->pluck('catid')->toArray())
                        <select id="categories" class="form-control select2" name="categories[]" multiple onchange="documentDirty=true;">
                            @foreach($categories as $category)
                                <option value="{{$category->catid}}" @if(in_array($category->catid, $article->category)) selected @endif>{{$category->base}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @endif
        @if(sArticles::config('general.features_on', 1) == 1)
            <div class="row-col col-lg-6 col-md-6 col-12">
                <div class="row form-row">
                    <div class="col-auto col-title">
                        <label for="features" class="warning">@lang('sArticles::global.features')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.features_article_help')"></i>
                    </div>
                    <div class="col">
                        @php($article->feature = $article->features->pluck('fid')->toArray())
                        @if(is_array($value = evo()->invokeEvent('sArticlesManagerValueEvent', ['field' => 'features', 'item' => $features, 'type' => $checkType, 'tab' => 'article'])))
                            @php($features = $value[0])
                        @endif
                        <select id="features" class="form-control select2" name="features[]" multiple onchange="documentDirty=true;">
                            @foreach($features as $feature)
                                <option value="{{$feature->fid}}" @if(in_array($feature->fid, $article->feature)) selected @endif>{{$feature->base}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @endif
        @if(sArticles::config('general.tags_on', 1) == 1)
            <div class="row-col col-lg-6 col-md-6 col-12">
                <div class="row form-row">
                    <div class="col-auto col-title">
                        <label for="tags">@lang('sArticles::global.main_tag_article')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.tags_article_help')"></i>
                    </div>
                    <div class="col">
                        @php($article->tag = $article->tags()->pluck('tagid')->toArray())
                        <select id="type" class="form-control select2" name="tags[]" onchange="documentDirty=true;">
                            <option></option>
                            @foreach($tags as $tag)
                                <option value="{{$tag->tagid}}" @if($tag->tagid == $article->tag[0]) selected @endif>{{$tag->base}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="row-col col-lg-6 col-md-6 col-12">
                <div class="row form-row">
                    <div class="col-auto col-title">
                        <label for="tags">@lang('sArticles::global.tags_article')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.tags_article_help')"></i>
                    </div>
                    <div class="col">
                        <select id="type" class="form-control select2" name="tags[]" multiple onchange="documentDirty=true;">
                            @foreach($tags as $tag)
                                <option value="{{$tag->tagid}}" @if(in_array($tag->tagid, $article->tag)) selected @endif>{{$tag->base}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @endif
        @if(sArticles::config('general.relevants_on', 1) == 1)
            <div class="row-col col-lg-6 col-md-6 col-12">
                <div class="row form-row">
                    <div class="col-auto col-title">
                        <label for="relevants" class="warning">@lang('sArticles::global.relevant_articles')</label>
                    </div>
                    <div class="col">
                        @php($articleRelevants = data_is_json($article->relevants ?? '', true) ?: [])
                        <select id="relevants" class="form-control select2" name="relevants[]" multiple onchange="documentDirty=true;">
                            @foreach(sArticles::all() as $item)
                                @if(($article->id ?? 0) != $item->id)
                                    <option value="{{$item->id}}" @if(in_array($item->id, $articleRelevants)) selected @endif>{{$item->pagetitle}}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @endif
        @if(is_array($html = evo()->invokeEvent('sArticlesManagerAddAfterEvent', ['field' => 'relevants', 'item' => $article, 'type' => $checkType, 'tab' => 'article'])))
            {!!implode('', $html)!!}
        @endif
        <div class="row-col col-lg-6 col-md-6 col-12">
            <div class="row form-row form-row-image">
                <div class="col-auto col-title">
                    <label for="cover" class="warning">@lang('sArticles::global.image')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.image_help')"></i>
                </div>
                <div class="col">
                    <input type="text" id="cover" class="form-control" name="cover" value="{{$article->cover ?? ''}}" onchange="documentDirty=true;">
                    <input class="form-control" type="button" value="@lang('global.insert')" onclick="BrowseServer('cover')">
                    <div class="col-12">
                        <div id="image_for_cover" class="image_for_field" data-image="{{$article->coverSrc ?? ''}}" onclick="BrowseServer('cover')" style="background-image: url('{{$article->coverSrc ?? ''}}');"></div>
                        <script>document.getElementById('cover').addEventListener('change', evoRenderImageCheck, false);</script>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-col col-lg-6 col-md-6 col-12">
            <div class="row form-row">
                <div class="col-auto col-title-7">
                    <label for="alias" class="warning">@lang('global.resource_alias')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('global.resource_alias_help')"></i>
                </div>
                <div class="input-group col">
                    <input type="text" id="alias" class="form-control" name="alias" maxlength="255" value="{{$article->alias ?? ''}}" onchange="documentDirty=true;" spellcheck="true">
                    <a id="preview" href="{{$article->link ?? '/'}}" class="btn btn-outline-secondary form-control" type="button" target="_blank">@lang('global.preview')</a>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="split my-3"></div>

@push('scripts.bot')
    <div id="actions">
        <div class="btn-group">
            <div class="dropdown">
                <a href="{{$url}}&get=articles&type={{$checkType}}" class="btn btn-secondary">
                    <i class="fa fa-times-circle"></i> <span>@lang('sArticles::global.to_list') {{sArticles::config('types.'.$checkType.'.to_button_text', __('sArticles::global.add_article'))}}</span>
                </a>
                @if(sArticles::config('general.filter_types_on', 1) == 1)
                    <div class="dropdown-menu">
                        <a href="{{$url}}&get=articles" class="btn btn-secondary dropdown-item">
                            @lang('sArticles::global.to_list_publications')
                        </a>
                    </div>
                @endif
            </div>
            <a id="Button1" class="btn btn-success" href="javascript:void(0);" onclick="saveForm('#form');">
                <i class="fa fa-floppy-o"></i>
                <span>@lang('global.save')</span>
            </a>
            <a id="Button3" class="btn btn-danger" data-href="{{$url}}&get=articleDelete&i={{$article->id}}" data-delete="{{$article->id}}" data-name="{{$article->pagetitle}}">
                <i class="fa fa-trash"></i> <span>@lang('global.remove')</span>
            </a>
        </div>
    </div>
@endpush
