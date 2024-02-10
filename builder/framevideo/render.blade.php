@if (trim($value ?? ''))
    @php($r = '/(?im)\b(?:https?:\/\/)?(?:w{3}\.)?youtu(?:be)?\.(?:com|be)\/(?:(?:\??v=?i?=?\/?)|watch\?vi?=|watch\?.*?&v=|embed\/|)([A-Z0-9_-]{11})\S*(?=\s|$)/')
    @php(preg_match_all($r, $value, $matches, PREG_SET_ORDER, 0))
    @if (isset($matches[0][1]))
        <lite-youtube videoid="{{$matches[0][1]}}" params="modestbranding=1&rel=0&enablejsapi=1&vq=hd1080">
            <button type="button" class="lty-playbtn"><span class="lyt-visually-hidden">Play Video: Keynote (Google I/O 18)</span></button>
        </lite-youtube>
    @endif
@endif
