<?php

/**
 * Class 
 */
class WPS_Performance
{
    public static $plugins = [
        'classic-editor'=>'classic-editor/classic-editor.php',
        'acf-flexible-layouts-manager'=>'acf-flexible-layouts-manager/acf-flexible-layouts-manager.php',
        'acf-restrict-color-picker'=>'acf-restrict-color-picker/acf-restrict-color-picker.php',
        'multisite-clone-duplicator'=>'multisite-clone-duplicator/multisite-clone-duplicator.php'
    ];

    public function removeBlockLibrary(){

        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
        wp_dequeue_style( 'wc-block-style' ); // REMOVE WOOCOMMERCE BLOCK CSS
        wp_dequeue_style( 'global-styles' ); // REMOVE THEME.JSON
    }

    public function __construct()
    {
        if ( !is_admin() ){

            global $_config;

            add_filter('option_active_plugins', [$this, 'disablePlugins']);

            if( !$_config->get('gutenberg', false) ) {
                add_filter('site_option_active_sitewide_plugins', [$this, 'disableSitewidePlugins']);
                add_action( 'wp_enqueue_scripts', [$this, 'removeBlockLibrary'], 100 );
            }

            remove_action( 'init', 'check_theme_switched', 99 );

            add_action('wp_footer', function (){

                if( WP_DEBUG )
                    echo '<!-- '.get_num_queries().' queries in '.timer_stop().' seconds. -->'.PHP_EOL;
            });
        }
    }

    function disablePlugins($plugins){

        $disabled_plugins = array_values(self::$plugins);

        foreach ($plugins as $i => $plugin){

            if ( in_array(($plugin), $disabled_plugins) )
                unset($plugins[$i]);
        }

        return $plugins;
    }

    function disableSitewidePlugins($plugins){

        $disabled_plugins = array_values(self::$plugins);

        foreach ($plugins as $plugin => $time){

            if ( in_array($plugin, $disabled_plugins) )
                unset($plugins[$plugin]);
        }

        return $plugins;
    }
}