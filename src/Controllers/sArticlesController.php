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

/**
 * sArticlesController package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class sArticlesController
{
    public $url;

    /**
     * Initialize this object with the runtime context it needs.
     *
     * For table data providers this stores evo-ui context, current state, and configuration so
     * later row, filter, and modal methods can stay stateless from the caller perspective.
     */
    public function __construct()
    {
        $this->url = $this->moduleUrl();
        //Paginator::defaultView('pagination');
    }

    /**
     * Index for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @return View Value returned for the caller.
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
     * Persist project-level sArticles settings after the manager form is changed.
     *
     * Fresh installations run from the package defaults and do not need a copied config file.
     * The custom settings file is created only when an administrator changes settings, keeping
     * vendor defaults upgradeable while preserving the site's explicit overrides.
     *
     * @param mixed $settings Settings payload collected from the manager settings form.
     * @return bool True when the settings file was written successfully.
     */
    public function updateFileConfigs($settings): bool
    {
        // Preparation of deadlines with data
        $string = '<?php return ' . $this->dataToString($settings) . ';';
        $path = EVO_CORE_PATH . 'custom/config/seiger/settings/sArticles.php';
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return false;
        }

        // Save the config
        return file_put_contents($path, $string, LOCK_EX) !== false;
    }

    /**
     * Lang default for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @return string String value ready for manager or frontend use.
     */
    public function langDefault(): string
    {
        $language = trim((string) evo()->getConfig('s_lang_default', 'base'));

        return preg_match('/^[A-Za-z0-9_]+$/', $language) === 1 ? $language : 'base';
    }

    /**
     * Lang list for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @return array<string, mixed> Structured array payload consumed by the manager UI or package runtime.
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
     * Set modify tables for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @param mixed $table Table value.
     * @return void No value is returned.
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
     * Ensure language columns for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @param string $tableName Table Name value.
     * @param array<string, mixed> $languages Language codes included in the current multilingual flow.
     * @param callable $columnsForLanguage Columns For Language value.
     * @return void No value is returned.
     * @since 2.0.0
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
     * Set articles listing for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @return void No value is returned.
     * @since 2.0.0
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
     * Get automatic translate tag for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @param mixed $source Source value.
     * @param mixed $target Target value.
     * @return string String value ready for manager or frontend use.
     * @since 2.0.0
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
     * Update translate tag for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @param mixed $source Source value.
     * @param mixed $target Target value.
     * @param mixed $value Submitted value to validate.
     * @return bool True when the condition is met, false otherwise.
     * @since 2.0.0
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
     * Google translate for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @param string $text Text value.
     * @param string $source Source value.
     * @param string $target Target value.
     * @return string String value ready for manager or frontend use.
     * @since 2.0.0
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
     * Text editor for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @param string $ids Ids value.
     * @param string $height Height value.
     * @param string $editor Editor value.
     * @return string String value ready for manager or frontend use.
     * @since 2.0.0
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
     * Build the Evolution manager module URL.
     *
     * The URL is reused by table row actions, modal actions, and links back into the package
     * manager module.
     *
     * @return string String value ready for manager or frontend use.
     * @since 2.0.0
     */
    protected function moduleUrl(): string
    {
        return 'index.php?a=112&id=' . md5(__('sArticles::global.articles'));
    }

    /**
     * Validate price for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @param mixed $price Price value.
     * @return float Value returned for the caller.
     * @since 2.0.0
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
     * Validate alias for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @param mixed $string String value.
     * @param mixed $id Internal record identifier.
     * @param mixed $key Configuration or request key to read.
     * @return string String value ready for manager or frontend use.
     * @since 2.0.0
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
     * Messages for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @since 2.0.0
     */
    public function messages()
    {
        return [
            'title.required' => 'A title is required',
            'body.required' => 'A message is required',
        ];
    }

    /**
     * View for the records manager flow.
     *
     * Manager blade files are resolved through the lowercase Laravel package view namespace.
     * Language lines intentionally keep the historical `sArticles::` namespace, but views now use
     * `sarticles::` so Laravel vendor overrides match `views/vendor/sarticles`.
     *
     * @param string $tpl Tpl value.
     * @param array<string, mixed> $data Data value.
     * @return mixed Renderable manager view instance.
     * @since 2.0.0
     */
    public function view(string $tpl, array $data = [])
    {
        return \View::make('sarticles::'.$tpl, $data);
    }

    /**
     * Data to string for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     *
     * @param mixed $data Data value.
     * @return string String value ready for manager or frontend use.
     * @since 2.0.0
     */
    protected function dataToString(mixed $data): string
    {
        return var_export($data, true);
    }
}
