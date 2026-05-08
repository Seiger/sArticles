<?php

namespace Seiger\sArticles\Livewire;

use Livewire\Component;

class ModulePanel extends Component
{
    public array $rawTabs = [];
    public array $context = [];

    public string $activeTab = 'articles';

    public function mount(array $tabs = [], string $activeTab = 'articles', array $context = []): void
    {
        $this->rawTabs = $tabs;
        $this->context = $context;
        $this->activeTab = $this->normalizeTab($activeTab);
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $this->normalizeTab($tab);
    }

    public function render()
    {
        return view('sArticles::livewire.module-panel', [
            'tabs' => $this->navigationTabs(),
            'preset' => $this->preset(),
            'title' => $this->title(),
            'activeTab' => $this->activeTab,
        ]);
    }

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

    protected function livewireTabs(): array
    {
        return ['articles', 'authors', 'tags', 'categories', 'features', 'comments', 'polls', 'tvparams', 'settings'];
    }
}
