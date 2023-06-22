<?php $result = '<blockquote>'.($value['text'] ?? '');
    if (trim($value['author'] ?? '')) {
        $result .= '<figcaption>'.$value['author'].'</figcaption>';
    }
    $result .= '</blockquote>';
    echo $result;
