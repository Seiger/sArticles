@php
    use Illuminate\Support\Facades\Cache;
    use Seiger\sArticles\Models\sArticle;
    $articles = Cache::rememberForever('sArticles-articles-list', function () {return (sArticle::active()->get()->pluck('pagetitle', 'id')->toArray() ?? []);});
@endphp
<select id="{{$id ?? ''}}" name="builder[{{$i ?? '9999'}}][previewarticle][id]" class="form-control" onchange="documentDirty=true;">
    <option></option>
    @foreach ($articles as $articleId => $articleTitle)
        @php($selected = ($value['id'] ?? 0) == $articleId ? "selected" : "")
        <option value="{{$articleId}}" {{$selected}}>{{$articleTitle}} ({{$articleId}})</option>
    @endforeach
</select>
