<?php if (is_array($value ?? []) && isset($value['id']) && $value['id'] > 0) {
    $article = \Seiger\sArticles\Models\sArticle::find($value['id']);
    $result = '';
    if ($article) {
        $result .= '<section class="article__preview">';
        $result .= '<a href="'.$article->link.'" class="article__preview-link">';
        $result .= '<div class="article__preview-img"><img src="'.$article->coverSrc.'" alt=""/></div>';
        $result .= '<div class="article__preview-text"><p class="article__preview-read">'.__('Читайте також').'</p>';
        $result .= '<p class="article__preview-title">'.$article->pagetitle.'</p>';
        $result .= '<p class="article__preview-descr">'.$article->introtext.'</p></div></a></section>';
    }
    echo $result;
}
