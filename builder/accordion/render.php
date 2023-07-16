<?php if (is_array($value ?? []) && is_array($value['title'] ?? []) && count($value['title'] ?? [])) {
    echo '<section class="accordion">';
    foreach ($value['title'] as $key => $title) {
        echo '<div class="accordion-item">';
            echo '<button id="accordion-button-'.$key.'" aria-expanded="false">';
                echo '<span class="icon-img"><img src="/img/icon-tryzub.svg" alt="" loading="lazy"/></span>';
                echo '<span class="accordion-title">'.$title.'</span>';
                echo '<span class="icon" aria-hidden="true"></span>';
            echo '</button>';
            echo '<div class="accordion-content">';
            echo $value['text'][$key];
            echo '</div>';
        echo '</div>';
    }
    echo '</section>';
}
