<?php namespace Seiger\sArticles\Livewire;

use Livewire\Component;

/**
 * ModulePanel package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class ModulePanel extends Component
{
    public array $rawTabs = [];
    public array $context = [];

    public string $activeTab = 'articles';

    /**
     * Mount for ModulePanel.
     *
     * This method keeps the mount responsibility inside ModulePanel, so callers can rely on a
     * stable package boundary while the manager UI, frontend runtime, or legacy storage details
     * evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function mount(array $tabs = [], string $activeTab = 'articles', array $context = []): void
    {
        $this->rawTabs = $tabs;
        $this->context = $context;
        $this->activeTab = $this->normalizeTab($activeTab);
    }

    /**
     * Switch tab for ModulePanel.
     *
     * This method keeps the switch tab responsibility inside ModulePanel, so callers can rely on
     * a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return void No value is returned; the method updates package state, storage, or output.
     * @since 2.0.0
     */
    public function switchTab(string $tab): void
    {
        $this->activeTab = $this->normalizeTab($tab);
    }

    /**
     * Render for ModulePanel.
     *
     * This method keeps the render responsibility inside ModulePanel, so callers can rely on a
     * stable package boundary while the manager UI, frontend runtime, or legacy storage details
     * evolve.
     *
     * @return mixed Resolved value used by the package workflow.
     * @since 2.0.0
     */
    public function render()
    {
        return view('sArticles::livewire.module-panel', [
            'tabs' => $this->navigationTabs(),
            'preset' => $this->preset(),
            'title' => $this->title(),
            'activeTab' => $this->activeTab,
        ]);
    }

    /**
     * Normalize tab for package-safe usage.
     *
     * This method keeps the normalize tab responsibility inside ModulePanel, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function normalizeTab(string $tab): string
    {
        $tab = trim($tab);
        $allowed = collect($this->rawTabs)
            ->pluck('key')
            ->filter(fn ($key) => in_array($key, $this->livewireTabs(), true))
            ->values()
            ->all();

        return in_array($tab, $allowed, true) ? $tab : ($allowed[0] ?? 'articles');
    }

    /**
     * Navigation tabs for ModulePanel.
     *
     * This method keeps the navigation tabs responsibility inside ModulePanel, so callers can
     * rely on a stable package boundary while the manager UI, frontend runtime, or legacy
     * storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function navigationTabs(): array
    {
        return collect($this->rawTabs)
            ->map(function (array $tab) {
                $key = (string) ($tab['key'] ?? '');
                $tab['active'] = $key === $this->activeTab;

                if (in_array($key, $this->livewireTabs(), true)) {
                    $tab['type'] = 'wire';
                    $tab['method'] = 'switchTab';
                    $tab['argument'] = $key;
                    unset($tab['href'], $tab['data']);
                }

                return $tab;
            })
            ->values()
            ->all();
    }

    /**
     * Preset for ModulePanel.
     *
     * This method keeps the preset responsibility inside ModulePanel, so callers can rely on a
     * stable package boundary while the manager UI, frontend runtime, or legacy storage details
     * evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function preset(): string
    {
        return match ($this->activeTab) {
            'authors' => 'sarticles.authors',
            'tags' => 'sarticles.tags',
            'categories' => 'sarticles.categories',
            'features' => 'sarticles.features',
            'comments' => 'sarticles.comments',
            'polls' => 'sarticles.polls',
            'tvparams' => 'sarticles.tvparams',
            'settings' => 'sarticles.settings',
            default => 'sarticles.articles',
        };
    }

    /**
     * Title for ModulePanel.
     *
     * This method keeps the title responsibility inside ModulePanel, so callers can rely on a
     * stable package boundary while the manager UI, frontend runtime, or legacy storage details
     * evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function title(): string
    {
        return match ($this->activeTab) {
            'authors' => __('sArticles::global.authors'),
            'tags' => __('sArticles::global.tags'),
            'categories' => __('sArticles::global.categories'),
            'features' => __('sArticles::global.features'),
            'comments' => __('sArticles::global.comments'),
            'polls' => __('sArticles::global.polls'),
            'tvparams' => __('sArticles::global.tvs'),
            'settings' => __('sArticles::global.settings'),
            default => __('sArticles::global.articles'),
        };
    }

    /**
     * Livewire tabs for ModulePanel.
     *
     * This method keeps the livewire tabs responsibility inside ModulePanel, so callers can rely
     * on a stable package boundary while the manager UI, frontend runtime, or legacy storage
     * details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
    protected function livewireTabs(): array
    {
        return ['articles', 'authors', 'tags', 'categories', 'features', 'comments', 'polls', 'tvparams', 'settings'];
    }
}
