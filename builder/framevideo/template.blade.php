<small>Відео</small>
<input id="{{$id ?? ''}}" name="builder[{{$i ?? '9999'}}][framevideo]" value="{{$value ?? ''}}" type="text" class="form-control" onchange="documentDirty=true;">
@if (trim($value ?? ''))
    @php($r = '/(?im)\b(?:https?:\/\/)?(?:w{3}\.)?youtu(?:be)?\.(?:com|be)\/(?:(?:\??v=?i?=?\/?)|watch\?vi?=|watch\?.*?&v=|embed\/|)([A-Z0-9_-]{11})\S*(?=\s|$)/')
    @php(preg_match_all($r, $value, $matches, PREG_SET_ORDER, 0))
    @if (isset($matches[0][1])) {
        <iframe height="150" src="https://www.youtube.com/embed/{{$matches[0][1]}}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>
    @endif
@endif
