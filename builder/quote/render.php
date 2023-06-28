<?php $result = '<blockquote>'.($value['text'] ?? '');
if (trim($value['author'] ?? '')) {
    if (trim($value['src'])) {
        $result .= '<figcaption><img src="'.$value['src'].'" alt="'.$value['author'].' quote" />'.$value['author'].'</figcaption>';
    } else {
        $result .= '<figcaption>'.$value['author'].'</figcaption>';
    }
}
$result .= '</blockquote>';
echo $result;
