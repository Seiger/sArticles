<div class="table-responsive tagsTable">
    <table class="table table-condensed table-hover sectionTrans">
        <thead>
        <tr>
            <td style="text-align:center !important;"><b>@lang('global.resource_alias')</b></td>
            @foreach($sArticlesController->langList() as $lang)
                <td style="text-align:center !important;"><b>{{strtoupper($lang)}}</b></td>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach($tags as $tag)
            <tr>
                <td data-id="{{$tag['tagid']}}">
                    <a style="padding: 3px 5px;" href="#" data-href="{!! $url !!}&get=tagDelete&i={{$tag['tagid']}}" data-delete="{{$tag['tagid']}}" data-name="{{$tag[$sArticlesController->langDefault()]}}" class="btn btn-outline-danger">
                        <i class="fa fa-trash" style="font-size: x-large;" title="@lang('global.remove')"></i>
                    </a>
                    <a style="padding: 3px 5px;color:#0057b8;" class="btn btn-light" type="button" href="#" data-toggle="modal" data-target="#editTagAlias" title="@lang('sArticles::global.edit_alias')">
                        <i class="fa fa-pencil-alt" style="font-size: x-large;"></i>
                    </a>&emsp;
                    {{$tag['alias']}}
                </td>
                @foreach($sArticlesController->langList() as $lang)
                    <td data-id="{{$tag['tagid']}}" data-lang="{{$lang}}">
                        @if($lang == $sArticlesController->langDefault())
                            <div class="input-group">
                                <input type="text" class="form-control" name="tag[{{$tag['tagid']}}][{{$lang}}]" value="{{$tag[$lang]}}" />
                                @if(evo()->getConfig('sart_tag_texts_on', 1) == 1)
                                    <span class="input-group-btn">
                                        <a style="padding: 3px 5px;color:#0057b8;" class="btn btn-light" type="button" href="#" data-toggle="modal" data-target="#editTag" title="@lang('sArticles::global.auto_translate') {{strtoupper($sArticlesController->langDefault())}} => {{strtoupper($lang)}}">
                                            <i class="fa fa-pencil-alt" style="font-size: x-large;"></i>
                                        </a>
                                    </span>
                                @endif
                            </div>
                        @else
                            <div class="input-group">
                                <input type="text" class="form-control" name="tag[{{$tag['tagid']}}][{{$lang}}]" value="{{$tag[$lang]}}" />
                                <span class="input-group-btn">
                                    <button style="padding:0 5px;color:#0057b8;" class="btn btn-light js_translate" type="button" title="@lang('sArticles::global.auto_translate') {{strtoupper($sArticlesController->langDefault())}} => {{strtoupper($lang)}}">
                                        <i class="fa fa-language" style="font-size: xx-large;"></i>
                                    </button>
                                </span>
                                @if(evo()->getConfig('sart_tag_texts_on', 1) == 1)
                                    <span class="input-group-btn">
                                        <a style="padding: 3px 5px;color:#0057b8;" class="btn btn-light" type="button" href="#" data-toggle="modal" data-target="#editTag" title="@lang('sArticles::global.auto_translate') {{strtoupper($sArticlesController->langDefault())}} => {{strtoupper($lang)}}">
                                            <i class="fa fa-pencil-alt" style="font-size: x-large;"></i>
                                        </a>
                                    </span>
                                @endif
                            </div>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('scripts.bot')
    <div id="actions">
        <div class="btn-group">
            <div class="btn-group">
                <a id="Button2" class="btn btn-primary" href="#" data-toggle="modal" data-target="#addTag">
                    <i class="fa fa-plus"></i>
                    <span>@lang('sArticles::global.add_tag')</span>
                </a>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addTag" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">@lang('sArticles::global.add_tag')</div>
                <div class="modal-body">
                    <p>@lang('sArticles::global.add_new_tag') @if($lang_default != 'base') @lang('sArticles::global.on_lang') <span class="badge bg-seigerit">{{strtoupper($lang_default)}}</span> @endif</p>
                    <input type="text" name="add_tag" value="" class="form-control"/>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('global.cancel')</button>
                    <span class="btn btn-success js_add_tag">@lang('global.add')</span>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editTagAlias" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">#</div>
                <div class="modal-body">
                    <p>@lang('sArticles::global.tag_alias') <span class="tagName text-primary">#</span></p>
                    <div class="input-group">
                        <span class="input-group-btn">
                            <i class="fa fa-question-circle" data-tooltip="@lang('sArticles::global.tag_alias_help')" style="font-size: x-large;margin: 3px 3px 0 0;"></i>
                        </span>
                        <input id="tagAlias" type="text" value="" class="form-control"/>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('global.cancel')</button>
                    <button data-id="" data-lang="" class="btn btn-success js_save_alias">@lang('global.save')</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editTag" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">#</div>
                <div class="modal-body">
                    <p>@lang('sArticles::global.tag_texts') <span class="tagName text-primary">#</span> @if($sArticlesController->langDefault() != 'base') @lang('sArticles::global.on_lang') <span class="tagLang text-uppercase badge bg-seigerit"></span> @endif</p>
                    <textarea id="tagContent" cols="30" rows="10"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('global.cancel')</button>
                    <button data-id="" data-lang="" class="btn btn-success js_save_texts">@lang('global.save')</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $('.js_add_tag').on('click', function () {
            var _value = $(document).find('[name="add_tag"]').val();
            jQuery.ajax({
                url: '{!!$url!!}&get=addTag',
                type: 'POST',
                dataType: 'JSON',
                data: 'value=' + _value,
                success: function (ajax) {
                    if (ajax.status == 1) {
                        window.location.reload();
                    }
                }
            });
            $('#addTag').modal('hide');
        });
        $('#editTagAlias').on('show.bs.modal', function(e) {
            var source = $(e.relatedTarget).parents('td');
            var dataId = source.data('id');
            var dataLang = '{{$sArticlesController->langDefault()}}';
            $.ajax({
                url: '{!!$url!!}&get=tagGetTexts',
                type: 'POST',
                dataType: 'JSON',
                data: 'tagId=' + dataId,
                success: function (ajax) {
                    $('.js_save_alias').attr('data-id', dataId);
                    $('.js_save_alias').attr('data-lang', dataLang);
                    $('#editTagAlias').find('#tagAlias').val(ajax['alias']);
                    $('#editTagAlias').find('.modal-header, .tagName').text('#'+ajax[dataLang]);
                }
            });
        });
        $('#editTag').on('show.bs.modal', function(e) {
            tinymce.get('tagContent').setContent('');
            var source = $(e.relatedTarget).parents('td');
            var dataId = source.data('id');
            var dataLang = source.data('lang');
            $.ajax({
                url: '{!!$url!!}&get=tagGetTexts',
                type: 'POST',
                dataType: 'JSON',
                data: 'tagId=' + dataId + '&lang=' + dataLang,
                success: function (ajax) {
                    $('.js_save_texts').attr('data-id', dataId);
                    $('.js_save_texts').attr('data-lang', dataLang);
                    $('#editTag').find('#tagContent').val(ajax[dataLang+'_content']);
                    tinymce.get('tagContent').setContent(ajax[dataLang+'_content']);
                }
            });
            $(this).find('.modal-header, .tagName').text('#'+source.find('[type="text"]').val());
            $(this).find('.tagLang').text(dataLang);
        });
        $(document).on('click', '.js_save_alias', function () {
            documentDirty = false;
            var dataId = $(this).attr('data-id');
            var dataLang = $(this).attr('data-lang');
            var tagAlias = $('#tagAlias').val();
            $.ajax({
                url: '{!!$url!!}&get=tagSetAlias',
                type: 'POST',
                dataType: 'JSON',
                data: 'tagId=' + dataId + '&alias=' + tagAlias,
                success: function (ajax) {
                    if (ajax.status == 1) {
                        window.location.reload();
                    } else {
                        $('#editTag').modal('hide');
                    }
                }
            });
        });
        $(document).on('click', '.js_save_texts', function () {
            documentDirty = false;
            var dataId = $(this).attr('data-id');
            var dataLang = $(this).attr('data-lang');
            var tagContent = tinymce.get('tagContent').getContent();
            $.ajax({
                url: '{!!$url!!}&get=tagSetTexts',
                type: 'POST',
                dataType: 'JSON',
                data: 'tagId=' + dataId + '&lang=' + dataLang + '&texts[content]=' + encodeURIComponent(tagContent),
                success: function (ajax) {
                    $('#editTag').modal('hide');
                }
            });
        });
        $(document).on("click", ".js_translate", function () {
            var _this = $(this).parents('td');
            var source = _this.data('id');
            var target = _this.data('lang');
            $.ajax({
                url: '{!!$url!!}&get=tagTranslate',
                type: 'POST',
                data: 'source=' + source + '&target=' + target,
                success: function (ajax) {
                    _this.find('input').val(ajax);
                }
            });
        });
        $(".sectionTrans").on("blur", "input", function () {
            var _this = $(this).parents('td');
            var source = _this.data('id');
            var target = _this.data('lang');
            var _value = _this.find('input').val();
            $.ajax({
                url: '{!!$url!!}&get=tagTranslateUpdate',
                type: 'POST',
                data: 'source=' + source + '&target=' + target + '&value=' + _value,
            });
        });
    </script>
@endpush
