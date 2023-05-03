<?php

/**
 * Class
 */
class WPS_Translation {

    private static $translations=[];
    private static $missing_translations=[];

	public $locale;

    /**
     * Constructor
     */
    public function __construct()
    {
        if( !is_admin() && !wp_is_json_request() )
            return;

	    add_action( 'wp_ajax_translate', function (){
			$this->translateText($_POST['q']??'', $_POST['wysiwyg']??false);
	    } );

        $language = get_bloginfo('language');
        $language  = explode('-', $language);
        $this->locale = count($language) ? $language[0] : 'en';
        $resource = WPS_YAML_TRANSLATION_FILES.'/wordpress.'.$this->locale.'.yaml';

        if( !file_exists($resource) )
            return;

        try{

            self::$translations = Spyc::YAMLLoad($resource);
        }
        catch (Exception $e){

            wp_die(basename($resource).' loading error: '.$e->getMessage());
        }

        if( is_admin() && ($_GET['debug']??'') == 'translations' )
            add_action('shutdown', [$this, 'shutdown']);
    }

	/**
	 * @return void
	 */
	public function shutdown(){

        if( !empty(self::$missing_translations) && WP_DEBUG && $_SERVER['REQUEST_METHOD'] === 'GET' )
            echo "<!--\nMissing translations:\n\n".implode("\n", array_unique(self::$missing_translations))."\n-->";
    }

	/**
	 * @param $key
	 * @return mixed
	 */
	public static function translate($key){

        if( !isset(self::$translations[$key]) )
            self::$missing_translations[] = $key;

        return self::$translations[$key]??$key;
    }

	/**
	 * @param $text
	 * @param $wysiwyg
	 * @return void
	 */
	public function translateText($text, $wysiwyg=false){

	    $response = $translated_text = false;

	    if( defined('GOOGLE_TRANSLATE_KEY') && GOOGLE_TRANSLATE_KEY ){

			$response = wp_remote_post('https://translation.googleapis.com/language/translate/v2?key='.GOOGLE_TRANSLATE_KEY, [
				'body'=>[
					'q'=>$text, 'format'=>$wysiwyg?'html':'text', 'target'=>$this->locale
				]
			]);

		    if( !is_wp_error($response) ){

			    $body = json_decode($response['body'], true);
			    $translated_text = $body['data']['translations'][0]['translatedText']??false;
		    }
	    }
	    elseif( defined('DEEPL_KEY') && DEEPL_KEY ){

		    $response = wp_remote_post('https://api-free.deepl.com/v2/translate',
			    [
				    'body'=>[
					    'text'=>$text,
					    'preserve_formatting'=> true,
					    'non_splitting_tags'=> true,
					    'target_lang'=>$this->locale
				    ],
				    'headers'=>[
					    'Authorization'=> 'DeepL-Auth-Key '.DEEPL_KEY
				    ]
			    ]
		    );
			if( !is_wp_error($response) ){

				$body = json_decode($response['body'], true);
				$translated_text = $body['translations'][0]['text']??false;
			}
	    }

		if( !$response )
			wp_send_json_error(['message'=>'response is empty']);

		if( is_wp_error($response) )
			wp_send_json_error(['message'=>$response->get_error_message()]);

	    wp_send_json(['text'=>$translated_text]);
    }
}

if( !function_exists('__t') ){

    function __t($key){

        return WPS_Translation::translate($key);
    }
}
