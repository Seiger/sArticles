<?php if (is_array($value ?? []) && is_array($value['src'] ?? []) && count($value['src'] ?? [])) {
    foreach ($value['src'] as $key => $src) {
        if (trim($src)) {
            $id = ($id ?? 'slider');
            if ($key > 0) {
                $id = $id.$key;
            }
            echo '<div class="slide row form-row">
    <div class="col-auto col-title-7">
        <div id="image_for_' . $id . '" class="image_for_field" data-image="'.$src.'" onclick="BrowseServer(\'' . $id . '\')" style="background-image: url(\'' . MODX_SITE_URL . $src . '\');"></div>
    </div>
    <div class="col">
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">IMG</span>
            </div>
            <input type="text" id="' . $id . '" class="form-control" name="builder[' . ($i ?? '9999') . '][slider][src][]" value="' . $src . '" placeholder="Image file" onchange="documentDirty=true;">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" onclick="BrowseServer(\'' . $id . '\')"><i class="fas fa-image"></i></button>
            </div>
        </div>
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">ALT</span>
            </div>
            <input type="text" class="form-control" name="builder[' . ($i ?? '9999') . '][slider][alt][]" value="' . $value['alt'][$key] . '" placeholder="Image alt" onchange="documentDirty=true;">
        </div>
        <button onclick="onAddSlide($(this))" type="button" class="btn btn-primary btn-lg btn-block">Add new Image</button>
        <script>document.getElementById(\'' . $id . '\').addEventListener(\'change\', evoRenderImageCheck, false);</script>
    </div>
    <div class="col-auto"><i onclick="onDeleteSlide($(this))" class="fa fa-minus-circle text-danger b-btn-del"></i></div>
</div>';
        }
    }
} else {
    echo '<div class="slide row form-row">
    <div class="col-auto col-title-7">
        <div id="image_for_' . ($id ?? '') . '" class="image_for_field" data-image="slider" onclick="BrowseServer(\'' . ($id ?? '') . '\')" style="background-image: url(\'' . MODX_SITE_URL . '\');"></div>
    </div>
    <div class="col">
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">IMG</span>
            </div>
            <input type="text" id="' . ($id ?? '') . '" class="form-control" name="builder[' . ($i ?? '9999') . '][slider][src][]" value="" placeholder="Image file" onchange="documentDirty=true;">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" onclick="BrowseServer(\'' . ($id ?? '') . '\')"><i class="fas fa-image"></i></button>
            </div>
        </div>
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">ALT</span>
            </div>
            <input type="text" class="form-control" name="builder[' . ($i ?? '9999') . '][slider][alt][]" value="" placeholder="Image alt" onchange="documentDirty=true;">
        </div>
        <button onclick="onAddSlide($(this))" type="button" class="btn btn-primary btn-lg btn-block">Add new Image</button>
        <script>document.getElementById(\'' . ($id ?? '') . '\').addEventListener(\'change\', evoRenderImageCheck, false);</script>
    </div>
    <div class="col-auto"><i onclick="onDeleteSlide($(this))" class="fa fa-minus-circle text-danger b-btn-del"></i></div>
</div>';
}
