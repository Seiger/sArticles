<form id="form" name="form" method="post" enctype="multipart/form-data" action="{!!$url!!}&get=featuresSave" onsubmit="documentDirty=false;">
    <input type="hidden" name="back" value="&get=features" />

    <div class="row form-row widgets sortable">
        @foreach($features as $feature)
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <i style="cursor:pointer;font-size:x-large;" class="fas fa-sort"></i>&emsp; {{$feature->base ?? ''}}
                        <span class="close-icon"><i class="fa fa-times"></i></span>
                    </div>
                    <div class="card-block">
                        <div class="userstable">
                            <div class="card-body">
                                <div class="row form-row">
                                    <div class="col-auto col-title-6">
                                        <label class="warning">@lang('sOffers::global.badge')</label>
                                    </div>
                                    <div class="col">
                                        <input type="text" class="form-control" name="features[badge][]" maxlength="255" value="{{$feature->badge ?? ''}}" onchange="documentDirty=true;" spellcheck="true">
                                    </div>
                                </div>
                                <div class="row form-row">
                                    <div class="col-auto col-title-6">
                                        <label class="warning">@lang('sOffers::global.color')</label>
                                    </div>
                                    <div class="col">
                                        <input type="text" class="form-control" name="features[color][]" maxlength="255" value="{{$feature->color ?? ''}}" onchange="documentDirty=true;" spellcheck="true">
                                    </div>
                                </div>
                                @foreach($sOfferController->langList() as $idx => $lang)
                                    <div class="row form-row">
                                        <div class="col-auto col-title-6">
                                            <label class="warning">@lang('sOffers::global.content') @if($lang != 'base')<span class="badge bg-seigerit">{{$lang}}</span>@endif</label>
                                        </div>
                                        <div class="col">
                                            <input type="text" class="form-control" name="features[{{$lang}}][]" maxlength="255" value="{{$feature->{$lang} ?? ''}}" onchange="documentDirty=true;" spellcheck="true">
                                        </div>
                                    </div>
                                @endforeach
                                <div class="row form-row">
                                    <div class="col-auto col-title-6">
                                        <label class="warning">@lang('global.resource_alias')</label>
                                    </div>
                                    <div class="col">
                                        <input type="text" class="form-control" name="features[alias][]" maxlength="255" value="{{$feature->alias ?? ''}}" onchange="documentDirty=true;" spellcheck="true">
                                    </div>
                                </div>
                                <input type="hidden" name="features[fid][]" value="{{$feature->fid}}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
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
                    <i style="cursor:pointer;font-size:x-large;" class="fas fa-sort"></i>&emsp; @lang('sOffers::global.feature_item')
                    <span class="close-icon"><i class="fa fa-times"></i></span>
                </div>
                <div class="card-block">
                    <div class="userstable">
                        <div class="card-body">
                            <div class="row form-row">
                                <div class="col-auto col-title-6">
                                    <label class="warning">@lang('sOffers::global.badge')</label>
                                </div>
                                <div class="col">
                                    <input type="text" class="form-control" name="features[badge][]" maxlength="255" value="" onchange="documentDirty=true;" spellcheck="true">
                                </div>
                            </div>
                            <div class="row form-row">
                                <div class="col-auto col-title-6">
                                    <label class="warning">@lang('sOffers::global.color')</label>
                                </div>
                                <div class="col">
                                    <input type="text" class="form-control" name="features[color][]" maxlength="255" value="" onchange="documentDirty=true;" spellcheck="true">
                                </div>
                            </div>
                            @foreach($sOfferController->langList() as $idx => $lang)
                                <div class="row form-row">
                                    <div class="col-auto col-title-6">
                                        <label class="warning">@lang('sOffers::global.content') @if($lang != 'base')<span class="badge bg-seigerit">{{$lang}}</span>@endif</label>
                                    </div>
                                    <div class="col">
                                        <input type="text" class="form-control" name="features[{{$lang}}][]" maxlength="255" value="" onchange="documentDirty=true;" spellcheck="true">
                                    </div>
                                </div>
                            @endforeach
                            <div class="row form-row">
                                <div class="col-auto col-title-6">
                                    <label class="warning">@lang('global.resource_alias')</label>
                                </div>
                                <div class="col">
                                    <input type="text" class="form-control" name="features[alias][]" maxlength="255" value="" onchange="documentDirty=true;" spellcheck="true">
                                </div>
                            </div>
                            <input type="hidden" name="features[fid][]" value="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush