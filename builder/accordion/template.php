<?php echo '<small>Список текстів</small>';
if (is_array($value ?? []) && is_array($value['title'] ?? []) && count($value['title'] ?? [])) {
    $idOrig = ($id ?? 'accordion');
    foreach ($value['title'] as $key => $title) {
        if (trim($title)) {
            if ($key > 0) {
                $id = $idOrig.$key;
            }
            echo '<div class="accord row form-row">
    <div class="col">
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">Title</span>
            </div>
            <input name="builder['.($i ?? '9999').'][accordion][title][]" value="'.$title.'" type="text" class="form-control" placeholder="Заголовок" onchange="documentDirty=true;">
        </div>
        <textarea id="'.($id ?? '').'" name="builder['.($i ?? '9999').'][accordion][text][]" data-id="'.($idOrig ?? '').'" rows="3" onchange="documentDirty=true;">'.$value['text'][$key].'</textarea>
        <button onclick="onAddAccord($(this))" type="button" class="btn btn-primary btn-xs btn-block">Додати текст</button>
    </div>
    <div class="col-auto"><i onclick="onDeleteAccord($(this))" class="fa fa-minus-circle text-danger b-btn-del"></i></div>
</div>';
        }
    }
} else {
    echo '<div class="accord row form-row">
    <div class="col">
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">Title</span>
            </div>
            <input name="builder['.($i ?? '9999').'][accordion][title][]" value="" type="text" class="form-control" placeholder="Заголовок" onchange="documentDirty=true;">
        </div>
        <textarea id="'.($id ?? '').'" name="builder['.($i ?? '9999').'][accordion][text][]" data-id="" rows="3" onchange="documentDirty=true;"></textarea>
        <button onclick="onAddAccord($(this))" type="button" class="btn btn-primary btn-xs btn-block">Додати текст</button>
    </div>
    <div class="col-auto"><i onclick="onDeleteAccord($(this))" class="fa fa-minus-circle text-danger b-btn-del"></i></div>
</div>';
}
