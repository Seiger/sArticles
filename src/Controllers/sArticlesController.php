<?php namespace Seiger\sArticles\Controllers;

use EvolutionCMS\Facades\UrlProcessor;
use EvolutionCMS\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Seiger\sArticles\Models\sArticlesAuthor;
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
        return $this->view('index');
    }

    /**
     * Default language
     *
     * @return string
     */
    public function langDefault(): string
    {
        return evo()->getConfig('s_lang_default', 'base');
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
            $lang = explode(',', $lang);
        } else {
            $lang = ['base'];
        }
        return $lang;
    }

    /**
     * Modifying table feature values for translates
     *
     * @return void
     */
    public function setModifyTables($table = ''): void
    {
        $needs = [];
        $columns = [];
        $lang = $this->langList();
        if ($lang != ['base']) {
            switch ($table) {
                case 'features': // Features table
                    $query = evo()->getDatabase()->query("DESCRIBE " . evo()->getDatabase()->getFullTableName('s_articles_features'));
                    if ($query) {
                        $fields = evo()->getDatabase()->makeArray($query);
                        foreach ($fields as $field) {
                            $columns[$field['Field']] = $field;
                        }
                        foreach ($lang as $item) {
                            if (!isset($columns[$item])) {
                                $needs[] = "ADD `{$item}` varchar(255) COMMENT '" . strtoupper($item) . " Value version'";
                            }
                        }
                    }
                    if (count($needs)) {
                        $need = implode(', ', $needs);
                        $query = "ALTER TABLE `".evo()->getDatabase()->getFullTableName('s_articles_features')."` {$need}";
                        evo()->getDatabase()->query($query);
                    }
                    break;
                case 'tags': // Tags table
                    $query = evo()->getDatabase()->query("DESCRIBE " . evo()->getDatabase()->getFullTableName('s_articles_tags'));
                    if ($query) {
                        $fields = evo()->getDatabase()->makeArray($query);
                        foreach ($fields as $field) {
                            $columns[$field['Field']] = $field;
                        }
                        foreach ($lang as $item) {
                            if (!isset($columns[$item])) {
                                $needs[] = "ADD `{$item}` varchar(255) COMMENT '" . strtoupper($item) . " Value version'";
                                $needs[] = "ADD `{$item}_content` mediumtext COMMENT '" . strtoupper($item) . " Text version'";
                            }
                        }
                    }
                    if (count($needs)) {
                        $need = implode(', ', $needs);
                        $query = "ALTER TABLE `".evo()->getDatabase()->getFullTableName('s_articles_tags')."` {$need}";
                        evo()->getDatabase()->query($query);
                    }
                    break;
                case 'authors': // Authors table
                    $query = evo()->getDatabase()->query("DESCRIBE " . evo()->getDatabase()->getFullTableName('s_articles_authors'));
                    if ($query) {
                        $fields = evo()->getDatabase()->makeArray($query);
                        foreach ($fields as $field) {
                            $columns[$field['Field']] = $field;
                        }
                        foreach ($lang as $item) {
                            if (!isset($columns[$item.'_name'])) {
                                $needs[] = "ADD `{$item}_name` varchar(255) COMMENT '" . strtoupper($item) . " Name version'";
                                $needs[] = "ADD `{$item}_lastname` varchar(255) COMMENT '" . strtoupper($item) . " Lastname version'";
                                $needs[] = "ADD `{$item}_office` varchar(255) COMMENT '" . strtoupper($item) . " Office position version'";
                            }
                        }
                    }
                    if (count($needs)) {
                        $need = implode(', ', $needs);
                        $query = "ALTER TABLE `".evo()->getDatabase()->getFullTableName('s_articles_authors')."` {$need}";
                        evo()->getDatabase()->query($query);
                    }
                    break;
            }
        }
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
                $link = str_replace(MODX_SITE_URL, '', $article->link);
                $articlesListing[trim($link, '/')] = $article->id;
            }
        }
        evo()->clearCache('full');
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
    protected function googleTranslate(string $text, string $source = 'ru', string $target = 'uk'): string
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
        if (!trim($editor)) {
            $editor = evo()->getConfig('which_editor', 'TinyMCE5');
        }
        $elements = [];
        $ids = explode(",", $ids);

        foreach ($ids as $id) {
            $elements[] = trim($id);
        }

        return implode("", evo()->invokeEvent('OnRichTextEditorInit', [
            'editor' => $editor,
            'elements' => $elements,
            'height' => $height,
            'contentType' => 'htmlmixed'
        ]));
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
}
