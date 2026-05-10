@php($array = explode("\r\n", $value ?? ''))
<section class="article__quest">
    <img class="article__quest-img" src="/assets/images/sarticles/icon-note.svg" alt=""/>
    <div class="article__quest-box"><p>{!!implode('</p><p>', $array)!!}</p></div>
</section>
