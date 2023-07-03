<form id="form" name="form" method="post" enctype="multipart/form-data" action="{!!$url!!}&get=contentSave" onsubmit="documentDirty=false;">
    <input type="hidden" name="back" value="&get=content&lang={{request()->lang ?? 'base'}}&i={{request()->i ?? 0}}" />
    <input type="hidden" name="article" value="{{request()->i ?? 0}}" />
    <input type="hidden" name="lang" value="{{request()->lang ?? 'base'}}" />
    <div class="row form-row">
        <div class="row-col col-lg-12 col-12">
            <div class="row form-row">
                <div class="col-auto col-title-9">
                    <label for="pagetitle" class="warning">@lang('global.resource_title')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('global.resource_title_help')"></i>
                </div>
                <div class="col">
                    {{--@if($lang == $sArticlesController->langDefault())--}}
                    <input type="text" id="pagetitle" class="form-control" name="pagetitle" maxlength="255" value="{{$content->pagetitle ?? ''}}" onchange="documentDirty=true;" spellcheck="true"/>
                    {{--@else
                        <div class="input-group">
                            <input type="text" id="pagetitle" class="form-control" name="pagetitle" maxlength="255" value="{{$content->pagetitle ?? ''}}" onchange="documentDirty=true;" spellcheck="true" style="width: calc(100% - 52px);"/>
                            <button data-lang="{{$lang}}" class="btn btn-light js_translate" type="button" title="@lang('sArticles::global.auto_translate') {{strtoupper($sArticlesController->langDefault())}} => {{strtoupper($lang)}}" style="padding:0 5px;color:#0275d8;">
                                <i class="fa fa-language" style="font-size:xx-large;"></i>
                            </button>
                        </div>
                    @endif--}}
                </div>
            </div>
            @if(evo()->getConfig('sart_long_title_on', 1) == 1)
                <div class="row form-row">
                    <div class="col-auto col-title-9">
                        <label for="longtitle" class="warning">@lang('global.long_title')</label>
                    </div>
                    <div class="col">
                        <input type="text" id="longtitle" class="form-control" name="longtitle" maxlength="255" value="{{$content->longtitle ?? ''}}" onchange="documentDirty=true;" spellcheck="true">
                    </div>
                </div>
            @endif
            <div class="row form-row">
                <div class="col-auto col-title-9">
                    <label for="introtext" class="warning">@lang('global.resource_summary')</label>
                </div>
                <div class="col">
                    <textarea id="introtext" class="form-control" name="introtext" rows="3" wrap="soft" onchange="documentDirty=true;">{{$content->introtext ?? ''}}</textarea>
                </div>
            </div>
            <div class="row form-row form-row-richtext">
                <div class="col-auto col-title-9">
                    <div class="sbuttons-wraper">
                        <label for="content" class="warning">@lang('sArticles::global.add_block')</label><br><br>
                        @foreach($buttons as $button){!! $button !!}<br><br>@endforeach
                    </div>
                </div>
                <div id="builder" class="col builder">
                    @if(count($chunks))
                        @foreach($chunks as $chunk)
                            <div class="row col row-col-wrap col-12 b-draggable">
                                <div class="col-12 b-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto"><i title="@lang('sArticles::global.sort_order')" class="fa fa-sort b-move"></i></div>
                                        <div class="col">{!! $chunk !!}</div>
                                        <div class="col-auto"><i title="@lang('global.remove')" onclick="onDeleteField($(this))" class="fa fa-minus-circle text-danger b-btn-del"></i></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="row col row-col-wrap col-12 b-draggable">
                            <div class="col-12 b-item">
                                <div class="row align-items-center">
                                    <div class="col-auto"><i title="@lang('sArticles::global.sort_order')" class="fa fa-sort b-move"></i></div>
                                    <div class="col">
                                        <div class="col"><textarea id="richtext1" name="builder[1][richtext]" rows="3" onchange="documentDirty=true;"></textarea></div>
                                    </div>
                                    <div class="col-auto"><i title="@lang('global.remove')" onclick="onDeleteField($(this))" class="fa fa-minus-circle text-danger b-btn-del"></i></div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <i class="b-resize b-resize-r"></i>
                </div>
            </div>
            @foreach($constructor as $item)
                <div class="row form-row">
                    <div class="col-auto col-title">
                        <label for="{{$item['key']}}" class="warning">{{$item['name']}}</label>
                    </div>
                    <div class="col">
                        @switch($item['type'])
                            @case('Text')
                                <input id="{{$item['key']}}" name="constructor[{{$item['key']}}]" value="{{$item['value']}}" class="form-control" type="text" onchange="documentDirty=true;">
                                @break
                            @case('Image')
                                <div class="input-group mb-3">
                                    <input id="{{$item['key']}}" name="constructor[{{$item['key']}}]" value="{{$item['value']}}" class="form-control" type="text" onchange="documentDirty=true;">
                                    <div class="input-group-append">
                                        <button onclick="BrowseServer('{{$item['key']}}')" class="btn btn-outline-secondary" type="button">@lang('global.insert')</button>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div id="image_for_{{$item['key']}}" data-image="{{$item['value']}}" onclick="BrowseServer('{{$item['key']}}')" class="image_for_field" style="background-image: url('{{MODX_SITE_URL . $item['value']}}');"></div>
                                    <script>document.getElementById('{{$item['key']}}').addEventListener('change', evoRenderImageCheck, false);</script>
                                </div>
                                @break
                            @default
                                <textarea id="{{$item['key']}}" class="form-control" name="constructor[{{$item['key']}}]" rows="3" wrap="soft" onchange="documentDirty=true;">{{$item['value']}}</textarea>
                        @endswitch
                    </div>
                </div>
            @endforeach
        </div>
        <div class="split my-2"></div>
        <div class="row-col col-lg-12 col-12">
            <div class="row form-row">
                <div class="col-auto col-title-9">
                    @if(trim(evo()->getConfig('sart_name_seotitle', '')))
                        <label for="seotitle" class="warning">{{evo()->getConfig('sart_name_seotitle', '')}}</label>
                    @else
                        <label for="seotitle" class="warning">@lang('sArticles::global.seotitle')</label>
                    @endif
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.seotitle_help')"></i>
                </div>
                <div class="col">
                    <div class="input-group">
                        <input type="text" id="seotitle" class="form-control" name="seotitle" value="{{$content->seotitle ?? ''}}" onchange="documentDirty=true;">
                    </div>
                </div>
            </div>
            <div class="row form-row">
                <div class="col-auto col-title-9">
                    @if(trim(evo()->getConfig('sart_name_seodescription', '')))
                        <label for="seotitle" class="warning">{{evo()->getConfig('sart_name_seodescription', '')}}</label>
                    @else
                        <label for="seotitle" class="warning">@lang('sArticles::global.seodescription')</label>
                    @endif
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.seodescription_help')"></i>
                </div>
                <div class="col">
                    <div class="input-group">
                        <textarea id="seodescription" class="form-control" name="seodescription" rows="3" wrap="soft" onchange="documentDirty=true;">{{$content->seodescription ?? ''}}</textarea>
                    </div>
                </div>
            </div>
            <div class="row form-row">
                <div class="col-auto col-title-9">
                    <label for="seorobots" class="warning">@lang('sArticles::global.seorobots')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.seorobots_help')"></i>
                </div>
                <div class="col">
                    <select id="seorobots" class="form-control" name="seorobots" onchange="documentDirty=true;">
                        @foreach(['index,follow', 'noindex,nofollow'] as $value)
                            <option value="{{$value}}" @if($content->seorobots ?? 'index,follow' == $value) selected @endif>{{$value}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="split my-2"></div>
        </div>
    </div>
</form>

@push('scripts.bot')
    <div id="actions">
        <div class="btn-group">
            <a id="Button5" class="btn btn-secondary" href="{!!$url!!}">
                <i class="fa fa-times-circle"></i><span>@lang('sArticles::global.to_list_articles')</span>
            </a>
            <a id="Button1" class="btn btn-success" href="javascript:void(0);" onclick="saveForm('#form');">
                <i class="fa fa-floppy-o"></i>
                <span>@lang('global.save')</span>
            </a>
            <a id="Button3" class="btn btn-danger" data-href="{{$url}}&get=articleDelete&i={{request()->i ?? 0}}" data-toggle="modal" data-target="#confirmDelete" data-id="{{request()->i ?? 0}}" data-name="{{$content->pagetitle ?? ''}}">
                <i class="fa fa-trash"></i> <span>@lang('global.remove')</span>
            </a>
        </div>
    </div>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/bootstrap.min.css"/>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-sortablejs@latest/jquery-sortable.js"></script>
    <div class="modal fade" id="confirmDelete" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">@lang('sArticles::global.confirm_delete')</div>
                <div class="modal-body">
                    @lang('sArticles::global.you_sure') <b id="confirm-name"></b> @lang('sArticles::global.with_id') <b id="confirm-id"></b>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('global.cancel')</button>
                    <a class="btn btn-danger btn-ok">@lang('global.remove')</a>
                </div>
            </div>
        </div>
    </div>
    <div class="draft-elements">
        @foreach($elements as $element)
            <div class="element">
                <div class="row col row-col-wrap col-12 b-draggable">
                    <div class="col-12 b-item">
                        <div class="row align-items-center">
                            <div class="col-auto"><i title="@lang('sArticles::global.sort_order')" class="fa fa-sort b-move"></i></div>
                            <div class="col">{!! $element !!}</div>
                            <div class="col-auto"><i title="@lang('global.remove')" onclick="onDeleteField($(this))" class="fa fa-minus-circle text-danger b-btn-del"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <style>.draft-elements{display:none;}</style>
    <script>
        $(document).on("click","[data-element]",function(){
            let attr=$(this).attr('data-element');
            if (attr=='richtext'){tinymce.remove()}
            let cnts=$('.builder').find('.b-draggable').length+1;
            let elem=$('#'+attr).closest('.element').html();
            let enew=elem.replace('id="'+attr+'"','id="'+attr+cnts+'"')
                .replace('id="image_for_'+attr+'"','id="image_for_'+attr+cnts+'"')
                .replace('BrowseServer(\''+attr+'\')','BrowseServer(\''+attr+cnts+'\')')
                .replace('BrowseServer(\''+attr+'\')','BrowseServer(\''+attr+cnts+'\')')
                .replace('getElementById(\''+attr+'\')','getElementById(\''+attr+cnts+'\')')
                .replace(/builder\[9999\]\[/g,'builder['+cnts+'][');
            $(".b-resize").before(enew);documentDirty=true;
            if(attr=='richtext'){tinymce.init({{evo()->getConfig('tinymce5_theme')??'custom'}})}
        });
        sortableTabs();
        function sortableTabs(){$('#builder').sortable({animation:150,onChange:function(){
            tinymce.remove();
            $('#builder').find('.b-draggable').each(function(index){
                let parent=$('#builder').find('.b-draggable').eq(index);
                let elemId=parent.find('[name^="builder\["]').first().attr('name').replace("builder[","").split("][")[0];
                parent.find('.b-item [name^="builder\['+elemId+'\]"]').each(function(position){
                    this.name = this.name.replace("builder["+elemId+"]","builder["+index+"]");
                })
            });
            tinymce.init({{evo()->getConfig('tinymce5_theme')??'custom'}})}
        })}
        function onDeleteField(target){let parent=target.closest('.b-draggable');alertify.confirm("@lang('sSettings::global.are_you_sure')","@lang('sSettings::global.deleted_irretrievably')",function(){alertify.error("@lang('sSettings::global.deleted')");parent.remove()},function(){alertify.success("@lang('sSettings::global.canceled')")}).set('labels',{ok:"@lang('global.delete')",cancel:"@lang('global.cancel')"}).set({transition:'zoom'});documentDirty=true}
    </script>
@endpush
