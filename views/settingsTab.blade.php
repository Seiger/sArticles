<form id="form" name="form" method="post" enctype="multipart/form-data" action="{!!$url!!}&get=settingsSave" onsubmit="documentDirty=false;">
    <input type="hidden" name="back" value="&get=settings" />
    <h3>@lang('sArticles::global.management_additional_fields')</h3>
    <div class="row form-row widgets sortable">
        @php($settings = require MODX_BASE_PATH . 'core/custom/config/seiger/settings/sArticles.php')
        @foreach($settings as $key => $setting)
            @if(!in_array($key, ['general', 'types']))
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <i style="cursor:pointer;font-size:x-large;" class="fas fa-sort"></i>&emsp; {{$setting['key']}}
                            <span class="close-icon"><i class="fa fa-times"></i></span>
                        </div>
                        <div class="card-block">
                            <div class="userstable">
                                <div class="card-body">
                                    <div class="row form-row">
                                        <div class="col-auto col-title-6">
                                            <label class="warning">@lang('global.name')</label>
                                        </div>
                                        <div class="col">
                                            <input type="text" class="form-control" name="settings[name][]" maxlength="255" value="{{$setting['name']}}" onchange="documentDirty=true;" spellcheck="true">
                                        </div>
                                    </div>
                                    <div class="row form-row">
                                        <div class="col-auto col-title-6">
                                            <label class="warning">@lang('sArticles::global.field_type')</label>
                                        </div>
                                        <div class="col">
                                            <select id="rating" class="form-control" name="settings[type][]" onchange="documentDirty=true;">
                                                @foreach(['Text', 'Textarea', 'RichText', 'File', 'Image'] as $value)
                                                    <option value="{{$value}}" @if($setting['type'] == $value) selected @endif>{{$value}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <input type="hidden" name="settings[key][]" value="{{$setting['key']}}" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
    <div class="split my-3"></div>
    <h3>@lang('sArticles::global.management_base_functionality')</h3>
    <div class="row form-row">
        <div class="row-col col-12">
            <div class="row form-row">
                <div class="col-auto">
                    <label for="parent">@lang('sArticles::global.resource')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.resource_help')"></i>
                </div>
                <div class="col">
                    <div>
                        @php($parentlookup = false)
                        @if(evo()->getConfig('sart_blank', 1) == 0)
                            @php($parentname = evo()->getConfig('site_name'))
                        @else
                            @php($parentlookup = evo()->getConfig('sart_blank', 1))
                        @endif
                        @if($parentlookup !== false && is_numeric($parentlookup))
                            @php($parentname = \EvolutionCMS\Models\SiteContent::withTrashed()->select('pagetitle')->find($parentlookup)->pagetitle)
                            @if(!$parentname)
                                @php(evo()->webAlertAndQuit($_lang["error_no_parent"]))
                            @endif
                        @endif
                        <i id="plock" class="fa fa-folder" onclick="enableParentSelection(!allowParentSelection);"></i>
                        <b id="parentName">{{evo()->getConfig('sart_blank', 1)}} ({{entities($parentname)}})</b>
                        <input type="hidden" name="parent" value="{{evo()->getConfig('sart_blank', 1)}}" onchange="documentDirty=true;" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row form-row">
        <div class="row-col col-12">
            <div class="row form-row">
                <div class="col-auto">
                    <label for="rating_on">@lang('sArticles::global.rating')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.rating')"></i>
                </div>
                <div class="col">
                    <input type="checkbox" id="rating_on_check" class="form-checkbox form-control" name="rating_on_check" value="" onchange="documentDirty=true;" onclick="changestate(document.form.rating_on);" @if(evo()->getConfig('sart_rating_on', 1) == 1) checked @endif>
                    <input type="hidden" id="rating_on" name="rating_on" value="{{evo()->getConfig('sart_rating_on', 1)}}" onchange="documentDirty=true;">
                </div>
            </div>
        </div>
    </div>
    <div class="row form-row">
        <div class="row-col col-12">
            <div class="row form-row">
                <div class="col-auto">
                    <label for="comments_on">@lang('sArticles::global.comments')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.comments_on_off_help')"></i>
                </div>
                <div class="col">
                    <input type="checkbox" id="comments_on_check" class="form-checkbox form-control" name="comments_on_check" value="" onchange="documentDirty=true;" onclick="changestate(document.form.comments_on);" @if(evo()->getConfig('sart_comments_on', 1) == 1) checked @endif>
                    <input type="hidden" id="comments_on" name="comments_on" value="{{evo()->getConfig('sart_comments_on', 1)}}" onchange="documentDirty=true;">
                </div>
            </div>
        </div>
    </div>
    <div class="row form-row">
        <div class="row-col col-12">
            <div class="row form-row">
                <div class="col-auto">
                    <label for="polls_on">@lang('sArticles::global.polls')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.polls_on_off_help')"></i>
                </div>
                <div class="col">
                    <input type="checkbox" id="publishedcheck" class="form-checkbox form-control" name="publishedcheck" value="" onchange="documentDirty=true;" onclick="changestate(document.form.published);" @if(evo()->getConfig('sart_polls_on', 1) == 1) checked @endif>
                    <input type="hidden" id="published" name="polls_on" value="{{evo()->getConfig('sart_polls_on', 1)}}" onchange="documentDirty=true;">
                </div>
            </div>
        </div>
    </div>
    <div class="row form-row">
        <div class="row-col col-12">
            <div class="row form-row">
                <div class="col-auto">
                    <label for="features_on" class="warning">@lang('sArticles::global.features')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.features_on_off_help')"></i>
                </div>
                <div class="col">
                    <input type="checkbox" id="features_oncheck" class="form-checkbox form-control" name="features_oncheck" value="" onchange="documentDirty=true;" onclick="changestate(document.form.features_on);" @if(evo()->getConfig('sart_features_on', 1) == 1) checked @endif>
                    <input type="hidden" id="features_on" name="features_on" value="{{evo()->getConfig('sart_features_on', 1)}}" onchange="documentDirty=true;">
                </div>
            </div>
        </div>
    </div>
    <div class="row form-row">
        <div class="row-col col-12">
            <div class="row form-row">
                <div class="col-auto">
                    <label for="categories_on" class="warning">@lang('sArticles::global.categories')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.categories_on_help')"></i>
                </div>
                <div class="col">
                    <input type="checkbox" class="form-checkbox form-control" onchange="documentDirty=true;" onclick="changestate(document.form.categories_on);" @if(evo()->getConfig('sart_categories_on', 1) == 1) checked @endif>
                    <input type="hidden" name="categories_on" value="{{evo()->getConfig('sart_categories_on', 1)}}" onchange="documentDirty=true;">
                </div>
            </div>
        </div>
    </div>
    <div class="row form-row">
        <div class="row-col col-12">
            <div class="row form-row">
                <div class="col-auto">
                    <label for="tag_texts_on">@lang('sArticles::global.tag_texts')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.tag_texts_on_off_help')"></i>
                </div>
                <div class="col">
                    <input type="checkbox" id="tag_texts_on_check" class="form-checkbox form-control" name="tag_texts_on_check" value="" onchange="documentDirty=true;" onclick="changestate(document.form.tag_texts_on);" @if(evo()->getConfig('sart_tag_texts_on', 1) == 1) checked @endif>
                    <input type="hidden" id="tag_texts_on" name="tag_texts_on" value="{{evo()->getConfig('sart_tag_texts_on', 1)}}" onchange="documentDirty=true;">
                </div>
            </div>
        </div>
    </div>
    <div class="row form-row">
        <div class="row-col col-12">
            <div class="row form-row">
                <div class="col-auto">
                    <label for="in_main_menu" class="warning">@lang('sArticles::global.in_main_menu')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.in_main_menu_help')"></i>
                </div>
                <div class="col">
                    <input type="checkbox" id="in_main_menucheck" class="form-checkbox form-control" name="in_main_menucheck" value="" onchange="documentDirty=true;" onclick="changestate(document.form.in_main_menu);" @if(evo()->getConfig('sart_in_main_menu', 0) == 1) checked @endif>
                    <input type="hidden" id="in_main_menu" name="in_main_menu" value="{{evo()->getConfig('sart_in_main_menu', 0)}}" onchange="documentDirty=true;">
                </div>
            </div>
        </div>
    </div>
    <div class="row form-row">
        <div class="row-col col-lg-3 col-md-3 col-12">
            <div class="row form-row">
                <div class="col-auto">
                    <label for="main_menu_order" class="warning">@lang('sArticles::global.main_menu_order')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.main_menu_order_help')"></i>
                </div>
                <div class="input-group col">
                    <div class="input-group-prepend">
                        <span class="btn btn-secondary" onclick="let elm = document.form.main_menu_order;let v=parseInt(elm.value+'')-1;elm.value=v>0? v:0;elm.focus();documentDirty=true;return false;" style="cursor: pointer;"><i class="fa fa-angle-left"></i></span>
                        <span class="btn btn-secondary" onclick="let elm = document.form.main_menu_order;let v=parseInt(elm.value+'')+1;elm.value=v>0? v:0;elm.focus();documentDirty=true;return false;" style="cursor: pointer;"><i class="fa fa-angle-right"></i></span>
                    </div>
                    <input type="text" id="main_menu_order" name="main_menu_order" class="form-control" value="{{evo()->getConfig('sart_main_menu_order', 11)}}" maxlength="11" onchange="documentDirty=true;">
                </div>
            </div>
        </div>
    </div>
    @if(evo()->getConfig('which_editor', 'TinyMCE5') == 'TinyMCE5')
        @php(evo()->setConfig('tinymce5_theme', evo()->getConfig('sart_tinymce5_theme', 'custom')))
        @php($files = array_diff(scandir(MODX_BASE_PATH.'assets/plugins/tinymce5/configs'), array('.', '..', 'custom.js')))
        @include('tinymce5settings::tinymce5settings', ['themes'=>$files])
    @endif
    <div class="row form-row">
        <div class="row-col col-lg-3 col-md-3 col-12">
            <div class="row form-row">
                <div class="col-title">
                    <label for="general__filter_types_on">@lang('sArticles::global.show_filter_types')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.show_filter_types')"></i>
                </div>
                <div class="col">
                    <input type="checkbox" class="form-checkbox form-control" onchange="documentDirty=true;" onclick="changestate(document.form.general__filter_types_on);" @if(sArticles::config('general.filter_types_on', 1) == 1) checked @endif>
                    <input type="hidden" name="general__filter_types_on" value="{{sArticles::config('general.filter_types_on', 1)}}" onchange="documentDirty=true;">
                </div>
            </div>
        </div>
    </div>
    <div class="split my-3"></div>
    <h3>@lang('sArticles::global.management_fields_on')</h3>
    @php($types = array_keys(sArticles::config('types', [])))
    @if(count($types) == 0)
        @php($types = ['article'])
    @endif
    @foreach($types as $type)
        <div class="row form-row">
            <div class="row-col col-lg-4 col-md-4 col-12">
                <div class="row form-row">
                    <div class="col-title-8">
                        <label for="types__{{$type}}__name">@lang('global.resource_type')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.resource_type_help')"></i>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" name="types__{{$type}}__name" value="{{sArticles::config('types.'.$type.'.name', $type)}}" onchange="documentDirty=true;">
                    </div>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="row-col col-lg-3 col-md-3 col-12 offset-1">
                <div class="row form-row">
                    <div class="col-title-8">
                        <label for="types__{{$type}}__list">@lang('sArticles::global.list_resources')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.list_resources_help')"></i>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" name="types__{{$type}}__list" value="{{sArticles::config('types.'.$type.'.list', __('sArticles::global.articles'))}}" onchange="documentDirty=true;">
                    </div>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="row-col col-lg-3 col-md-3 col-12 offset-1">
                <div class="row form-row">
                    <div class="col-title-8">
                        <label for="types__{{$type}}__add_button_text">@lang('sArticles::global.add_button_text')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.add_button_text_help')"></i>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" name="types__{{$type}}__add_button_text" value="{{sArticles::config('types.'.$type.'.add_button_text', __('sArticles::global.add_article'))}}" onchange="documentDirty=true;">
                    </div>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="row-col col-lg-3 col-md-3 col-12 offset-1">
                <div class="row form-row">
                    <div class="col-title-8">
                        <label for="types__{{$type}}__add_button_text">@lang('sArticles::global.to_button_text')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.add_button_text_help')"></i>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" name="types__{{$type}}__to_button_text" value="{{sArticles::config('types.'.$type.'.to_button_text', __('sArticles::global.add_article'))}}" onchange="documentDirty=true;">
                    </div>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="row-col col-lg-3 col-md-3 col-12 offset-1">
                <div class="row form-row">
                    <div class="col-title-8">
                        <label for="types__{{$type}}__publish_date_on">@lang('global.publish_date')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.show_field') @lang('global.publish_date')"></i>
                    </div>
                    <div class="col">
                        <input type="checkbox" class="form-checkbox form-control" onchange="documentDirty=true;" onclick="changestate(document.form.types__{{$type}}__publish_date_on);" @if(sArticles::config('types.'.$type.'.publish_date_on', 1) == 1) checked @endif>
                        <input type="hidden" name="types__{{$type}}__publish_date_on" value="{{sArticles::config('types.'.$type.'.publish_date_on', 1)}}" onchange="documentDirty=true;">
                    </div>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="row-col col-lg-3 col-md-3 col-12 offset-1">
                <div class="row form-row">
                    <div class="col-title-8">
                        <label for="types__{{$type}}__long_title_on">@lang('global.long_title')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.show_field') @lang('global.long_title')"></i>
                    </div>
                    <div class="col">
                        <input type="checkbox" class="form-checkbox form-control" onchange="documentDirty=true;" onclick="changestate(document.form.types__{{$type}}__long_title_on);" @if(sArticles::config('types.'.$type.'.long_title_on', 1) == 1) checked @endif>
                        <input type="hidden" name="types__{{$type}}__long_title_on" value="{{sArticles::config('types.'.$type.'.long_title_on', 1)}}" onchange="documentDirty=true;">
                    </div>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="row-col col-lg-3 col-md-3 col-12 offset-1">
                <div class="row form-row">
                    <div class="col-title-8">
                        <label for="types__{{$type}}__cover_title_on">@lang('sArticles::global.cover_title')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.show_field') @lang('sArticles::global.cover_title')"></i>
                    </div>
                    <div class="col">
                        <input type="checkbox" class="form-checkbox form-control" onchange="documentDirty=true;" onclick="changestate(document.form.types__{{$type}}__cover_title_on);" @if(sArticles::config('types.'.$type.'.cover_title_on', 1) == 1) checked @endif>
                        <input type="hidden" name="types__{{$type}}__cover_title_on" value="{{sArticles::config('types.'.$type.'.cover_title_on', 1)}}" onchange="documentDirty=true;">
                    </div>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="row-col col-lg-3 col-md-3 col-12 offset-1">
                <div class="row form-row">
                    <div class="col-title-8">
                        <label for="types__{{$type}}__introtext_on">@lang('global.resource_summary')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.show_field') @lang('global.resource_summary')"></i>
                    </div>
                    <div class="col">
                        <input type="checkbox" class="form-checkbox form-control" onchange="documentDirty=true;" onclick="changestate(document.form.types__{{$type}}__introtext_on);" @if(sArticles::config('types.'.$type.'.introtext_on', 1) == 1) checked @endif>
                        <input type="hidden" name="types__{{$type}}__introtext_on" value="{{sArticles::config('types.'.$type.'.introtext_on', 1)}}" onchange="documentDirty=true;">
                    </div>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="row-col col-lg-3 col-md-3 col-12 offset-1">
                <div class="row form-row">
                    <div class="col-title-8">
                        <label for="types__{{$type}}__visual_editor_introtext">@lang('sArticles::global.visual_editor_for') @lang('global.resource_summary')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.visual_editor_for') @lang('global.resource_summary')"></i>
                    </div>
                    <div class="col">
                        <input type="checkbox" class="form-checkbox form-control" onchange="documentDirty=true;" onclick="changestate(document.form.types__{{$type}}__visual_editor_introtext);" @if(sArticles::config('types.'.$type.'.visual_editor_introtext', 0) == 1) checked @endif>
                        <input type="hidden" name="types__{{$type}}__visual_editor_introtext" value="{{sArticles::config('types.'.$type.'.visual_editor_introtext', 0)}}" onchange="documentDirty=true;">
                    </div>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="row-col col-lg-3 col-md-3 col-12 offset-1">
                <div class="row form-row">
                    <div class="col-title-8">
                        <label for="types__{{$type}}__description_on">@lang('global.description')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.show_field') @lang('global.description')"></i>
                    </div>
                    <div class="col">
                        <input type="checkbox" class="form-checkbox form-control" onchange="documentDirty=true;" onclick="changestate(document.form.types__{{$type}}__description_on);" @if(sArticles::config('types.'.$type.'.description_on', 1) == 1) checked @endif>
                        <input type="hidden" name="types__{{$type}}__description_on" value="{{sArticles::config('types.'.$type.'.description_on', 1)}}" onchange="documentDirty=true;">
                    </div>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="row-col col-lg-3 col-md-3 col-12 offset-1">
                <div class="row form-row">
                    <div class="col-title-8">
                        <label for="types__{{$type}}__visual_editor_description">@lang('sArticles::global.visual_editor_for') @lang('global.description')</label>
                        <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.visual_editor_for') @lang('global.description')"></i>
                    </div>
                    <div class="col">
                        <input type="checkbox" class="form-checkbox form-control" onchange="documentDirty=true;" onclick="changestate(document.form.types__{{$type}}__visual_editor_description);" @if(sArticles::config('types.'.$type.'.visual_editor_description', 0) == 1) checked @endif>
                        <input type="hidden" name="types__{{$type}}__visual_editor_description" value="{{sArticles::config('types.'.$type.'.visual_editor_description', 0)}}" onchange="documentDirty=true;">
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <div class="split my-3"></div>
    <h3>@lang('sArticles::global.management_fields_name')</h3>
    <div class="row form-row">
        <div class="col-auto col-title-9">
            <label for="seotitle">@lang('sArticles::global.seotitle')</label>
            <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.seotitle_help')"></i>
        </div>
        <div class="col">
            <div class="input-group">
                <input type="text" id="seotitle" class="form-control" name="seotitle" value="{{evo()->getConfig('sart_name_seotitle', '')}}" onchange="documentDirty=true;">
            </div>
        </div>
    </div>
    <div class="row form-row">
        <div class="col-auto col-title-9">
            <label for="seodescription">@lang('sArticles::global.seodescription')</label>
            <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.seodescription_help')"></i>
        </div>
        <div class="col">
            <div class="input-group">
                <input type="text" id="seodescription" class="form-control" name="seodescription" value="{{evo()->getConfig('sart_name_seodescription', '')}}" onchange="documentDirty=true;">
            </div>
        </div>
    </div>
    <div class="split my-3"></div>
</form>

@push('scripts.bot')
    <style>
        .close-icon{cursor:pointer;position:absolute;top:0;right:0;z-index:2;padding:0.6rem 1rem;}
        .draft-value{display:none;}
    </style>
    <script>
        $(document).on("click", ".close-icon", function () {$(this).closest('.card').remove();documentDirty=true;});
        function addItem() {$(".widgets").append($('.draft-value').html());}
    </script>
    <div id="actions">
        <div class="btn-group">
            <a id="Button2" class="btn btn-primary" href="javascript:void(0);" onclick="addItem();">
                <i class="fa fa-plus"></i><span>@lang('global.add')</span>
            </a>
            <a id="Button1" class="btn btn-success" href="javascript:void(0);" onclick="saveForm('#form');">
                <i class="fas fa-save"></i><span>@lang('global.save')</span>
            </a>
            <a id="Button5" class="btn btn-secondary" href="{!!$url!!}">
                <i class="fa fa-times-circle"></i><span>@lang('global.cancel')</span>
            </a>
        </div>
    </div>
    <div class="draft-value">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <i style="cursor:pointer;font-size:x-large;" class="fas fa-sort"></i>&emsp; @lang('sArticles::global.new_field')
                    <span class="close-icon"><i class="fa fa-times"></i></span>
                </div>
                <div class="card-block">
                    <div class="userstable">
                        <div class="card-body">
                            <div class="row form-row">
                                <div class="col-auto col-title-6">
                                    <label class="warning">@lang('sArticles::global.key')</label>
                                </div>
                                <div class="col">
                                    <input type="text" class="form-control" name="settings[key][]" maxlength="255" value="" onchange="documentDirty=true;" spellcheck="true">
                                </div>
                            </div>
                            <div class="row form-row">
                                <div class="col-auto col-title-6">
                                    <label class="warning">@lang('global.name')</label>
                                </div>
                                <div class="col">
                                    <input type="text" class="form-control" name="settings[name][]" maxlength="255" value="" onchange="documentDirty=true;" spellcheck="true">
                                </div>
                            </div>
                            <div class="row form-row">
                                <div class="col-auto col-title-6">
                                    <label class="warning">@lang('sArticles::global.field_type')</label>
                                </div>
                                <div class="col">
                                    <select id="rating" class="form-control" name="settings[type][]" onchange="documentDirty=true;">
                                        @foreach(['Text', 'Textarea', 'RichText', 'File', 'Image'] as $value)
                                            <option value="{{$value}}">{{$value}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush
