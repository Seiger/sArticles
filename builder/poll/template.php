<?php
use Illuminate\Support\Facades\Cache;
use Seiger\sArticles\Models\sArticlesPoll;
$polls = Cache::rememberForever('sArticles-polls-list', function () {return (sArticlesPoll::all()->pluck('question', 'pollid')->toArray() ?? []);});
echo '<select id="'.($id ?? '').'" name="builder['.($i ?? '9999').'][poll][id]" class="form-control" onchange="documentDirty=true;">';
echo '<option></option>';
$sArticlesController = new \Seiger\sArticles\Controllers\sArticlesController();
foreach ($polls as $pollId => $pollTitle) {
    $selected = ($value['id'] ?? 0) == $pollId ? "selected" : "";
    echo '<option value="'.$pollId.'" '.$selected.'>'.(trim($pollTitle[evo()->getLocale()] ?? ($pollTitle[$sArticlesController->langDefault()] ?? __('sArticles::global.no_text'))) ?: __('sArticles::global.no_text')).'</option>';
}
echo '</select>';
