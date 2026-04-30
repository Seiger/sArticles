@php
    use Illuminate\Support\Facades\Cache;
    use Seiger\sArticles\Models\sArticlesPoll;
    $langDefault = evo()->getConfig('s_lang_default', 'base');
    $polls = Cache::rememberForever('sArticles-polls-list', function () {return (sArticlesPoll::all()->pluck('question', 'pollid')->toArray() ?? []);});
@endphp
<select id="{{$id ?? ''}}" name="builder[{{$i ?? '9999'}}][poll][id]" class="form-control" onchange="documentDirty=true;">
    <option></option>
    @foreach ($polls as $pollId => $pollTitle)
        @php
            $selected = ($value['id'] ?? 0) == $pollId ? "selected" : "";
            $title = is_array($pollTitle)
                ? ($pollTitle[evo()->getLocale()] ?? $pollTitle[$langDefault] ?? $pollTitle['base'] ?? reset($pollTitle))
                : $pollTitle;
        @endphp
        <option value="{{$pollId}}" {{$selected}}>{{$title}} ({{$pollId}})</option>
    @endforeach
</select>
