<?php
use Illuminate\Support\Facades\Cache;
use Seiger\sArticles\Models\sArticle;
$articles = Cache::rememberForever('sArticles-articles-list', function () {return (sArticle::active()->get()->pluck('pagetitle', 'id')->toArray() ?? []);});
echo '<select id="'.($id ?? '').'" name="builder['.($i ?? '9999').'][previewarticle][id]" class="form-control" onchange="documentDirty=true;">';
echo '<option></option>';
foreach ($articles as $articleId => $articleTitle) {
    $selected = ($value['id'] ?? 0) == $articleId ? "selected" : "";
    echo '<option value="'.$articleId.'" '.$selected.'>'.$articleTitle.' ('.$articleId.')</option>';
}
echo '</select>';
