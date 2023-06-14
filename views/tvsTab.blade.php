<h3>{{(request()->i ?? 0) == 0 ? __('sOffers::global.add_help') : ($offer->pagetitle ?? __('sOffers::global.no_text'))}}</h3>
<div class="split my-3"></div>

<form id="form" name="form" method="post" enctype="multipart/form-data" action="{!!$url!!}&get=tvsSave" onsubmit="documentDirty=false;">
    <input type="hidden" name="back" value="&get=tvs&i={{request()->i ?? 0}}" />
    <input type="hidden" name="offer" value="{{request()->i ?? 0}}" />
    @foreach($tvs as $tv)
        @php($tv->value = $tvValues[$tv->name] ?? '')
        <div class="row form-row">
            <div class="row-col col-lg-12 col-12">
                <div class="row form-row">
                    <div class="col-auto col-title-auto">
                        <label for="{{$tv->name}}" class="warning">
                            {{$tv->caption}}
                            @if(evo()->hasPermission('edit_template'))<br/><small class="text-muted">[*{{$tv->name}}*]</small>@endif
                        </label>
                        @if(!empty($tv->description))<i class="fa fa-question-circle" data-tooltip="{{$tv->description}}"></i>@endif
                    </div>
                    <div class="col">
                        {!! renderFormElement(
                            $tv->type,
                            $tv->id,
                            $tv->default_text,
                            $tv->elements,
                            '$tvPBV',
                            '',
                            $tv->toArray(),
                            $tvs->toArray(),
                            $offer->toArray(),
                        ) !!}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</form>
<div class="split my-3"></div>

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
            <a id="Button3" class="btn btn-danger" data-href="{{$url}}&get=offerDelete&i={{$offer->id}}" data-delete="{{$offer->id}}" data-name="{{$offer->pagetitle}}">
                <i class="fa fa-trash"></i> <span>@lang('global.remove')</span>
            </a>
        </div>
    </div>
@endpush