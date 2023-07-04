<div class="table-responsive">
    <table class="table table-condensed table-hover sectionTrans">
        <thead>
        <tr>
            <th style="text-align:center;">@lang('global.name')</th>
            <th style="width:55px;text-align:center;">@lang('sArticles::global.votes')</th>
            <th id="action-btns">@lang('global.onlineusers_action')</th>
        </tr>
        </thead>
        <tbody>
        @php($polls = \Seiger\sArticles\Models\sArticlesPoll::all())
        @foreach($polls as $poll)
            <tr>
                <td>
                    <b>{{trim($poll->question[$sArticlesController->langDefault()] ?? '') ?: __('sArticles::global.no_text')}}</b>
                </td>
                <td>@php($votes = data_is_json($poll->votes ?? '', true)){{$votes['total'] ?? 0}}</td>
                <td style="text-align:center;">
                    <div class="btn-group">
                        <a href="{{$url}}&get=poll&i={{$poll->pollid}}" class="btn btn-outline-success">
                            <i class="fa fa-pencil"></i> <span>@lang('global.edit')</span>
                        </a>
                        <a href="#" data-href="{{$url}}&get=pollDelete&i={{$poll->pollid}}" data-delete="{{$poll->pollid}}" data-name="{{$poll->question[$sArticlesController->langDefault()] ?? ''}}" class="btn btn-outline-danger">
                            <i class="fa fa-trash"></i> <span>@lang('global.remove')</span>
                        </a>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@push('scripts.bot')
    <div id="actions">
        <div class="btn-group">
            <a id="Button2" href="{!!$url!!}&get=poll&i=0" class="btn btn-primary" title="@lang('sArticles::global.add_poll')">
                <i class="fa fa-plus"></i> <span>@lang('global.add')</span>
            </a>
        </div>
    </div>
@endpush
