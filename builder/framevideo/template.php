<?php echo '<input id="'.($id ?? '').'" name="builder['.($i ?? '9999').'][framevideo]" value="'.($value ?? '').'" type="text" class="form-control" onchange="documentDirty=true;">';
if (trim($value ?? '')) {
    echo '<iframe height="150" src="'.$value.'" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>';
}
