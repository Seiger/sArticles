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
        {{--@foreach($sPost->listTags() as $tag)
            <tr>
                <td>{{$tag['alias']}}</td>
                @foreach($sPost->langTabs() as $lang => $tabName)
                    <td data-id="{{$tag['id']}}" data-lang="{{$lang}}">
                        @if($lang == $sPost->langDefault())
                            <div class="input-group">
                                <input type="text" class="form-control" name="tag[{{$tag['id']}}][{{$lang}}]" value="{{$tag[$lang]}}" />
                                <span class="input-group-btn">
                                    <a style="padding: 3px 5px;" class="btn btn-light" type="button" href="#" data-toggle="modal" data-target="#editTag" title="{{$_lang['spost_auto_translate']}} {{strtoupper($sPost->langDefault())}} => {{strtoupper($lang)}}">
                                        <i class="fa fa-pencil-alt" style="font-size: x-large;"></i>
                                    </a>
                                </span>
                            </div>
                        @else
                            <div class="input-group">
                                <input type="text" class="form-control" name="tag[{{$tag['id']}}][{{$lang}}]" value="{{$tag[$lang]}}" />
                                <span class="input-group-btn">
                                    <button style="padding: 0 5px;" class="btn btn-light js_translate" type="button" title="{{$_lang['spost_auto_translate']}} {{strtoupper($sPost->langDefault())}} => {{strtoupper($lang)}}">
                                        <i class="fa fa-language" style="font-size: xx-large;"></i>
                                    </button>
                                </span>
                                <span class="input-group-btn">
                                    <a style="padding: 3px 5px;" class="btn btn-light" type="button" href="#" data-toggle="modal" data-target="#editTag" title="{{$_lang['spost_auto_translate']}} {{strtoupper($sPost->langDefault())}} => {{strtoupper($lang)}}">
                                        <i class="fa fa-pencil-alt" style="font-size: x-large;"></i>
                                    </a>
                                </span>
                            </div>
                        @endif
                        {!! $sPost->filterContent($tag[$lang.'_content'], 'img'); !!}
                    </td>
                @endforeach
            </tr>
        @endforeach--}}
        </tbody>
    </table>
</div>

<div class="modal fade" id="editTag" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">#</div>
            <div class="modal-body">
                <p>{{$_lang['spost_tag_texts']}} <span class="tagName text-primary">#</span> @if($sArticlesController->langDefault() != 'base') {{$_lang['spost_on_lang']}} <span class="tagLang text-muted text-uppercase"></span> @endif</p>
                <textarea id="{{$sArticlesController->langDefault()}}_tagContent" cols="30" rows="10"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('global.cancel')</button>
                <button data-id="" data-lang="" class="btn btn-success js_save_texts">@lang('global.save')</button>
            </div>
        </div>
    </div>
</div>

@push('scripts.bot')
    <div id="actions">
        <div class="btn-group">
            <a id="Button2" class="btn btn-info" href="#" data-toggle="modal" data-target="#addTag">
                <i class="fa fa-hashtag"></i>
                <span>@lang('sArticles::global.add_tag')</span>
            </a>
        </div>
    </div>
    {{--<script>
        $('#editTag').on('show.bs.modal', function(e) {
            var source = $(e.relatedTarget).parents('td');
            var dataId = source.data('id');
            var dataLang = source.data('lang');
            $.ajax({
                url: '{!!$url!!}&get=tagTexts',
                type: 'POST',
                dataType: 'JSON',
                data: 'tagId=' + dataId + '&lang=' + dataLang,
                success: function (ajax) {
                    $('.js_save_texts').attr('data-id', dataId);
                    $('.js_save_texts').attr('data-lang', dataLang);
                    $('#editTag').find('#{{$sPost->langDefault()}}_tagContent').val(ajax[dataLang+'_content']);
                    tinymce.get('{{$sPost->langDefault()}}_tagContent').setContent(ajax[dataLang+'_content']);
                }
            });
            $(this).find('.modal-header, .tagName').text('#'+source.find('[type="text"]').val());
            $(this).find('.tagLang').text(dataLang);
        });

        $(document).on('click', '.js_save_texts', function () {
            documentDirty = false;
            var dataId = $(this).attr('data-id');
            var dataLang = $(this).attr('data-lang');
            var tagContent = tinymce.get('{{$sPost->langDefault()}}_tagContent').getContent();
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
    </script>--}}
@endpush
