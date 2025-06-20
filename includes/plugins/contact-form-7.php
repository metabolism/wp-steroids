<?php

/**
 * Class
 */
class WPS_Contact_Form_7 {

    public static function enqueue_scripts(){

        add_action( 'wp_enqueue_scripts', function (){

            if ( function_exists( 'wpcf7_enqueue_scripts' ) )
                wpcf7_enqueue_scripts();

            if ( function_exists( 'wpcf7_enqueue_styles' ) )
                wpcf7_enqueue_styles();
        });
    }

    /**
     * constructor.
     */
    public function __construct()
    {
        add_filter( 'wpcf7_load_js', '__return_false' );
        add_filter( 'wpcf7_load_css', '__return_false' );

        add_action( 'wp_enqueue_scripts', function (){

            wp_dequeue_script('google-recaptcha');
            remove_action( 'wp_enqueue_scripts', 'wpcf7_recaptcha_enqueue_scripts', 20 );
        });

        global $_config;

        if( !$_config->get('security.rest_api', false) ){

            add_filter( 'rest_authentication_errors', function( $result ) {

                if( !is_wp_error($result) || !defined('REST_REQUEST') || !REST_REQUEST )
                    return  $result;

                if( $result->get_error_code() == 'restricted_rest_api_access' && strpos($_SERVER['REQUEST_URI'], 'contact-form-7') !== false )
                    return false;

                return  $result;

            }, 11 );
        }
    }
}
