<div class="sarticles-listbar sarticles-comments-toolbar">
    <label class="sarticles-search" title="@lang('global.search')">
        {!! svg('tabler-search', 'sarticles-icon sarticles-search__icon')->toHtml() !!}
        <input type="search" name="search" value="{{request()->search ?? ''}}" autocomplete="off" />
    </label>
</div>
<div class="split my-1"></div>
<div class="table-responsive sarticles-comments-table">
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
                            <button type="button" class="btn btn-outline-success btn-icon js__approve_comment" data-value="0" title="@lang('sArticles::global.comment_hidden')" aria-label="@lang('sArticles::global.comment_hidden')">
                                {!! svg('tabler-eye-off', 'sarticles-icon sarticles-icon-hide')->toHtml() !!}
                                {!! svg('tabler-eye', 'sarticles-icon sarticles-icon-show')->toHtml() !!}
                            </button>
                        @else
                            <button type="button" class="btn btn-outline-danger btn-icon js__approve_comment" data-value="1" title="@lang('sArticles::global.approved')" aria-label="@lang('sArticles::global.approved')">
                                {!! svg('tabler-eye-off', 'sarticles-icon sarticles-icon-hide')->toHtml() !!}
                                {!! svg('tabler-eye', 'sarticles-icon sarticles-icon-show')->toHtml() !!}
                            </button>
                        @endif
                        <button class="btn btn-primary btn-icon js__comment_edit" data-toggle="modal" data-target="#editComment" data-item="{{ $comment->toJson() }}" title="@lang('global.edit')" aria-label="@lang('global.edit')">
                            {!! svg('tabler-edit', 'sarticles-icon')->toHtml() !!}
                        </button>
                        <a href="#"
                           class="btn btn-outline-danger btn-icon"
                           data-href="{{$url}}&get=commentDelete&i={{ $comment->comid }}&article={{ $comment->article_id }}{{ (request()->get('page')) ? '&page='.request()->get('page') : '' }}"
                           data-delete="{{$comment->comid}}"
                           data-name="{!! Str::limit($comment->comment, 50, "...") !!}"
                           title="@lang('global.remove')"
                           aria-label="@lang('global.remove')">
                            {!! svg('tabler-trash', 'sarticles-icon')->toHtml() !!}
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
    <script>
        function sArticlesApplySearch() {
            var input = jQuery(document).find("[name=\"search\"]");
            var target = new URL(window.location.href);
            var value = input.val();

            if (value) {
                target.searchParams.set('search', value);
            } else {
                target.searchParams.delete('search');
            }
            target.searchParams.delete('page');

            if (window.sArticlesLoadModuleView) {
                sArticlesLoadModuleView(target.toString());
            } else {
                window.location.href = target.toString();
            }
        }

        jQuery(document).on("click", ".sarticles-search__icon", sArticlesApplySearch);
        jQuery(document).on('keydown', "[name=\"search\"]", function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sArticlesApplySearch();
            }
        });
    </script>
    @include('sArticles::partials.commentsjs')
@endpush
