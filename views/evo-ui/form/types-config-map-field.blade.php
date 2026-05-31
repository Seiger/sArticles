@php
    $itemName = (string) $itemField['name'];
    $itemType = (string) ($itemField['type'] ?? 'text');
    $itemLabel = __($itemField['label'] ?? $itemName);
    $itemModel = 'data.' . $name . '.' . $index . '.' . $itemName;
@endphp

<label class="evo-ui-field {{ ($itemField['span'] ?? null) === 'full' ? 'evo-ui-field--full' : '' }}">
    <span class="evo-ui-field__label">
        <span>{{ $itemLabel }}</span>
    </span>

    @if($itemType === 'checkbox')
        <span class="evo-ui-checkbox">
            <input type="checkbox" wire:model.live="{{ $itemModel }}">
        </span>
    @elseif($itemType === 'multi-select')
        <select
            class="evo-ui-input evo-ui-select--multiple"
            wire:model.live="{{ $itemModel }}"
            multiple
            size="{{ (int) ($itemField['size'] ?? 5) }}"
        >
            @foreach($controller->fieldOptions($itemField) as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>
    @elseif($itemType === 'number')
        <input class="evo-ui-input" type="number" wire:model.blur="{{ $itemModel }}" @if(isset($itemField['min'])) min="{{ $itemField['min'] }}" @endif>
    @elseif($itemType === 'textarea')
        <textarea class="evo-ui-input evo-ui-textarea" rows="{{ $itemField['rows'] ?? 3 }}" wire:model.blur="{{ $itemModel }}"></textarea>
    @else
        <input class="evo-ui-input" type="text" wire:model.blur="{{ $itemModel }}">
    @endif
</label>
