<?php

use Dallgoot\Yaml;

/**
 * Class
 */
class WPS_Translation {

    private static $translations=[];
    private static $missing_translations=[];

    /**
     * Constructor
     */
    public function __construct()
    {
        if( !is_admin() )
            return;

        $language = get_bloginfo('language');
        $language  = explode('-', $language);
        $locale = count($language) ? $language[0] : 'en';
        $resource = WPS_YAML_TRANSLATION_FILES.'/wordpress.'.$locale.'.yaml';

        if( !file_exists($resource) )
            return;

        try{

            $translations = Yaml::parseFile($resource);
            self::$translations = json_decode(json_encode($translations->jsonSerialize()),true);
        }
        catch (Exception $e){

            wp_die(basename($resource).' loading error: '.$e->getMessage());
        }

        add_action('shutdown', [$this, 'shutdown']);
    }

    public function shutdown(){

        if( !empty(self::$missing_translations) )
            echo "<!--\nMissing translations:\n\n".implode("\n", array_unique(self::$missing_translations))."\n-->";
    }

    public static function translate($key){

        if( !isset(self::$translations[$key]) )
            self::$missing_translations[] = $key;

        return self::$translations[$key]??$key;
    }
}

if( !function_exists('__t') ){

    function __t($key){

        return WPS_Translation::translate($key);
    }
}
