<form id="form" name="form" method="post" enctype="multipart/form-data" action="{!!$url!!}&get=pollSave" onsubmit="documentDirty=false;">
    <input type="hidden" name="back" value="&get=poll&i={{request()->i ?? 0}}" />
    <input type="hidden" name="poll" value="{{request()->i ?? 0}}" />
    <div class="row form-row">
        <div class="col">
            <h3>@lang('sArticles::global.question')</h3>
        </div>
    </div>
    <div class="row form-row">
        <div class="col">
            @foreach($sArticlesController->langList() as $lang)
                <div class="input-group mb-3">
                    @if($lang != 'base')
                        <div class="input-group-prepend">
                            <span class="badge bg-seigerit">{{$lang}}</span>
                        </div>
                    @endif
                    <input name="question[{{$lang}}]" value="{{$question[$lang] ?? ''}}" type="text" class="form-control" maxlength="255" onchange="documentDirty=true;" spellcheck="true">
                </div>
            @endforeach
        </div>
    </div>
    <div class="split my-3"></div>
    <div class="row form-row">
        <div class="col">
            <h3>@lang('sArticles::global.answers') @if(($votes['total'] ?? 0) > 0)<small class="text-monospace">@lang('sArticles::global.total_votes'): {{$votes['total'] ?? 0}}</small>@endif</h3>
        </div>
    </div>
    @foreach($answers as $Key => $answer)
        <div class="row form-row answer">
            <div class="col">
                <p class="text-monospace text-center">@lang('sArticles::global.answer') @if(($votes[$Key] ?? 0) > 0)<small>@lang('sArticles::global.number_votes'): {{$votes[$Key] ?? 0}}</small>@endif <i title="@lang('global.remove')" onclick="onDeleteField($(this))" class="fa fa-minus-circle text-danger b-btn-del"></i></p>
                @foreach($sArticlesController->langList() as $lang)
                    <div class="input-group mb-3">
                        @if($lang != 'base')
                            <div class="input-group-prepend">
                                <span class="badge bg-seigerit">{{$lang}}</span>
                            </div>
                        @endif
                        <input name="answers[{{$lang}}][{{$Key}}]" value="{{$answer[$lang] ?? ''}}" type="text" class="form-control" maxlength="255" onchange="documentDirty=true;" spellcheck="true">
                    </div>
                @endforeach
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
            <span id="Button2" class="btn btn-primary" title="@lang('sArticles::global.add_answer')" onclick="addItem()">
                <i class="fa fa-plus"></i> <span>@lang('global.add')</span>
            </span>
            <a id="Button5" class="btn btn-secondary" href="{!!$url!!}&get=polls">
                <i class="fa fa-times-circle"></i><span>@lang('global.cancel')</span>
            </a>
        </div>
    </div>
    <div class="draft-element">
        <div class="row form-row answer">
            <div class="col">
                <p class="text-monospace text-center">@lang('sArticles::global.answer') <i title="@lang('global.remove')" onclick="onDeleteField($(this))" class="fa fa-minus-circle text-danger b-btn-del"></i></p>
                @foreach($sArticlesController->langList() as $lang)
                    <div class="input-group mb-3">
                        @if($lang != 'base')
                            <div class="input-group-prepend">
                                <span class="badge bg-seigerit">{{$lang}}</span>
                            </div>
                        @endif
                        <input name="answers[{{$lang}}][]" value="" type="text" class="form-control" maxlength="255" onchange="documentDirty=true;" spellcheck="true">
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <style>
        .close-icon{cursor:pointer;position:absolute;top:0;right:0;z-index:2;padding:0.6rem 1rem;}
        .draft-element{display:none;}
    </style>
    <script>
        function addItem() {$("#form").append($('.draft-element').html());}
        function onDeleteField(target){let parent=target.closest('.answer');alertify.confirm("@lang('sSettings::global.are_you_sure')","@lang('sSettings::global.deleted_irretrievably')",function(){alertify.error("@lang('sSettings::global.deleted')");parent.remove()},function(){alertify.success("@lang('sSettings::global.canceled')")}).set('labels',{ok:"@lang('global.delete')",cancel:"@lang('global.cancel')"}).set({transition:'zoom'});documentDirty=true}
    </script>
@endpush
