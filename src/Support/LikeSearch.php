<?php namespace Seiger\sArticles\Support;

/**
 * Build portable SQL fragments for manager search filters.
 *
 * Evolution installations can run on MySQL, PostgreSQL, or SQLite depending on the host. The
 * package therefore avoids backslash as the LIKE escape character because PostgreSQL treats
 * `ESCAPE '\\'` as a two-character escape string on many modern configurations.
 *
 * The helper uses `!` as an explicit escape marker and mirrors that choice when preparing user
 * input. This keeps literal `%`, `_`, and `!` characters searchable without leaking SQL dialect
 * details into every table data provider.
 *
 * @since 2.1.0
 */
class LikeSearch
{
    /**
     * Build a LOWER(column) LIKE placeholder with a portable escape marker.
     *
     * The returned fragment is intended for `whereRaw()` and `selectRaw()` expressions where the
     * value is still bound separately. Keeping the escape marker in one place prevents PostgreSQL
     * from receiving the MySQL-oriented `ESCAPE '\\'` form that breaks Livewire table updates.
     *
     * @param mixed $query Query builder instance exposing grammar wrapping.
     * @param string $field Column or qualified column name to compare.
     * @return string SQL fragment with one placeholder for the LIKE needle.
     * @since 2.1.0
     */
    public static function lowerExpression($query, string $field): string
    {
        return self::expression($query, $field);
    }

    /**
     * Build a LIKE placeholder for a wrapped field.
     *
     * SQLite is sometimes used for local demos and does not lowercase Ukrainian text with the
     * built-in `LOWER()` function. Callers can disable lowercasing while still keeping the same
     * portable escape marker and bound-value contract.
     *
     * @param mixed $query Query builder instance exposing grammar wrapping.
     * @param string $field Column or qualified column name to compare.
     * @param bool $lowercase Whether to wrap the field with SQL LOWER().
     * @return string SQL fragment with one placeholder for the LIKE needle.
     * @since 2.1.0
     */
    public static function expression($query, string $field, bool $lowercase = true): string
    {
        return self::expressionForWrapped($query->getGrammar()->wrap($field), $lowercase);
    }

    /**
     * Build a LIKE placeholder when only the connection grammar is available.
     *
     * Some helper lookups use the DB facade directly instead of an Eloquent builder. This method
     * keeps those lookups on the same escaping contract as the main table queries.
     *
     * @param mixed $grammar Query grammar exposing `wrap()`.
     * @param string $field Column or qualified column name to compare.
     * @param bool $lowercase Whether to wrap the field with SQL LOWER().
     * @return string SQL fragment with one placeholder for the LIKE needle.
     * @since 2.1.0
     */
    public static function expressionForGrammar($grammar, string $field, bool $lowercase = true): string
    {
        return self::expressionForWrapped($grammar->wrap($field), $lowercase);
    }

    /**
     * Convert a user-entered token into a bound LIKE value.
     *
     * The wildcard characters are escaped for the package-wide `ESCAPE '!'` SQL fragment, so
     * searches for literal percent or underscore characters behave consistently across supported
     * database drivers.
     *
     * @param string $value User-entered search phrase or token.
     * @return string LIKE-ready bound value wrapped in `%` wildcards.
     * @since 2.1.0
     */
    public static function needle(string $value): string
    {
        return '%' . strtr($value, [
            '!' => '!!',
            '%' => '!%',
            '_' => '!_',
        ]) . '%';
    }

    /**
     * Build the final SQL fragment for an already wrapped column.
     *
     * The escape marker is intentionally a plain single-character SQL string. That form is accepted
     * by PostgreSQL, MySQL, and SQLite, avoiding driver-specific backslash literal rules.
     *
     * @param string $wrappedField Grammar-wrapped column reference.
     * @param bool $lowercase Whether to wrap the field with SQL LOWER().
     * @return string SQL fragment with one placeholder for the LIKE needle.
     * @since 2.1.0
     */
    protected static function expressionForWrapped(string $wrappedField, bool $lowercase): string
    {
        $sql = $lowercase ? 'LOWER(' . $wrappedField . ') LIKE ?' : $wrappedField . ' LIKE ?';

        return $sql . " ESCAPE '!'";
    }
}
