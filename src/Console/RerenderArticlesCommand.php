<?php namespace Seiger\sArticles\Console;

use Illuminate\Console\Command;
use Seiger\sArticles\Models\sArticleTranslate;
use Seiger\sArticles\Support\BuilderRenderer;

/**
 * Rebuild materialized article HTML from stored builder JSON.
 *
 * The command is intentionally explicit: changing render views does not mutate existing articles
 * until a site owner runs this command. Large ranges are processed in chunks so production sites
 * can safely refresh many translations without loading the full table into memory.
 *
 * @since 2.1.0
 */
class RerenderArticlesCommand extends Command
{
    protected $signature = 'sarticles:rerender
        {--articles= : Article ID, comma-separated IDs, or inclusive ID range such as 123-10000}
        {--lang= : Translation language code to refresh}
        {--chunk=200 : Number of translation rows processed per chunk}
        {--dry-run : Count matching rows without saving generated content}';

    protected $description = 'Re-render stored sArticles translation HTML from builder JSON.';

    /**
     * Execute the re-render command.
     *
     * Rows without valid builder JSON are skipped to avoid overwriting legacy hand-authored HTML.
     * Dry runs use the same chunked traversal as real runs, making estimates safe on large sites.
     *
     * @param BuilderRenderer $renderer Builder renderer shared with manager save flows.
     * @return int Console exit code.
     * @since 2.1.0
     */
    public function handle(BuilderRenderer $renderer): int
    {
        $articleFilter = $this->parseArticlesOption((string) ($this->option('articles') ?? ''));

        if ($articleFilter === false) {
            $this->error('Invalid --articles value. Use one ID, comma-separated IDs, or an inclusive range like 123-10000.');

            return 1;
        }

        $chunk = $this->normalizeChunk((int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $stats = [
            'matched' => 0,
            'rerendered' => 0,
            'changed' => 0,
            'skipped' => 0,
        ];

        $query = sArticleTranslate::query();
        $this->applyArticleFilter($query, $articleFilter);

        if ((string) ($this->option('lang') ?? '') !== '') {
            $query->where('lang', (string) $this->option('lang'));
        }

        $query->chunkById($chunk, function ($translations) use ($renderer, $dryRun, &$stats): void {
            foreach ($translations as $translation) {
                $stats['matched']++;
                $builder = data_is_json((string) ($translation->builder ?? ''), true);

                if (!is_array($builder) || !count($builder)) {
                    $stats['skipped']++;
                    continue;
                }

                $html = $renderer->renderContent($builder);
                $stats['rerendered']++;

                if ((string) ($translation->content ?? '') !== $html) {
                    $stats['changed']++;
                }

                if (!$dryRun) {
                    $translation->content = $html;
                    $translation->save();
                }
            }
        }, 'tid');

        $this->info(($dryRun ? 'Dry run complete.' : 'Re-render complete.'));
        $this->line('Matched: ' . $stats['matched']);
        $this->line('Renderable: ' . $stats['rerendered']);
        $this->line('Changed: ' . $stats['changed']);
        $this->line('Skipped: ' . $stats['skipped']);
        $this->line('Chunk size: ' . $chunk);

        return 0;
    }

    /**
     * Parse the supported article filter formats.
     *
     * The option accepts exactly one contract shape: a single ID, a comma-separated ID list, or one
     * inclusive range. Mixed list/range syntax is rejected so operators can predict the query scope.
     *
     * @param string $value Raw --articles option value.
     * @return array<string, mixed>|false Parsed query filter or false when invalid.
     * @since 2.1.0
     */
    protected function parseArticlesOption(string $value): array|false
    {
        $value = trim($value);

        if ($value === '') {
            return ['type' => 'all'];
        }

        if (preg_match('/^\d+$/', $value)) {
            return ['type' => 'ids', 'ids' => [(int) $value]];
        }

        if (preg_match('/^\d+(?:,\d+)+$/', $value)) {
            $ids = array_values(array_unique(array_map('intval', explode(',', $value))));

            return ['type' => 'ids', 'ids' => $ids];
        }

        if (preg_match('/^(\d+)-(\d+)$/', $value, $matches)) {
            $from = (int) $matches[1];
            $to = (int) $matches[2];

            if ($from < 1 || $to < $from) {
                return false;
            }

            return ['type' => 'range', 'from' => $from, 'to' => $to];
        }

        return false;
    }

    /**
     * Apply the parsed article filter to the translations query.
     *
     * Filtering is performed on `s_article_translates.article`; chunking still uses `tid` because
     * multiple translations can belong to the same article.
     *
     * @param mixed $query Eloquent query builder for translation rows.
     * @param array<string, mixed> $filter Parsed article filter.
     * @return void
     * @since 2.1.0
     */
    protected function applyArticleFilter(mixed $query, array $filter): void
    {
        if (($filter['type'] ?? 'all') === 'ids') {
            $query->whereIn('article', (array) ($filter['ids'] ?? []));
            return;
        }

        if (($filter['type'] ?? 'all') === 'range') {
            $query->whereBetween('article', [(int) $filter['from'], (int) $filter['to']]);
        }
    }

    /**
     * Normalize chunk size into the supported operational range.
     *
     * The default keeps memory usage low, while the upper bound prevents accidental very large
     * chunks from defeating the command's production-safety contract.
     *
     * @param int $chunk Requested chunk size.
     * @return int Safe chunk size between 1 and 1000.
     * @since 2.1.0
     */
    protected function normalizeChunk(int $chunk): int
    {
        return min(1000, max(1, $chunk));
    }
}
