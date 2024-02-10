<div class="row form-row">
    <div class="col-auto col-title-7">
        <div class="input-group mb-3">
            <div class="input-group-prepend"><span class="input-group-text">IMG</span></div>
            <input type="text" id="{{$id ?? ''}}" class="form-control" name="builder[{{$i ?? '9999'}}][quote][src]" value="{{$value['src'] ?? ''}}" placeholder="Image file" onchange="documentDirty=true;">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" onclick="BrowseServer('{{$id ?? ''}}')"><i class="fas fa-image"></i></button>
            </div>
        </div>
        <div id="image_for_{{$id ?? ''}}" class="image_for_field" data-image="{{$value['src'] ?? 'quote'}}" onclick="BrowseServer('{{$id ?? ''}}')" style="background-image: url('{{MODX_SITE_URL.($value['src'] ?? 'quote')}}');"></div>
    </div>
    <div class="col">
        <div class="input-group mb-3">
            <div class="input-group-prepend"><span class="input-group-text">AUTHOR</span></div>
            <input type="text" class="form-control" name="builder[{{$i ?? '9999'}}][quote][author]" value="{{$value['author'] ?? ''}}" placeholder="Author name" onchange="documentDirty=true;">
        </div>
        <textarea class="form-control" name="builder[{{$i ?? '9999'}}][quote][text]" rows="6" placeholder="Quote text" onchange="documentDirty=true;">{!!$value['text'] ?? ''!!}</textarea>
        <script>document.getElementById('{{$id ?? ''}}').addEventListener('change', evoRenderImageCheck, false)</script>
    </div>
</div>
