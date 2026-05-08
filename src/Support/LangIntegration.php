<?php

namespace Seiger\sArticles\Support;

use Illuminate\Support\Str;
use Seiger\sArticles\Controllers\sArticlesController;

class LangIntegration
{
    public function enabled(): bool
    {
        return (bool) evo()->getConfig('check_sLang', false)
            && class_exists('\Seiger\sLang\Facades\sLang');
    }

    public function default(): string
    {
        if ($this->enabled()) {
            $facadeClass = '\Seiger\sLang\Facades\sLang';
            $language = trim((string) $facadeClass::langDefault());

            return $language !== '' ? $language : 'uk';
        }

        $language = trim((string) (new sArticlesController())->langDefault());

        return $language !== '' ? $language : 'base';
    }

    public function languages(): array
    {
        if (!$this->enabled()) {
            return [$this->default()];
        }

        $facadeClass = '\Seiger\sLang\Facades\sLang';
        $default = $this->default();
        $languages = collect((array) $facadeClass::langConfig())
            ->map(fn ($language) => trim((string) $language))
            ->filter(fn ($language) => $language !== '')
            ->unique()
            ->values();

        if (!$languages->contains($default)) {
            $languages->prepend($default);
        }

        return $languages
            ->sortBy(fn ($language) => $language === $default ? 0 : 1)
            ->values()
            ->all() ?: [$default];
    }

    public function tabName(string $language): string
    {
        return 'lang_' . Str::slug($language, '_');
    }

    public function tabLabel(string $language): string
    {
        $info = $this->languageInfo($language);
        $short = trim((string) ($info['short'] ?? ''));

        return $short !== '' ? $short : strtoupper($language);
    }

    public function languageTitle(string $language): string
    {
        $info = $this->languageInfo($language);
        $name = trim((string) ($info['name'] ?? ''));

        return $name !== '' ? $name : strtoupper($language);
    }

    protected function languageInfo(string $language): array
    {
        if (!$this->enabled()) {
            return [];
        }

        $facadeClass = '\Seiger\sLang\Facades\sLang';
        $list = (array) $facadeClass::langList();

        return (array) ($list[$language] ?? []);
    }
}
