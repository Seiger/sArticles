<blockquote>{!!$value['text'] ?? ''!!}
    @if (trim($value['author'] ?? ''))
        <figcaption>{{$value['author']}}</figcaption>
    @endif
</blockquote>
