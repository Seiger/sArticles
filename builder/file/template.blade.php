<small>Файл</small>
<div class="row form-row">
    <div class="col-auto col-title-7">
        <div id="image_for_{{$id ?? ''}}" class="image_for_field icon" data-image="{{$value['icon'] ?? 'singlefile'}}" onclick="BrowseServer('{{$id ?? ''}}')" style="background-image: url('{{MODX_SITE_URL.($value['icon'] ?? '')}}')"></div>
    </div>
    <div class="col">
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">ICON</span>
            </div>
            <input type="text" id="{{$id ?? ''}}" class="form-control" name="builder[{{$i ?? '9999'}}][singlefile][icon]" value="{{$value['icon'] ?? ''}}" placeholder="Іконка файлу" onchange="documentDirty=true">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" onclick="BrowseServer('{{$id ?? ''}}')"><i class="fas fa-image"></i></button>
            </div>
            <div class="input-group-prepend">
                <span class="input-group-text">FILE</span>
            </div>
            <input type="text" id="file-{{$id ?? ''}}" class="form-control" name="builder[{{$i ?? '9999'}}][singlefile][file]" value="{{$value['file'] ?? ''}}" placeholder="Файл для завантаження" onchange="documentDirty=true">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" onclick="BrowseFileServer('file-{{$id ?? ''}}')"><i class="fas fa-file"></i></button>
            </div>
        </div>
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">TITLE</span>
            </div>
            <input type="text" class="form-control" name="builder[{{$i ?? '9999'}}][singlefile][title]" value="{{$value['title'] ?? ''}}" placeholder="Підпис до файлу" onchange="documentDirty=true">
        </div>
        <script>document.getElementById('{{$id ?? ''}}').addEventListener('change', evoRenderImageCheck, false);</script>
    </div>
</div>
