@php($items = is_array($value['items'] ?? null) && count($value['items']) ? $value['items'] : [['question' => '', 'answer' => '']])

<small>FAQ</small>
@foreach($items as $key => $item)
    <div class="row form-row">
        <div class="col">
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text">Question</span>
                </div>
                <input
                    name="builder[{{$i ?? '9999'}}][faq][items][{{$key}}][question]"
                    value="{{$item['question'] ?? ''}}"
                    type="text"
                    class="form-control"
                    placeholder="Question"
                    onchange="documentDirty=true;"
                >
            </div>
            <textarea
                name="builder[{{$i ?? '9999'}}][faq][items][{{$key}}][answer]"
                rows="4"
                class="form-control"
                placeholder="Answer"
                onchange="documentDirty=true;"
            >{{$item['answer'] ?? ''}}</textarea>
        </div>
    </div>
@endforeach
