<div class="input-group">
    <input type="text" class="form-control" name="search" value="{{request()->search ?? ''}}" placeholder="@lang('global.resource_title') @lang('sOffers::global.or') @lang('global.long_title')" />
    <span class="input-group-btn">
        <button class="btn btn-light js_search" type="button" title="@lang('global.search')" style="padding:0 5px;color:#0275d8;">
            <i class="fa fa-search" style="font-size:large;margin:5px;"></i>
        </button>
    </span>
</div>
<div class="split my-1"></div>
<div class="table-responsive">
    <table class="table table-condensed table-hover sectionTrans">
        <thead>
        <tr>
            <th style="text-align:center;">@lang('global.name')</th>
            <th style="width:70px;text-align:center;">@lang('sOffers::global.availability')</th>
            <th id="action-btns">@lang('global.onlineusers_action')</th>
        </tr>
        </thead>
        <tbody>
        @php($offers = sOffers::all())
        @foreach($offers as $offer)
            <tr>
                <td>
                    <img src="{{$offer->coverSrc}}" alt="{{$offer->coverSrc}}" class="post-thumbnail">
                    <a href="{{$offer->link}}" target="_blank"><b>{{$offer->pagetitle ?? __('sOffers::global.no_text')}}</b> <small>({{$offer->id}})</small></a>
                </td>
                <td>
                    @if($offer->published)
                        <span class="badge badge-success">@lang('global.page_data_published') <small>({{$offer->views}})</small></span>
                    @else
                        <span class="badge badge-dark">@lang('global.page_data_unpublished') <small>({{$offer->views}})</small></span>
                    @endif
                </td>
                <td style="text-align:center;">
                    <div class="btn-group">
                        <a href="{{$url}}&get=offer&i={{$offer->id}}" class="btn btn-outline-success">
                            <i class="fa fa-pencil"></i> <span>@lang('global.edit')</span>
                        </a>
                        <a href="#" data-href="{{$url}}&get=offerDelete&i={{$offer->id}}" data-delete="{{$offer->id}}" data-name="{{$offer->pagetitle}}" class="btn btn-outline-danger">
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
<div class="paginator">{{$offers->render()}}</div>

@push('scripts.bot')
    <div id="actions">
        <div class="btn-group">
            <a id="Button2" href="{!!$url!!}&get=offer&i=0" class="btn btn-primary" title="@lang('sOffers::global.add_help')">
                <i class="fa fa-plus"></i> <span>@lang('global.add')</span>
            </a>
        </div>
    </div>

    <script>
        jQuery(document).on("click", ".js_search", function () {
            var _form = jQuery(document).find("[name=\"search\"]");
            window.location.href = window.location.href+'&'+_form.serialize();
        });
        jQuery(document).on('keypress', "[name=\"search\"]", function(e) {
            if (e.which == 13) {
                var _form = jQuery(document).find("[name=\"search\"]");
                window.location.href = window.location.href+'&'+_form.serialize();
            }
        });
    </script>
@endpush