<div class="table-responsive tagsTable">
    <table class="table table-condensed table-hover sectionTrans">
        <thead>
        <tr>
            <td style="width:130px;text-align:center !important;"><b>@lang('global.settings_photo')</b></td>
            <td style="text-align:center !important;"><b>@lang('global.resource_alias')</b></td>
            @foreach($sArticlesController->langList() as $lang)
                <td style="text-align:center !important;"><b>{{strtoupper($lang)}}</b></td>
            @endforeach
            <td style="width:40px;"></td>
        </tr>
        </thead>
        <tbody>
        @foreach($authors as $author)
            <tr>
                <td>
                    <input type="hidden" id="cover{{$author->autid}}" name="cover{{$author->autid}}" value="{{$author->image ?? ''}}" onchange="authorImageChange({{$author->autid}})">
                    <div id="image_for_cover{{$author->autid}}" class="image_for_field" data-image="{{trim($author->image ?? '') ?: 'empty'}}" onclick="BrowseServer('cover{{$author->autid}}')" style="background-image: url('{{MODX_SITE_URL}}{{trim($author->image ?? '') ?: 'empty'}}');width:120px;"></div>
                    <script>document.getElementById('cover{{$author->autid}}').addEventListener('change', evoRenderImageCheck, false);</script>
                </td>
                <td data-id="{{$author->autid}}">
                    <div class="input-group mb-3">
                        <select name="gender" class="change_gender">
                            <option value="man" @if($author->gender == 'man') selected @endif>Чоловік</option>
                            <option value="woman" @if($author->gender == 'woman') selected @endif>Жінка</option>
                        </select>
                    </div>
                    {{$author->alias}}&emsp;
                    <a style="padding: 3px 5px;color:#0057b8;" class="btn btn-light" type="button" href="#" data-toggle="modal" data-target="#editAuthorAlias" title="@lang('sArticles::global.edit_alias')">
                        <i class="fa fa-pencil-alt" style="font-size: x-large;"></i>
                    </a>
                </td>
                @foreach($sArticlesController->langList() as $lang)
                    <td data-id="{{$author->autid}}" data-lang="{{$lang}}">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">@lang('global.user_first_name')</span>
                            </div>
                            <input type="text" class="form-control" name="{{$lang}}_name" value="{{$author->{$lang.'_name'} }}" />
                            <div class="input-group-prepend">
                                <span class="input-group-text">@lang('global.user_last_name')</span>
                            </div>
                            <input type="text" class="form-control" name="{{$lang}}_lastname" value="{{$author->{$lang.'_lastname'} }}" />
                        </div>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">@lang('sArticles::global.office')</span>
                            </div>
                            <input type="text" class="form-control" name="{{$lang}}_office" value="{{$author->{$lang.'_office'} }}" />
                        </div>
                    </td>
                @endforeach
                <td>
                    <a style="padding: 3px 5px;" href="#" data-href="{!! $url !!}&get=authorDelete&i={{$author->autid}}" data-delete="{{$author->autid}}" data-name="{{$author->{$sArticlesController->langDefault().'_name'} }}" class="btn btn-outline-danger">
                        <i class="fa fa-trash" style="font-size: x-large;" title="@lang('global.remove')"></i>
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('scripts.bot')
    <div id="actions">
        <div class="btn-group">
            <div class="btn-group">
                <a id="Button2" class="btn btn-primary" href="#" data-toggle="modal" data-target="#addAuthor">
                    <i class="fa fa-plus"></i>
                    <span>@lang('sArticles::global.add_author')</span>
                </a>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addAuthor" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">@lang('sArticles::global.add_author')</div>
                <div class="modal-body">
                    <p>@lang('sArticles::global.add_author') @if($lang_default != 'base') @lang('sArticles::global.on_lang') <span class="badge bg-seigerit">{{strtoupper($lang_default)}}</span> @endif</p>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">@lang('global.user_first_name')</span>
                        </div>
                        <input type="text" class="form-control" name="add_author_name" value="" />
                        <div class="input-group-prepend">
                            <span class="input-group-text">@lang('global.user_last_name')</span>
                        </div>
                        <input type="text" class="form-control" name="add_author_lastname" value="" />
                    </div>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">@lang('sArticles::global.office')</span>
                        </div>
                        <input type="text" class="form-control" name="add_author_office" value="" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('global.cancel')</button>
                    <span class="btn btn-success js_add_author">@lang('global.add')</span>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editAuthorAlias" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header"></div>
                <div class="modal-body">
                    <p>@lang('sArticles::global.edit_alias') <span class="authorName text-primary"></span></p>
                    <div class="input-group">
                        <input id="authorAlias" type="text" value="" class="form-control"/>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('global.cancel')</button>
                    <button data-id="" data-lang="" class="btn btn-success js_save_alias">@lang('global.save')</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        function authorImageChange(author) {
            $.ajax({
                url: '{!!$url!!}&get=authorImageChange',
                type: 'POST',
                dataType: 'JSON',
                data: 'author='+author+'&image='+event.target.value
            });
        }
        $('.js_add_author').on('click', function () {
            var _name = $(document).find('[name="add_author_name"]').val();
            var _lastname = $(document).find('[name="add_author_lastname"]').val();
            var _office = $(document).find('[name="add_author_office"]').val();
            jQuery.ajax({
                url: '{!!$url!!}&get=addAuthor',
                type: 'POST',
                dataType: 'JSON',
                data: 'name='+_name+'lastname='+_lastname+'&office='+_office,
                success: function (ajax) {
                    if (ajax.status == 1) {
                        window.location.reload();
                    }
                }
            });
            $('#addAuthor').modal('hide');
        });
        $('#editAuthorAlias').on('show.bs.modal', function(e) {
            var source = $(e.relatedTarget).parents('td');
            var dataId = source.data('id');
            var dataLang = '{{$sArticlesController->langDefault()}}';
            $.ajax({
                url: '{!!$url!!}&get=authorGetTexts',
                type: 'POST',
                dataType: 'JSON',
                data: 'dataId=' + dataId,
                success: function (ajax) {
                    $('.js_save_alias').attr('data-id', dataId);
                    $('.js_save_alias').attr('data-lang', dataLang);
                    $('#editAuthorAlias').find('#authorAlias').val(ajax['alias']);
                    $('#editAuthorAlias').find('.modal-header, .authorName').text(ajax[dataLang+'_name']);
                }
            });
        });
        $(document).on('click', '.js_save_alias', function () {
            documentDirty = false;
            var dataId = $(this).attr('data-id');
            var dataLang = $(this).attr('data-lang');
            var authorAlias = $('#authorAlias').val();
            $.ajax({
                url: '{!!$url!!}&get=authorSetAlias',
                type: 'POST',
                dataType: 'JSON',
                data: 'dataId=' + dataId + '&alias=' + authorAlias,
                success: function (ajax) {
                    if (ajax.status == 1) {
                        window.location.reload();
                    } else {
                        $('#editAuthorAlias').modal('hide');
                    }
                }
            });
        });
        $(".sectionTrans").on("blur", "input", function () {
            var dataId = $(this).parents('td').data('id');
            var target = $(this).attr('name');
            var _value = $(this).val();
            $.ajax({
                url: '{!!$url!!}&get=authorTextUpdate',
                type: 'POST',
                data: 'dataId=' + dataId + '&target=' + target + '&value=' + _value,
            });
        });
        $(document).on('change', '.change_gender', function () {
            let dataId = $(this).parents('td').data('id');
            let dataGender = $(this).val();
            $.ajax({
                url: '{!!$url!!}&get=authorSetGender',
                type: 'POST',
                dataType: 'JSON',
                data: 'dataId=' + dataId + '&gender=' + dataGender
            });
        });
    </script>
@endpush
