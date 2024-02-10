@php
    use Illuminate\Support\Facades\Cache;
    use Seiger\sArticles\Models\sArticlesPoll;
    $polls = Cache::rememberForever('sArticles-polls-list', function () {return (sArticlesPoll::all()->pluck('question', 'pollid')->toArray() ?? []);});
@endphp
<select id="{{$id ?? ''}}" name="builder[{{$i ?? '9999'}}][poll][id]" class="form-control" onchange="documentDirty=true;">
    <option></option>
    @foreach ($polls as $pollId => $pollTitle)
        @php($selected = ($value['id'] ?? 0) == $pollId ? "selected" : "")
        <option value="{{$pollId}}" {{$selected}}>{{$pollTitle[evo()->getLocale()] ?? $pollTitle[$sArticlesController->langDefault()]}} ({{$pollId}})</option>
    @endforeach
</select>
