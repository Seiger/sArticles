@if (is_array($value ?? []) && is_array($value['src'] ?? []) && count($value['src']))
    <div class="swiper article-gallery">
        <div class="swiper-wrapper">
            @foreach ($value['src'] as $key => $src)
                @if (trim($src))
                    <div class="swiper-slide">
                        <img src="{{$src}}" alt="{{$value['alt'][$key]}}"/>
                    </div>
                @endif
            @endforeach
        </div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
    </div>
@endif
