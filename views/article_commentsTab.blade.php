<div class="split my-1"></div>
<div class="table-responsive">
    <table class="table table-condensed table-hover sectionTrans">
        <thead>
        <tr>
            <th style="text-align:center;">@lang('global.comment')</th>
            <th style="width: 150px;text-align:center;">@lang('sArticles::global.author')</th>
            <th style="width:135px;text-align:center;">@lang('global.createdon')</th>
            <th id="action-btns">@lang('global.onlineusers_action')</th>
        </tr>
        </thead>
        <tbody>
        @foreach($comments as $comment)
            <tr id="comment{{ $comment->comid }}">
                <td>
                    <span id="comment{{ $comment->comid }}tinytext"><b>{!! $comment->comment!!}</b></span>
                </td>
                <td style="text-align:center;">
                    <span><b>{{ $usersComments[$comment->user_id]->fullname }}</b></span>
                </td>
                <td style="text-align:center;">
                    <span><b>{{ $comment->created_at }}</b></span>
                </td>
                <td style="text-align:center;">
                    <div class="btn-group">
                        @if($comment->approved)
                            <button  class="btn btn-info js__approve_comment" data-value="0">
                                <i class="fa fa-plus"></i> <span>@lang('sArticles::global.comment_hidden')</span>
                            </button>
                        @else
                            <button  class="btn btn-primary js__approve_comment" data-value="1">
                                <i class="fa fa-plus"></i> <span>@lang('sArticles::global.approved')</span>
                            </button>
                        @endif
                        <button  class="btn btn-outline-success js__comment_edit" data-toggle="modal" data-target="#editComment" data-item="{{ $comment->toJson() }}">
                            <i class="fa fa-pencil"></i> <span>@lang('global.edit')</span>
                        </button>
                        <a href="#"
                           class="btn btn-outline-danger"
                           data-href="{{$url}}&get=commentDelete&i={{ $comment->comid }}&article={{ $comment->article_id }}{{ (request()->get('page')) ? '&page='.request()->get('page') : '' }}"
                           data-delete="{{$comment->comid}}"
                           data-name="{!! Str::limit($comment->comment, 50, "...") !!}">
                            <i class="fa fa-trash"></i> <span>@lang('global.remove')</span>
                        </a>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="split my-1"></div>
<div class="paginator">{{$comments->render()}}</div>
<div class="modal fade" id="editComment" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">@lang('sArticles::global.edit_comment')</div>
            <div class="modal-body">
                <input type="hidden" id="comid" class="js_comid" value="0">
                <textarea class="js_comment" cols="80" rows="4"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('global.cancel')</button>
                <button class="btn btn-info js__approve_modal" data-value="0">@lang('sArticles::global.comment_hidden')</button>
                <button class="btn btn-primary js__approve_modal" data-value="1">@lang('sArticles::global.approved')</button>
            </div>
        </div>
    </div>
</div>
@push('scripts.bot')
    <div id="actions">
        <div class="btn-group">
            <a id="Button5" class="btn btn-secondary" href="{!!$url!!}">
                <i class="fa fa-times-circle"></i><span>@lang('sArticles::global.to_list_articles')</span>
            </a>
            <a id="Button3" class="btn btn-danger" data-href="{{$url}}&get=articleDelete&i={{$article->id}}" data-delete="{{$article->id}}" data-name="{{$article->pagetitle}}">
                <i class="fa fa-trash"></i> <span>@lang('global.remove')</span>
            </a>
        </div>
    </div>
    @include('sArticles::partials.commentsjs')
@endpush
