<?php
// Isolated resolver regression checks: no CMS bootstrap, database or network writes.
namespace Illuminate\Support {
    class Arr { public static function last(array $items) { return end($items); } }
}
namespace Illuminate\Support\Facades {
    class Cache {
        public static array $listing = [];
        public static function get($key) { return self::$listing; }
    }
}
namespace Seiger\sArticles\Models {
    class sArticle {
        public int $id = 0;
        public string $alias = '';
        public string $link = '';
        public static array $records = [];
        public static function where($field, $value) {
            return new class($field, $value) {
                public function __construct(private string $field, private mixed $value) {}
                public function get(): array {
                    $field = substr($this->field, strrpos($this->field, '.') + 1);
                    return array_values(array_filter(sArticle::$records,
                        fn ($record) => $record->$field == $this->value));
                }
                public function first() { return $this->get()[0] ?? null; }
            };
        }
    }
}
namespace Seiger\sArticles\Controllers {
    class sArticlesController {
        public function setArticlesListing(): void {
            \Illuminate\Support\Facades\Cache::$listing = [];
        }
    }
}
namespace {
    function evo() {
        return new class {
            public function getConfig($key, $default = null) {
                return $key === 'lang' ? $GLOBALS['testLanguage'] : $default;
            }
        };
    }
    require $argv[1] ?? dirname(__DIR__) . '/src/sArticles.php';
    $resolver = (new ReflectionClass(\Seiger\sArticles\sArticles::class))->newInstanceWithoutConstructor();
    $article = new \Seiger\sArticles\Models\sArticle();
    $article->id = 208;
    $article->alias = 'example-article';
    $path = 'wiki/' . $article->alias;
    $cases = [
        ['cache hit', 'uk', 'https://example.test/' . $path, $path, true, true],
        ['cache miss absolute URL', 'uk', 'https://example.test/' . $path, $path, false, true],
        ['relative URL', 'uk', '/' . $path, $path, false, true],
        ['trailing slash', 'uk', 'https://example.test/' . $path . '/', $path, false, true],
        ['URL query and fragment', 'uk', 'https://example.test/' . $path . '?x=1#top', $path, false, true],
        ['localized URL', 'ru', 'https://example.test/ru/' . $path, 'ru/' . $path, false, true],
        ['stripped language prefix', 'ru', 'https://example.test/ru/' . $path, $path, false, true],
        ['wrong parent rejected', 'uk', 'https://example.test/' . $path, 'news/' . $article->alias, false, false],
        ['wrong language rejected', 'uk', 'https://example.test/ru/' . $path, $path, false, false],
        ['unknown alias rejected', 'uk', 'https://example.test/' . $path, 'wiki/missing', false, false],
        ['empty path rejected', 'uk', 'https://example.test/' . $path, '', false, false],
    ];
    $failures = 0;
    foreach ($cases as [$name, $language, $link, $requestPath, $cacheHit, $expected]) {
        $GLOBALS['testLanguage'] = $language;
        $article->link = $link;
        \Seiger\sArticles\Models\sArticle::$records = [$article];
        \Illuminate\Support\Facades\Cache::$listing = $cacheHit ? [$path => 208] : [];
        $actual = $resolver->resolveArticleByUri($requestPath === '' ? [] : explode('/', $requestPath));
        $passed = (($actual->id ?? 0) === 208) === $expected;
        echo ($passed ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
        $failures += !$passed;
    }
    echo count($cases) . ' checks, ' . $failures . ' failures' . PHP_EOL;
    exit($failures ? 1 : 0);
}
