<?php
/**
 * Plugin Name: WordPress on Steroids
 * Description: Configure WordPress using yml and add amazing features
 * Version: 1.0.1
 * Author: Akhela
 * Author URI: http://www.akhela.fr
 */

use Dallgoot\Yaml;
use Dflydev\DotAccessData\Data;


if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

class WPS{

    function __construct() {
        // Do nothing.
    }

    private function loadAll($folder, $instantiate=false){

        $folder = __DIR__.'/includes/'.$folder;
        $files = scandir($folder);

        foreach($files as $file){

            if( !in_array($file, ['.','..']) )
            {
                $classname = 'WPS_'.str_replace(' ', '_', ucwords(str_replace('-', ' ', str_replace('class-', '', str_replace('.php', '', $file)))));
                $this->load($folder.'/'.$file, $classname, $instantiate);
            }
        }
    }

    /**
     * @param $file
     * @param $classname
     * @param bool $instantiate
     */
    private function load($file, $classname, $instantiate=false){

        if( !file_exists($file) )
            return;

        include_once $file;

        if( $instantiate )
            new $classname();
    }

    private function importConfig($resource){

        /**
         * Wordpress configuration file
         */

        global $_config;

        try{

            $config = Yaml::parseFile($resource);
            $config = json_decode(json_encode($config->jsonSerialize()),true);
        }
        catch (Exception $e){

            wp_die(basename($resource).' loading error: '.$e->getMessage());
        }

        $_config = new Data($config['wordpress']??$config);


        /**
         * Define constants
         */
        foreach ($_config->get('define', []) as $constant=>$value){

            if( !defined(strtoupper($constant)) ){

                if( substr($value, 0, 4) === 'env(' ){

                    $value = substr($value, 4, strlen($value)-6);
                    define( strtoupper($constant), $_ENV[$value]??false);
                }
                else{

                    define( strtoupper($constant), $value);
                }
            }
        }

        if( !defined('HEADLESS') )
            define('HEADLESS', $_config->get('headless', false) );

        if( !defined('URL_MAPPING') )
            define('URL_MAPPING', $_config->get('headless.mapping', false) );
    }

    /**
     * initialize
     *
     * Sets up the Meta Steroids
     *
     * @date    28/09/13
     * @since   5.0.0
     *
     * @param   void
     * @return  void
     */
    function initialize() {

        if( !defined('WPS_YAML_FILE') )
            die('WPS_YAML_FILE is not defined');

        if( !defined('WPS_YAML_TRANSLATION_FILES') )
            define('WPS_YAML_TRANSLATION_FILES', WP_LANG_DIR);

        define('WPS_PATH', __DIR__);
        define('WPS_PLUGIN_URL', plugin_dir_url(__FILE__));

        require __DIR__ . '/includes/vendor/autoload.php';

        $this->importConfig(WPS_YAML_FILE);

        $this->loadAll('lib');
        $this->loadAll('extensions', true);
        $this->loadAll('plugins', true);
    }
}

function wps() {

    global $wps;

    // Instantiate only once.
    if ( ! isset( $wps ) ) {

        $wps = new WPS();
        $wps->initialize();
    }

    return $wps;
}

if( ( defined('WP_INSTALLING') && WP_INSTALLING ) || !defined('WPINC') )
    return;

// Instantiate.
wps();
