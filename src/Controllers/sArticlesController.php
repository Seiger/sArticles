<?php namespace Seiger\sArticles\Controllers;

use EvolutionCMS\Facades\UrlProcessor;
use EvolutionCMS\Models\SiteContent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Seiger\sArticles\Models\sArticlesAuthor;
use Seiger\sArticles\Models\sArticlesCategory;
use Seiger\sArticles\Models\sArticlesFeature;
use Seiger\sArticles\Models\sArticlesTag;
use Seiger\sArticles\Models\sArticle;

class sArticlesController
{
    public $url;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->url = $this->moduleUrl();
        //Paginator::defaultView('pagination');
    }

    /**
     * Show tab page with sOffer files
     *
     * @return View
     */
    public function index(): View
    {
        return $this->view('articles.shell', [
            'tabs' => ['articles'],
            'get' => 'articles',
            'sArticlesController' => $this,
            'url' => $this->url,
            'linkType' => '',
            'checkType' => request()->type ?? 'article',
        ]);
    }

    /**
     * Update file configurations
     *
     * This method updates the file configurations based on the provided tabs array.
     * It generates a PHP file with the updated settings and saves it in a specific location.
     *
     * @return bool
     */
    public function updateFileConfigs($settings): bool
    {
        // Preparation of deadlines with data
        $string = '<?php return ' . $this->dataToString($settings) . ';';

        // Save the config
        $handle = fopen(EVO_CORE_PATH . 'custom/config/seiger/settings/sArticles.php', "w");
        fwrite($handle, $string);
        fclose($handle);

        return true;
    }

    /**
     * Default language
     *
     * @return string
     */
    public function langDefault(): string
    {
        $language = trim((string) evo()->getConfig('s_lang_default', 'base'));

        return preg_match('/^[A-Za-z0-9_]+$/', $language) === 1 ? $language : 'base';
    }

    /**
     * Languages list
     *
     * @return array
     */
    public function langList(): array
    {
        $lang = evo()->getConfig('s_lang_config', '');
        if (trim($lang)) {
            $lang = collect(explode(',', $lang))
                ->map(fn ($language) => trim((string) $language))
                ->filter(fn ($language) => $language !== '' && preg_match('/^[A-Za-z0-9_]+$/', $language) === 1)
                ->unique()
                ->values()
                ->all();
        } else {
            $lang = ['base'];
        }

        return $lang !== [] ? $lang : ['base'];
    }

    /**
     * Modifying table feature values for translates
     *
     * @return void
     */
    public function setModifyTables($table = ''): void
    {
        $languages = collect($this->langList())
            ->map(fn ($language) => trim((string) $language))
            ->filter(fn ($language) => $language !== '' && $language !== 'base')
            ->filter(fn ($language) => preg_match('/^[A-Za-z0-9_]+$/', $language) === 1)
            ->unique()
            ->values()
            ->all();

        if ($languages === []) {
            return;
        }

        match ($table) {
            'features' => $this->ensureLanguageColumns('s_articles_features', $languages, fn (string $language) => [
                $language => 'string',
            ]),
            'tags' => $this->ensureLanguageColumns('s_articles_tags', $languages, fn (string $language) => [
                $language => 'string',
                $language . '_content' => 'mediumText',
            ]),
            'authors' => $this->ensureLanguageColumns('s_articles_authors', $languages, fn (string $language) => [
                $language . '_name' => 'string',
                $language . '_lastname' => 'string',
                $language . '_office' => 'string',
            ]),
            'categories' => $this->ensureLanguageColumns('s_articles_categories', $languages, fn (string $language) => [
                $language => 'string',
            ]),
            default => null,
        };
    }

    /**
     * Ensure multilingual columns for legacy taxonomy tables using Laravel schema APIs.
     *
     * The old manager flow used raw MySQL schema SQL. The Livewire demo can run on
     * SQLite too, so these updates go through the framework abstraction.
     */
    protected function ensureLanguageColumns(string $tableName, array $languages, callable $columnsForLanguage): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $columns = [];

        foreach ($languages as $language) {
            foreach ((array) $columnsForLanguage($language) as $column => $type) {
                if (!Schema::hasColumn($tableName, $column)) {
                    $columns[$column] = $type;
                }
            }
        }

        if ($columns === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns) {
            foreach ($columns as $column => $type) {
                if ($type === 'mediumText') {
                    $table->mediumText($column)->nullable();
                    continue;
                }

                $table->string($column, 255)->nullable();
            }
        });
    }

    /**
     * Generate articles list aliases
     *
     * @return void
     */
    public function setArticlesListing(): void
    {
        $articlesListing = [];
        $articles = sArticle::select('id', 'alias', 'parent')->wherePublished(1)->get();
        if ($articles) {
            foreach ($articles as $article) {
                $link = str_replace(EVO_SITE_URL, '', $article->link);
                $articlesListing[trim($link, '/')] = $article->id;
            }
        }
        //evo()->clearCache('full');
        Cache::forever('articlesListing', $articlesListing);
    }

    /**
     * Get automatic Tag translation
     *
     * @param $source
     * @param $target
     * @return string
     */
    public function getAutomaticTranslateTag($source, $target): string
    {
        $result = '';
        $langDefault = $this->langDefault();
        $tag = sArticlesTag::find($source);
        if ($tag) {
            $text = $tag[$langDefault];
            $result = $this->googleTranslate($text, $langDefault, $target);
        }
        if (trim($result)) {
            $tag->{$target} = $result;
            $tag->save();
        }
        return $result;
    }

    /**
     * Update translation Tag
     *
     * @param $source
     * @param $target
     * @param $value
     * @return bool
     */
    public function updateTranslateTag($source, $target, $value): bool
    {
        $result = false;
        $tag = sArticlesTag::find($source);
        if ($tag) {
            if ($target == $this->langDefault()) {
                $tag->base = $value;
            }
            $tag->{$target} = $value;
            $tag->update();
            $result = true;
        }
        return $result;
    }

    /**
     * Get Google Translations
     *
     * @param $text
     * @param string $source
     * @param string $target
     * @return string
     */
    public function googleTranslate(string $text, string $source = 'ru', string $target = 'uk'): string
    {
        if ($source == $target) {
            return $text;
        }
        $out = '';
        // Google translate URL
        $url = 'https://translate.google.com/translate_a/single?client=at&dt=t&dt=ld&dt=qca&dt=rm&dt=bd&dj=1&hl=uk-RU&ie=UTF-8&oe=UTF-8&inputm=2&otf=2&iid=1dd3b944-fa62-4b55-b330-74909a99969e';
        $fields_string = 'sl=' . urlencode($source) . '&tl=' . urlencode($target) . '&q=' . urlencode($text);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 3);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');
        $result = curl_exec($ch);
        $result = json_decode($result, TRUE);
        if (isset($result['sentences'])) {
            foreach ($result['sentences'] as $s) {
                $out .= isset($s['trans']) ? $s['trans'] : '';
            }
        } else {
            $out = '';
        }
        if (preg_match('%^\p{Lu}%u', $text) && !preg_match('%^\p{Lu}%u', $out)) { // If the original is capitalized, then we make the translation capitalized
            $out = mb_strtoupper(mb_substr($out, 0, 1)) . mb_substr($out, 1);
        }
        return $out;
    }

    /**
     * Connecting the visual editor to the required fields
     *
     * @param string $ids List of id fields separated by commas
     * @param string $height Window height
     * @param string $editor Which editor to use TinyMCE5, Codemirror
     * @return string
     */
    public function textEditor(string $ids, string $height = '500px', string $editor = ''): string
    {
        $theme = null;
        $elements = [];
        $options = [];
        $ids = explode(",", $ids);

        if (!trim($editor)) {
            $editor = evo()->getConfig('which_editor', 'TinyMCE5');
        }
        if ($editor == 'TinyMCE5') {
            $theme = \sArticles::config('general.tinymce5_theme', evo()->getConfig('sart_tinymce5_theme', 'custom'));
        }

        foreach ($ids as $id) {
            $elements[] = trim($id);
            if ($theme) {
                $options[trim($id)]['theme'] = $theme;
            }
        }

        $editorHtml = evo()->invokeEvent('OnRichTextEditorInit', [
            'editor' => $editor,
            'elements' => $elements,
            'height' => $height,
            'contentType' => 'htmlmixed',
            'options' => $options
        ]);

        if (!is_array($editorHtml)) {
            return '';
        }

        return implode('', $editorHtml);
    }

    /**
     * Module url
     *
     * @return string
     */
    protected function moduleUrl(): string
    {
        return 'index.php?a=112&id=' . md5(__('sArticles::global.articles'));
    }

    /**
     * Price validation
     *
     * @param mixed $price
     * @return float
     */
    public function validatePrice(mixed $price): float
    {
        $validPrice = 0.00;
        $price = str_replace(',', '.', $price);

        if (is_int($price) || is_numeric($price)) {
            $price = floatval($price);
            $validPrice = floatval(number_format($price, 2, '.', ''));
        } elseif (is_float($price)) {
            $validPrice = floatval(number_format($price, 2, '.', ''));
        }

        return $validPrice;
    }

    /**
     * Alias validation
     *
     * @param $data
     * @param string $table
     * @return string
     */
    public function validateAlias($string = '', $id = 0, $key = 'article'): string
    {
        if (trim($string)) {
            $alias = Str::slug(trim($string), '-');
        } else {
            $alias = $id;
        }

        switch ($key) {
            default :
                $aliases = sArticle::where('s_articles.id', '<>', $id)->get('alias')->pluck('alias')->toArray();
                break;
            case "feature" :
                $aliases = sArticlesFeature::where('s_articles_features.fid', '<>', $id)->get('alias')->pluck('alias')->toArray();
                break;
            case "tag" :
                $aliases = sArticlesTag::where('s_articles_tags.tagid', '<>', $id)->get('alias')->pluck('alias')->toArray();
                break;
            case "category" :
                $aliases = sArticlesCategory::where('s_articles_categories.catid', '<>', $id)->get('alias')->pluck('alias')->toArray();
                break;
            case "author" :
                $aliases = sArticlesAuthor::where('s_articles_authors.autid', '<>', $id)->get('alias')->pluck('alias')->toArray();
                break;
        }

        if (in_array($alias, $aliases)) {
            $cnt = 1;
            $tempAlias = $alias;
            while (in_array($tempAlias, $aliases)) {
                $tempAlias = $alias . $cnt;
                $cnt++;
            }
            $alias = $tempAlias;
        }
        return $alias;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'title.required' => 'A title is required',
            'body.required' => 'A message is required',
        ];
    }

    /**
     * Display render
     *
     * @param string $tpl
     * @param array $data
     * @return bool
     */
    public function view(string $tpl, array $data = [])
    {
        return \View::make('sArticles::'.$tpl, $data);
    }

    /**
     * Convert data to a string representation.
     *
     * @param mixed $data The data to convert.
     * @return string The string representation of the data.
     */
    protected function dataToString(mixed $data): string
    {
        return var_export($data, true);
    }
}
