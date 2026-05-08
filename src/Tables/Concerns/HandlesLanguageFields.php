<?php

namespace Seiger\sArticles\Tables\Concerns;

use Seiger\sArticles\Controllers\sArticlesController;

trait HandlesLanguageFields
{
    protected function languageController(): sArticlesController
    {
        if (property_exists($this, 'controller') && $this->controller instanceof sArticlesController) {
            return $this->controller;
        }

        return new sArticlesController();
    }

    protected function languageCodes(): array
    {
        $languages = collect($this->languageController()->langList())
            ->map(fn ($language) => trim((string) $language))
            ->filter(fn ($language) => $language !== '')
            ->filter(fn ($language) => preg_match('/^[A-Za-z0-9_]+$/', $language) === 1)
            ->unique()
            ->values()
            ->all();

        return $languages !== [] ? $languages : ['base'];
    }

    protected function hasLanguageFields(): bool
    {
        $languages = $this->languageCodes();

        return count($languages) > 1 || ($languages[0] ?? 'base') !== 'base';
    }

    protected function languageLabel(string $language): string
    {
        return $language === 'base' ? 'BASE' : mb_strtoupper($language);
    }

    protected function languageTextField(string $language): string
    {
        return $language === 'base' ? 'base' : $language;
    }

    protected function languageContentField(string $language): string
    {
        return $language === 'base' ? 'base_content' : $language . '_content';
    }

    protected function languageAuthorField(string $language, string $field): string
    {
        return ($language === 'base' ? 'base' : $language) . '_' . $field;
    }
}
