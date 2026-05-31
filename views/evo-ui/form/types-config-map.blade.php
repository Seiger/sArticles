@php
    $name = (string) ($field['name'] ?? 'types');
    $keyField = (string) ($field['key_field'] ?? '_key');
    $label = __($field['label'] ?? $name);
    $showLabel = ($field['show_label'] ?? ($field['label_visible'] ?? true)) !== false && ($field['label'] ?? null) !== false;
    $help = $field['help'] ?? $field['description'] ?? null;
    $helpText = $help ? __($help) : '';
    $itemFields = collect((array) ($field['fields'] ?? []))
        ->filter(fn ($itemField) => is_array($itemField) && !empty($itemField['name']))
        ->values()
        ->all();
    $inputFields = collect($itemFields)
        ->reject(fn ($itemField) => (($itemField['type'] ?? 'text') === 'checkbox'))
        ->values()
        ->all();
    $toggleFields = collect($itemFields)
        ->filter(fn ($itemField) => (($itemField['type'] ?? 'text') === 'checkbox'))
        ->values()
        ->all();
    $items = array_values((array) data_get($controller->data, $name, []));
    $addLabel = __($field['add_label'] ?? 'evo::global.action_add');
@endphp

<div
    @class([
        'evo-ui-field',
        'evo-ui-field--full' => ($field['span'] ?? null) === 'full',
        'evo-ui-field--compact' => ($field['size'] ?? null) === 'compact',
        'evo-ui-field--no-label' => !$showLabel,
        'has-error' => $error,
    ])
>
    <span class="{{ $showLabel ? 'evo-ui-field__label' : 'evo-ui-sr-only' }}">
        <span>
            <span>{{ $label }}</span>
            @if(!empty($field['config_key']))
                <code>{{ $field['config_key'] }}</code>
            @endif
        </span>
        @if($helpText)
            <span
                class="evo-ui-field__help"
                title="{{ $helpText }}"
                aria-label="{{ $helpText }}"
                data-tooltip="{{ $helpText }}"
                data-evo-tooltip="{{ $helpText }}"
                tabindex="0"
            >?</span>
        @endif
    </span>

    <div class="evo-ui-config-map">
        <div class="evo-ui-config-map__toolbar">
            <button type="button" class="evo-ui-btn evo-ui-btn--success" wire:click.stop="addConfigMapItem('{{ $name }}')">
                <x-evo::icon name="plus" class="evo-ui-btn__icon" />
                <span class="evo-ui-btn__label">{{ $addLabel }}</span>
            </button>
        </div>

        <div class="evo-ui-config-map__items">
            @foreach($items as $index => $item)
                @php
                    $itemKey = (string) data_get($item, $keyField, '');
                    $deleteBlocked = $controller->configMapDeleteBlocked($field, (array) $item, $index);
                    $usageCount = $controller->configMapUsageCount($field, (array) $item);
                    $titleField = (string) ($field['title_field'] ?? 'name');
                    $title = trim((string) data_get($item, $titleField, $itemKey));
                    $title = $title !== '' ? $title : $itemKey;
                @endphp
                <div class="evo-ui-config-map__item" wire:key="config-map-{{ $name }}-{{ $index }}-{{ $itemKey }}">
                    <div class="evo-ui-config-map__item-header">
                        <span>
                            <b>{{ $title }}</b>
                            <code>{{ $itemKey }}</code>
                            @if($usageCount > 0)
                                <small>{{ __('evo::global.records_count', ['count' => $usageCount]) }}</small>
                            @endif
                        </span>
                        <button
                            type="button"
                            class="evo-ui-row-action evo-ui-row-action--danger {{ $deleteBlocked ? 'is-disabled' : '' }}"
                            title="@lang('global.remove')"
                            aria-label="@lang('global.remove')"
                            @if($deleteBlocked) disabled @endif
                            wire:click.stop="removeConfigMapItem('{{ $name }}', {{ $index }})"
                        >
                            <x-evo::icon name="trash" />
                        </button>
                    </div>

                    <div class="evo-ui-config-map__fields" style="align-items: start;">
                        <div style="display: grid; gap: 8px;">
                            <label class="evo-ui-field evo-ui-field--compact">
                                <span class="evo-ui-field__label">
                                    <span>{{ __($field['key_label'] ?? 'evo::global.key') }}</span>
                                </span>
                                <input
                                    class="evo-ui-input"
                                    type="text"
                                    wire:model.blur="data.{{ $name }}.{{ $index }}.{{ $keyField }}"
                                    @if($index === 0 && ($field['lock_first_key'] ?? false)) readonly @endif
                                >
                            </label>

                            @foreach($inputFields as $itemField)
                                @include('sarticles::evo-ui.form.types-config-map-field', ['itemField' => $itemField, 'item' => $item, 'name' => $name, 'index' => $index])
                            @endforeach
                        </div>

                        <div style="display: grid; gap: 8px;">
                            @foreach($toggleFields as $itemField)
                                @include('sarticles::evo-ui.form.types-config-map-field', ['itemField' => $itemField, 'item' => $item, 'name' => $name, 'index' => $index])
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if(!empty($field['hint']))
        <span class="evo-ui-field__hint">{{ __($field['hint']) }}</span>
    @endif

    @if($error)
        <span class="evo-ui-field__error">{{ $error }}</span>
    @endif
</div>
