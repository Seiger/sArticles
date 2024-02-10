<small>Зображення</small>
<div class="row form-row">
    <div class="col-auto col-title-7">
        <div id="image_for_{{$id ?? ''}}" class="image_for_field" data-image="{{$value['src'] ?? 'singleimg'}}" onclick="BrowseServer('{{$id ?? ''}}')" style="background-image: url('{{MODX_SITE_URL.($value['src'] ?? '')}}')"></div>
    </div>
    <div class="col">
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">IMG</span>
            </div>
            <input type="text" id="{{$id ?? ''}}" class="form-control" name="builder[{{$i ?? '9999'}}][singleimg][src]" value="{{$value['src'] ?? ''}}" placeholder="Файл зображення" onchange="documentDirty=true">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" onclick="BrowseServer('{{$id ?? ''}}')"><i class="fas fa-image"></i></button>
            </div>
        </div>
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">TIT</span>
            </div>
            <input type="text" class="form-control" name="builder[{{$i ?? '9999'}}][singleimg][title]" value="{{$value['title'] ?? ''}}" placeholder="Підпис до зображення" onchange="documentDirty=true">
            <div class="input-group-prepend">
                <span class="input-group-text">ALT</span>
            </div>
            <input type="text" class="form-control" name="builder[{{$i ?? '9999'}}][singleimg][alt]" value="{{$value['alt'] ?? ''}}" placeholder="Альтернативний текст" onchange="documentDirty=true">
        </div>
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">LINK</span>
            </div>
            <input type="text" class="form-control" name="builder[{{$i ?? '9999'}}][singleimg][link]" value="{{$value['link'] ?? ''}}" placeholder="Посилання Зображення" onchange="documentDirty=true">
        </div>
        <script>document.getElementById('{{$id ?? ''}}').addEventListener('change', evoRenderImageCheck, false)</script>
    </div>
</div>
