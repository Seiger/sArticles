<small>Зображення та текст</small>
<div class="row form-row">
    <div class="col-auto col-title-7">
        <div id="image_for_img-{{$id ?? ''}}" class="image_for_field" data-image="{{$value['src'] ?? 'imgandtext'}}" onclick="BrowseServer('img-{{$id ?? ''}}')" style="background-image: url('{{MODX_SITE_URL.($value['src'] ?? '')}}')"></div>
    </div>
    <div class="col">
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">ALIGN</span>
            </div>
            <select class="form-control" name="builder[{{$i ?? '9999'}}][imgandtext][align]">
                <option value="left" {{((trim($value['align'] ?? '') && $value['align'] == "left") ? "selected" : "")}}>Зображення зліва</option>
                <option value="right" {{((trim($value['align'] ?? '') && $value['align'] == "right") ? "selected" : "")}}>Зображення справа</option>
            </select>
            <div class="input-group-prepend">
                <span class="input-group-text">IMG</span>
            </div>
            <input id="img-{{$id ?? ''}}" type="text" class="form-control" name="builder[{{$i ?? '9999'}}][imgandtext][src]" value="{{$value['src'] ?? ''}}" placeholder="Файл зображення" onchange="documentDirty=true;">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" onclick="BrowseServer('img-{{$id ?? ''}}')"><i class="fas fa-image"></i></button>
            </div>
        </div>
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">TIT</span>
            </div>
            <input type="text" class="form-control" name="builder[{{$i ?? '9999'}}][imgandtext][title]" value="{{$value['title'] ?? ''}}" placeholder="Підпис до зображення" onchange="documentDirty=true;">
            <div class="input-group-prepend">
                <span class="input-group-text">ALT</span>
            </div>
            <input type="text" class="form-control" name="builder[{{$i ?? '9999'}}][imgandtext][alt]" value="{{$value['alt'] ?? ''}}" placeholder="Альтернативний текст" onchange="documentDirty=true;">
        </div>
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">LINK</span>
            </div>
            <input type="text" class="form-control" name="builder[{{$i ?? '9999'}}][imgandtext][link]" value="{{$value['link'] ?? ''}}" placeholder="Посилання Зображення" onchange="documentDirty=true;">
        </div>
        <script>document.getElementById('img-{{$id ?? ''}}').addEventListener('change', evoRenderImageCheck, false);</script>
    </div>
</div>
<textarea id="{{$id ?? ''}}" name="builder[{{$i ?? '9999'}}][imgandtext][text]" rows="3" onchange="documentDirty=true;">{!!$value['text'] ?? ''!!}</textarea>
