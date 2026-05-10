<section class="article__figure">
    @if (trim($value['link'] ?? ''))
        <a href="{{$value['link'] ?? ''}}">
    @endif
        <img src="{{$value['src'] ?? ''}}" alt="{{$value['alt'] ?? ''}}" class="article__figure-img" loading="lazy"/>
        @if (trim($value['title'] ?? ''))
            <span class="article__figure-text">{{$value['title']}}</span>
        @endif
    @if (trim($value['link'] ?? ''))
        </a>
    @endif
</section>
