@props([
    'controller',
    'filter',
    'options' => [],
    'labels' => [],
])

@php
    $state = $filter['state'];
    $type = $filter['type'] ?? null;
    $value = $controller->filterValue($filter);
    $active = false;
    $badge = null;
    $activeTitle = null;
    $searchable = ($filter['searchable'] ?? true) !== false;
    $clearable = ($filter['clearable'] ?? true) !== false;
    $autoApply = ($filter['auto_apply'] ?? false) === true;

    if (in_array($type, ['select', 'multi-select'], true)) {
        $selected = collect((array) $value)->filter(fn ($item) => $item !== null && $item !== '' && (string) $item !== '0')->values();
        $active = $selected->isNotEmpty();
        $badge = $type === 'multi-select' ? (string) $selected->count() : '';
        $activeTitle = $active && $labels !== []
            ? __($filter['label']) . ': ' . implode(', ', $labels)
            : null;
    }

    if ($type === 'date-range') {
        $dateValue = (array) $value;
        $from = (string) ($dateValue['from'] ?? '');
        $to = (string) ($dateValue['to'] ?? '');
        $active = $from !== '' || $to !== '';
        $badge = '';

        if ($active) {
            $parts = [];

            if ($from !== '') {
                $parts[] = __('evo::global.date_from') . ': ' . $from;
            }

            if ($to !== '') {
                $parts[] = __('evo::global.date_to') . ': ' . $to;
            }

            $activeTitle = __($filter['label']) . ': ' . implode('; ', $parts);
        }
    }
@endphp

@if($type === 'select')
    @php
        $filterPayload = [
            'state' => $state,
            'selected' => (string) $controller->filterValue($filter),
            'options' => $options,
        ];
    @endphp
    <details
        class="evo-ui-filter-dropdown"
        x-data='EvoUI.selectFilter(@json($filterPayload))'
        @click.outside="reset(); $root.open = false"
    >
        <x-evo::table.filter-summary :filter="$filter" :labels="$labels" :active="$active" :badge="$badge" :active-title="$activeTitle" />

        <div class="evo-ui-filter-menu">
            @if($searchable)
                <x-evo::table.filter-search :filter="$filter" />
            @endif

            <div class="evo-ui-filter-options">
                @foreach($options as $option)
                    <label wire:key="filter-{{ $state }}-{{ md5((string) $option['id']) }}" x-show="visibleOptions().some((option) => String(option.id) === @js((string) $option['id']))">
                        <input type="radio" name="filter-{{ $state }}" value="{{ $option['id'] }}" :checked="selected === @js((string) $option['id'])" @change="selected = @js((string) $option['id'])">
                        <span>{{ $option['name'] }}</span>
                    </label>
                @endforeach
            </div>

            <x-evo::table.filter-actions clear-action="selected = ''" :clearable="$clearable" />
        </div>
    </details>
@elseif($type === 'multi-select')
    @php
        $filterPayload = [
            'state' => $state,
            'selected' => array_map('intval', (array) $controller->filterValue($filter)),
            'options' => $options,
        ];
        $applyAfterToggle = $autoApply ? '; apply()' : '';
    @endphp
    <details
        class="evo-ui-filter-dropdown"
        x-data='EvoUI.multiFilter(@json($filterPayload))'
        @click.outside="reset(); $root.open = false"
    >
        <x-evo::table.filter-summary :filter="$filter" :labels="$labels" :active="$active" :badge="$badge" :active-title="$activeTitle" />

        <div class="evo-ui-filter-menu">
            @if($searchable)
                <x-evo::table.filter-search :filter="$filter" />
            @endif

            <div class="evo-ui-filter-options">
                @foreach($options as $option)
                    <label wire:key="filter-{{ $state }}-{{ $option['id'] }}" x-show="visibleOptions().some((option) => option.id === {{ (int) $option['id'] }})">
                        <input type="checkbox" value="{{ $option['id'] }}" :checked="selected.includes({{ (int) $option['id'] }})" @change="toggle({{ (int) $option['id'] }}){{ $applyAfterToggle }}">
                        <span>{{ $option['name'] }}</span>
                    </label>
                @endforeach
            </div>

            <div class="evo-ui-filter-menu__actions">
                @if($clearable)
                    <button
                        type="button"
                        class="evo-ui-filter-action"
                        :title="allVisibleSelected() ? @js(__('evo::global.filter_clear')) : @js(__('evo::global.filter_all'))"
                        :aria-label="allVisibleSelected() ? @js(__('evo::global.filter_clear')) : @js(__('evo::global.filter_all'))"
                        @click="toggleAllVisible(){{ $applyAfterToggle }}"
                    >
                        <x-evo::icon name="checks" x-show="!allVisibleSelected()" />
                        <x-evo::icon name="x" x-show="allVisibleSelected()" />
                    </button>
                @endif
                @unless($autoApply)
                    <x-evo::table.filter-apply />
                @endunless
            </div>
        </div>
    </details>
@elseif($type === 'segmented')
    <div class="evo-ui-segmented" aria-label="{{ __($filter['label']) }}">
        @foreach($filter['options'] as $option)
            <button
                type="button"
                title="{{ __($option['label']) }}"
                aria-label="{{ __($option['label']) }}"
                @class(['is-active' => $controller->filterValue($filter) === $option['value']])
                wire:click="setFilter('{{ $state }}', '{{ $option['value'] }}')"
            >
                <x-evo::icon :name="$option['icon']" />
            </button>
        @endforeach
    </div>
@elseif($type === 'toggle')
    <button
        type="button"
        title="{{ __($filter['label']) }}"
        aria-label="{{ __($filter['label']) }}"
        @class(['evo-ui-toggle-filter', 'is-active' => $controller->filterValue($filter) === ($filter['selected'] ?? true)])
        wire:click="toggleFilter('{{ $state }}')"
    >
        <x-evo::icon :name="$filter['icon']" />
    </button>
@elseif($type === 'date-range')
    @php
        $filterPayload = [
            'state' => $state,
            'from' => $dateValue['from'] ?? '',
            'to' => $dateValue['to'] ?? '',
        ];
    @endphp
    <details
        class="evo-ui-filter-dropdown"
        x-data='EvoUI.dateRangeFilter(@json($filterPayload))'
        @click.outside="reset(); $root.open = false"
    >
        <x-evo::table.filter-summary :filter="$filter" :labels="$labels" :active="$active" :badge="$badge" :active-title="$activeTitle" />

        <div class="evo-ui-filter-menu evo-ui-filter-menu--date">
            <label>
                <span>@lang('evo::global.date_from')</span>
                <input type="date" class="evo-ui-input" x-model="from">
            </label>
            <label>
                <span>@lang('evo::global.date_to')</span>
                <input type="date" class="evo-ui-input" x-model="to">
            </label>

            <div class="evo-ui-filter-menu__actions">
                <button type="button" class="evo-ui-filter-action" title="@lang('evo::global.filter_clear')" aria-label="@lang('evo::global.filter_clear')" @click="clear">
                    <x-evo::icon name="x" />
                </button>
                <x-evo::table.filter-apply />
            </div>
        </div>
    </details>
@endif
