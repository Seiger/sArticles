<?php $result = '<section class="iwt__section">';
if ((trim($value['align'] ?? '') != "right")) {
    $result .= '<div class="iwt__section-box">';
        if (trim($value['link'] ?? '')) {
            $result .= '<a href="' . ($value['link'] ?? '') . '">';
        }
            $result .= '<img src="' . ($value['src'] ?? '') . '" alt="' . ($value['alt'] ?? '') . '" class="article__figure-img" loading="lazy"/>';
            if (trim($value['title'] ?? '')) {
                $result .= '<span class="article__figure-text">' . $value['title'] . '</span>';
            }
        if (trim($value['link'] ?? '')) {
            $result .= '</a>';
        }
    $result .= '</div>';
}
$result .= '<div class="iwt__section-box">'.$value['text'].'</div>';
if ((trim($value['align'] ?? '') == "right")) {
    $result .= '<div class="iwt__section-box">';
    if (trim($value['link'] ?? '')) {
        $result .= '<a href="' . ($value['link'] ?? '') . '">';
    }
    $result .= '<img src="' . ($value['src'] ?? '') . '" alt="' . ($value['alt'] ?? '') . '" class="article__figure-img" loading="lazy"/>';
    if (trim($value['title'] ?? '')) {
        $result .= '<span class="article__figure-text">' . $value['title'] . '</span>';
    }
    if (trim($value['link'] ?? '')) {
        $result .= '</a>';
    }
    $result .= '</div>';
}
$result .= '</section>';
echo $result;
