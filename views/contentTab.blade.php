<form id="form" name="form" method="post" enctype="multipart/form-data" action="{!!$url!!}&get=contentSave" onsubmit="documentDirty=false;">
    <input type="hidden" name="back" value="&get=content&lang={{request()->lang ?? 'base'}}&i={{request()->i ?? 0}}" />
    <input type="hidden" name="offer" value="{{request()->i ?? 0}}" />
    <input type="hidden" name="lang" value="{{request()->lang ?? 'base'}}" />
    <div class="row form-row">
        <div class="row-col col-lg-12 col-12">
            <div class="row form-row">
                <div class="col-auto col-title">
                    <label for="pagetitle" class="warning">@lang('global.resource_title')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('global.resource_title_help')"></i>
                </div>
                <div class="col">
                    <input type="text" id="pagetitle" class="form-control" name="pagetitle" maxlength="255" value="{{$content->pagetitle ?? ''}}" onchange="documentDirty=true;" spellcheck="true">
                </div>
            </div>
            <div class="row form-row">
                <div class="col-auto col-title">
                    <label for="longtitle" class="warning">@lang('global.long_title')</label>
                </div>
                <div class="col">
                    <input type="text" id="longtitle" class="form-control" name="longtitle" maxlength="255" value="{{$content->longtitle ?? ''}}" onchange="documentDirty=true;" spellcheck="true">
                </div>
            </div>
            <div class="row form-row">
                <div class="col-auto col-title">
                    <label for="introtext" class="warning">@lang('global.resource_summary')</label>
                </div>
                <div class="col">
                    <textarea id="introtext" class="form-control" name="introtext" rows="5" wrap="soft" onchange="documentDirty=true;">{{$content->introtext ?? ''}}</textarea>
                </div>
            </div>
            <div class="row form-row form-row-richtext">
                <div class="col-auto col-title">
                    <label for="content" class="warning">@lang('global.resource_content')</label>
                </div>
                <div class="col">
                    <textarea id="content" class="form-control" name="content" cols="40" rows="15" onchange="documentDirty=true;">{{$content->content ?? ''}}</textarea>
                </div>
            </div>
            <div class="row form-row">
                <div class="col-auto col-title-9">
                    <label for="seotitle" class="warning">@lang('sOffers::global.seotitle')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sOffers::global.seotitle_help')"></i>
                </div>
                <div class="col">
                    <div class="input-group">
                        <input type="text" id="seotitle" class="form-control" name="seotitle" value="{{$content->seotitle ?? ''}}" onchange="documentDirty=true;">
                    </div>
                </div>
            </div>
            <div class="row form-row">
                <div class="col-auto col-title">
                    <label for="seodescription" class="warning">@lang('sOffers::global.seodescription')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sOffers::global.seodescription_help')"></i>
                </div>
                <div class="col">
                    <div class="input-group">
                        <textarea id="seodescription" class="form-control" name="seodescription" rows="3" wrap="soft" onchange="documentDirty=true;">{{$content->seodescription ?? ''}}</textarea>
                    </div>
                </div>
            </div>
            <div class="row form-row">
                <div class="col-auto col-title">
                    <label for="seorobots" class="warning">@lang('sOffers::global.seorobots')</label>
                    <i class="fa fa-question-circle" data-tooltip="@lang('sOffers::global.seorobots_help')"></i>
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
            @foreach($constructor as $item)
                <div class="row form-row">
                    <div class="col-auto col-title">
                        <label for="{{$item['key']}}" class="warning">{{$item['name']}}</label>
                    </div>
                    <div class="col">
                        <div class="input-group">
                            @if($item['type'] == 'Text')
                                <input type="text" id="{{$item['key']}}" class="form-control" name="constructor[{{$item['key']}}]" value="{{$item['value']}}" onchange="documentDirty=true;">
                            @else
                                <textarea id="{{$item['key']}}" class="form-control" name="constructor[{{$item['key']}}]" rows="3" wrap="soft" onchange="documentDirty=true;">{{$item['value']}}</textarea>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="split my-2"></div>
        </div>
    </div>
</form>

@push('scripts.bot')
    <div id="actions">
        <div class="btn-group">
            <a id="Button1" class="btn btn-success" href="javascript:void(0);" onclick="saveForm('#form');">
                <i class="fa fa-floppy-o"></i>
                <span>@lang('global.save')</span>
            </a>
            <a id="Button5" class="btn btn-secondary" href="{!!$url!!}">
                <i class="fa fa-times-circle"></i><span>@lang('global.cancel')</span>
            </a>
            <a id="Button3" class="btn btn-danger" data-href="{{$url}}&get=offerDelete&i={{request()->i ?? 0}}" data-toggle="modal" data-target="#confirmDelete" data-id="{{request()->i ?? 0}}" data-name="{{$content->pagetitle ?? ''}}">
                <i class="fa fa-trash"></i> <span>@lang('global.remove')</span>
            </a>
        </div>
    </div>
    <div class="modal fade" id="confirmDelete" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">@lang('sOffers::global.confirm_delete')</div>
                <div class="modal-body">
                    @lang('sOffers::global.you_sure') <b id="confirm-name"></b> @lang('sOffers::global.with_id') <b id="confirm-id"></b>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('global.cancel')</button>
                    <a class="btn btn-danger btn-ok">@lang('global.remove')</a>
                </div>
            </div>
        </div>
    </div>
@endpush