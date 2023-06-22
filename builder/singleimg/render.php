<?php $result = '<figure><img src="'.($value['src'] ?? '').'" alt="'.($value['alt'] ?? '').'"/>';
    if (trim($value['title'] ?? '')) {
        $result .= '<figcaption>'.$value['title'].'</figcaption>';
    }
    $result .= '</figure>';
    echo $result;
