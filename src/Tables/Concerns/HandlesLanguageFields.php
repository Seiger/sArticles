<?php namespace Seiger\sArticles\Tables\Concerns;

use Seiger\sArticles\Controllers\sArticlesController;

/**
 * HandlesLanguageFields package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
trait HandlesLanguageFields
{
    /**
     * Language controller for HandlesLanguageFields.
     *
     * This method keeps the language controller responsibility inside HandlesLanguageFields, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return sArticlesController Resolved value used by the package workflow.
     * @since 2.0.0
     */
    protected function languageController(): sArticlesController
    {
        if (property_exists($this, 'controller') && $this->controller instanceof sArticlesController) {
            return $this->controller;
        }

        return new sArticlesController();
    }

    /**
     * Language codes for HandlesLanguageFields.
     *
     * This method keeps the language codes responsibility inside HandlesLanguageFields, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return array<string, mixed> Normalized payload for the related manager or package workflow.
     * @since 2.0.0
     */
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

    /**
     * Has language fields for HandlesLanguageFields.
     *
     * This method keeps the has language fields responsibility inside HandlesLanguageFields, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return bool True when the package condition is met, false otherwise.
     * @since 2.0.0
     */
    protected function hasLanguageFields(): bool
    {
        $languages = $this->languageCodes();

        return count($languages) > 1 || ($languages[0] ?? 'base') !== 'base';
    }

    /**
     * Language label for HandlesLanguageFields.
     *
     * This method keeps the language label responsibility inside HandlesLanguageFields, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function languageLabel(string $language): string
    {
        return $language === 'base' ? 'BASE' : mb_strtoupper($language);
    }

    /**
     * Language text field for HandlesLanguageFields.
     *
     * This method keeps the language text field responsibility inside HandlesLanguageFields, so
     * callers can rely on a stable package boundary while the manager UI, frontend runtime, or
     * legacy storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function languageTextField(string $language): string
    {
        return $language === 'base' ? 'base' : $language;
    }

    /**
     * Language content field for HandlesLanguageFields.
     *
     * This method keeps the language content field responsibility inside HandlesLanguageFields,
     * so callers can rely on a stable package boundary while the manager UI, frontend runtime,
     * or legacy storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function languageContentField(string $language): string
    {
        return $language === 'base' ? 'base_content' : $language . '_content';
    }

    /**
     * Language author field for HandlesLanguageFields.
     *
     * This method keeps the language author field responsibility inside HandlesLanguageFields,
     * so callers can rely on a stable package boundary while the manager UI, frontend runtime,
     * or legacy storage details evolve.
     *
     * @return string Resolved text value for manager display, storage, or frontend output.
     * @since 2.0.0
     */
    protected function languageAuthorField(string $language, string $field): string
    {
        return ($language === 'base' ? 'base' : $language) . '_' . $field;
    }
}
