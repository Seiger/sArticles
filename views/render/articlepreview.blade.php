@if (is_array($value ?? []) && isset($value['id']) && $value['id'] > 0)
    @php($article = \Seiger\sArticles\Models\sArticle::find($value['id']))
    @if ($article)
        <section class="article__preview">
            <a href="{{$article->link}}" class="article__preview-link">
                <div class="article__preview-img"><img src="{{$article->coverSrc}}" alt=""/></div>
                <div class="article__preview-text"><p class="article__preview-read">@lang('Читайте також')</p>
                    <p class="article__preview-title">{{$article->pagetitle}}</p>
                    <p class="article__preview-descr">{{$article->introtext}}</p>
                </div>
            </a>
        </section>
    @endif
@endif
