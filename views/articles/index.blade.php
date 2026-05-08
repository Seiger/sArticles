@php
    $manager = app(\EvoUI\Support\ManagerContext::class);
    $theme = $manager->theme();
    $themeMode = $manager->themeMode($theme);
    $themeClasses = $manager->themeClasses($theme);
@endphp

<div
    class="evo-ui evo-ui-module-page {{ $themeClasses }}"
    data-evo-ui-root
    data-theme="{{ $theme }}"
    data-theme-mode="{{ $themeMode }}"
>
    <div class="notifier"><div class="notifier-txt"></div></div>

    <livewire:sarticles.module-panel
        :tabs="$tabs"
        :active-tab="request()->get('get', 'articles')"
        :context="['moduleUrl' => $moduleUrl, 'type' => $type]"
    />
</div>
