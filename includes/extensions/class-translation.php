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
        add_action( 'wp_ajax_translate', function (){

            if (!current_user_can('edit_posts'))
                wp_send_json_error(['message' => 'Action not allowed'], 403);

            $text = wp_unslash($_POST['q'] ?? '');
            $format = $_POST['format'] ?? 'text';

            if (!is_string($text) || trim($text) === '')
                wp_send_json_error(['message' => 'Invalid text'], 400);

            if (mb_strlen($text, 'UTF-8') > 10000)
                wp_send_json_error(['message' => 'Max char 10000.'], 400);

            if (!in_array($format, ['text', 'html'], true))
                wp_send_json_error(['message' => 'Format invalid'], 400);

            $key = 'wps_translate_rate_'.get_current_user_id();
            $count = (int) get_transient($key);

            if ($count >= 30)
                wp_send_json_error(['message' => 'Flooding'], 429);

            set_transient($key, $count + 1, 60);

            $this->translateText($text, $format);
        } );

        add_action( 'init', function (){

            if( is_user_logged_in() && is_admin() ){

                $user_locale = get_user_locale();
                $user_locale  = explode('_', $user_locale);

                $this->locale = count($user_locale) ? $user_locale[0] : 'en';
            }
            else{

                $language = get_bloginfo('language');
                $language  = explode('-', $language);
                $this->locale = count($language) ? $language[0] : 'en';
            }

            $resource = WPS_YAML_TRANSLATION_FILES.'/wordpress.'.$this->locale.'.yaml';

            if( !is_file($resource) )
                return;

            self::$translations = WPS_Yaml::load('wp_steroid_'.$this->locale, $resource);

        }, false, 2 );

        if( is_admin() ){

            if( $_GET['debug']??'' == 'translations' )
                add_action('shutdown', [$this, 'shutdown']);
        }
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

        if( !isset(self::$translations[$key]) && !in_array($key, self::$missing_translations) )
            self::$missing_translations[] = $key;

        return self::$translations[$key]??$key;
    }

    /**
     * @param $text
     * @param $format
     * @return void
     */
    public function translateText($text, $format='text'){

        $response = $translated_text = false;

        $text = str_replace("'", "`", $text);

        if( defined('WP_GOOGLE_TRANSLATE_KEY') && WP_GOOGLE_TRANSLATE_KEY ){

            $body = [
                'q'=>$text,
                'format'=>$format,
                'target'=>$this->locale
            ];

            $params = [
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'X-goog-api-key' => WP_GOOGLE_TRANSLATE_KEY
                ],
                'body' => json_encode($body)
            ];

            $response = wp_remote_post('https://translation.googleapis.com/language/translate/v2', $params);

            if( !is_wp_error($response) ){

                $body = json_decode(wp_remote_retrieve_body($response), true);
                $translated_text = $body['data']['translations'][0]['translatedText']??false;
            }
        }
        elseif( defined('WP_DEEPL_KEY') && WP_DEEPL_KEY ){

            $body = [
                'text'=>[$text],
                'preserve_formatting'=> true,
                'target_lang'=>$this->locale
            ];

            if( $format == 'html' ){

                $body['tag_handling'] = 'html';
                $body['split_sentences'] = 'nonewlines';
                $body['ignore_tags'] = ["br"];
            }

            $params = [
                'body'=>json_encode($body),
                'headers'=>[
                    'Authorization' => 'DeepL-Auth-Key '.WP_DEEPL_KEY,
                    'Content-Type' => 'application/json'
                ]
            ];

            $response = wp_remote_post('https://api-free.deepl.com/v2/translate', $params);

            if( !is_wp_error($response) ){

                $body = json_decode(wp_remote_retrieve_body($response), true);
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
