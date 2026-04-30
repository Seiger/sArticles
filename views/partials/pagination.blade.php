@if ($paginator->hasPages())
    {{-- Full link generate --}}
    @php
        switch (request()->get('get'))
       {
           case 'article_comments':
               $fullUrl = sArticles::moduleUrl() . '&get='.request()->get('get').'&i='.request()->get('i') . (request()->has('search') ? '&search=' . request()->search : '');
               break;
           case 'comments':
               $fullUrl = sArticles::moduleUrl() . '&get='.request()->get('get') . (request()->has('search') ? '&search=' . request()->search : '');
               break;
           default:
                $availability = in_array(request()->get('availability'), ['published', 'unpublished'], true) ? request()->get('availability') : '';
                $fullUrl = sArticles::moduleUrl()
                    . '&get=' . request()->get('get', 'articles')
                    . (request()->has('search') ? '&search=' . request()->search : '')
                    . (request()->has('type') ? '&type=' . request()->type : '')
                    . (trim(request()->input('section', '')) !== '' ? '&section=' . request()->input('section') : '')
                    . (trim(request()->input('category', '')) !== '' ? '&category=' . request()->input('category') : '')
                    . ($availability ? '&availability=' . $availability : '')
                    . (trim(request()->input('tag', '')) !== '' ? '&tag=' . request()->input('tag') : '')
                    . (trim(request()->input('feature', '')) !== '' ? '&feature=' . request()->input('feature') : '');
        }
        $paginator->withPath($fullUrl);
    @endphp
    <nav role="navigation" aria-label="{{__('Pagination Navigation')}}" id="translatePagination" class="sarticles-pagination">
        <ul class="pagination justify-content-center">
            {{-- Previous Page Link --}}
            @if (!$paginator->onFirstPage())
                <li class="page-item">
                    <a class="page-link" href="{{$paginator->url(1)}}" aria-label="First">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="{{$paginator->previousPageUrl()}}" aria-label="Previous">
                        <span aria-hidden="true">&lsaquo;</span>
                    </a>
                </li>
            @endif
            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{$element}}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{$page}}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{$paginator->url($page)}}">{{$page}}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{$paginator->nextPageUrl()}}" aria-label="Next">
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="{{$paginator->url($paginator->lastPage())}}" aria-label="Last">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            @endif
        </ul>
    </nav>
@endif
