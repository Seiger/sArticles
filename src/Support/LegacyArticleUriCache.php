<?php namespace Seiger\sArticles\Support;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Support\Facades\Cache;
use Seiger\sArticles\Models\sArticle;

/**
 * Cache legacy URI resolution without mixing translations, site trees or listing precedence.
 * Raw attributes keep model accessors/casts intact and avoid serializing appended dates.
 *
 * @since 2.1.0
 */
final class LegacyArticleUriCache
{
    /** @var \WeakMap<\Illuminate\Database\Connection, string>|null Isolate ephemeral SQLite databases. */
    private static ?\WeakMap $memoryDatabases = null;

    /**
     * Scope generations to the default database used by the article model.
     *
     * @param \Illuminate\Database\Connection $connection Resolved default connection.
     * @return string Opaque namespace independent of the current URI.
     * @since 2.1.0
     */
    private static function key($connection): string
    {
        $database = (string)$connection->getDatabaseName();
        $memoryScope = null;
        if ($connection->getDriverName() === 'sqlite'
            && ($database === '' || str_contains($database, ':memory:') || str_contains($database, 'mode=memory'))) {
            self::$memoryDatabases ??= new \WeakMap();
            $memoryScope = self::$memoryDatabases[$connection] ??= bin2hex(random_bytes(16));
        }
        return 'sArticles.legacy-uri.v1.' . hash('sha256', serialize([
            $connection->getName(), $connection->getConfig('driver'), $connection->getConfig('host'),
            $connection->getConfig('port'), $connection->getConfig('unix_socket'),
            $connection->getDatabaseName(), $connection->getTablePrefix(), $memoryScope,
        ]));
    }

    /**
     * Observe model, builder and raw SQL writes affecting article paths and site membership.
     * Rotate again after the outer transaction ends to discard concurrent pre-commit reads.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events Application event dispatcher.
     * @return void
     * @since 2.1.0
     */
    public static function listen($events): void
    {
        $dirty = [];
        $events->listen(QueryExecuted::class, static function ($event) use (&$dirty): void {
            if (!preg_match('~\b(?:insert|update|delete|replace|truncate|create|alter|drop|rename)\b~i', $event->sql)) return;
            $matched = false;
            foreach (['s_articles', 's_article_translates', 'site_content', 's_multisites', 'system_settings'] as $table) {
                $table = preg_quote($event->connection->getTablePrefix() . $table, '~');
                if (preg_match('~(?<![a-z0-9_])' . $table . '(?![a-z0-9_])~i', $event->sql)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) return;
            $key = self::key($event->connection);
            Cache::forever($key, bin2hex(random_bytes(16)));
            if ($event->connection->transactionLevel() > 0) {
                $dirty[spl_object_id($event->connection)][$key] = true;
            }
        });
        foreach ([TransactionCommitted::class, TransactionRolledBack::class] as $event) {
            $events->listen($event, static function ($event) use (&$dirty): void {
                $id = spl_object_id($event->connection);
                if ($event->connection->transactionLevel() !== 0) return;
                foreach (array_keys($dirty[$id] ?? []) as $key) Cache::forever($key, bin2hex(random_bytes(16)));
                unset($dirty[$id]);
            });
        }
    }

    /**
     * Reuse a model or explicit miss only within an equivalent URI and routing context.
     * Transactions, custom scopes and retrieved observers bypass shared entries.
     * Five-minute TTL bounds external SQL changes.
     *
     * @param array<string, mixed> $segments Unmodified request URI segments.
     * @param array<string, mixed> $listing Current alias map; its precedence is part of identity.
     * @param callable(): (sArticle|null) $resolve Original uncached resolver.
     * @return sArticle|null Fresh model instance or cached absence of a match.
     * @since 2.1.0
     */
    public static function remember(array $segments, array $listing, callable $resolve): ?sArticle
    {
        $model = new sArticle();
        $connection = $model->getConnection();
        $events = $model->getEventDispatcher();
        $scopes = $model->getGlobalScopes();
        $translationScope = $scopes['translate'] ?? null;
        $nativeTranslation = $translationScope instanceof \Closure
            && (new \ReflectionFunction($translationScope))->getFileName()
                === (new \ReflectionClass($model))->getFileName();
        // Additional or replaced scopes and observers can depend on arbitrary request state.
        if ($connection->transactionLevel() > 0
            || !$nativeTranslation || array_diff(array_keys($scopes), ['translate']) !== []
            || ($events && $events->hasListeners('eloquent.retrieved: ' . sArticle::class))) {
            return $resolve();
        }
        $context = [$segments, $listing, app()->getLocale(), defined('EVO_SITE_URL') ? EVO_SITE_URL : null];
        foreach (['lang', 'check_sMultisite', 'site_key', 'site_root', 'site_start', 'site_url',
            'friendly_urls', 'use_alias_path', 'friendly_url_suffix', 'friendly_url_prefix'] as $name) {
            $context[$name] = evo()->getConfig($name);
        }
        $context['resources'] = Cache::get('sMultisite-' . evo()->getConfig('site_key', 'default') . '-resources');
        $key = self::key($connection);
        $generation = Cache::get($key);
        if (!is_string($generation)) {
            Cache::add($key, bin2hex(random_bytes(16)), 86400);
            $generation = Cache::get($key);
            if (!is_string($generation)) return $resolve();
        }
        $entryKey = $key . '.' . $generation . '.' . hash('sha256', serialize($context));
        $entry = Cache::get($entryKey);
        if (is_array($entry) && array_key_exists('attributes', $entry)) {
            return $entry['attributes'] === null ? null
                : (new sArticle())->newFromBuilder($entry['attributes'], $connection->getName());
        }
        $article = $resolve();
        Cache::put($entryKey, ['attributes' => $article?->getRawOriginal()], 300);
        return $article;
    }
}
