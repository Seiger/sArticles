<div class="row-col col-lg-6 col-md-6 col-12">
    <div class="row form-row">
        <div class="col-auto">
            <label for="{{$id ?? ''}}">{{$title ?? ''}}</label>
        </div>
        <div class="col">
            <select id="{{$id ?? ''}}" class="form-control select2" name="{{$name ?? ''}}" multiple onchange="documentDirty=true;">
                @foreach(($values ?? []) as $key => $value)
                    <option value="{{$key}}" @if(in_array($key, $itemValues)) selected @endif>{{$value}}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>