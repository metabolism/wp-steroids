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
        if( !defined('WPCF7_VERSION') )
            return;

        add_filter( 'wpcf7_load_js', '__return_false' );
        add_filter( 'wpcf7_load_css', '__return_false' );

        add_action( 'wp_enqueue_scripts', function (){

            wp_dequeue_script('google-recaptcha');
            remove_action( 'wp_enqueue_scripts', 'wpcf7_recaptcha_enqueue_scripts', 20 );
        });

        global $_config;

        if( !$_config->get('security.rest_api', false) ){

            add_filter( 'rest_authentication_errors', function( $result ) {

                if ( !is_wp_error($result) )
                    return $result;

                if( $result->get_error_code() === 'restricted_rest_api_access' ){

                    global $wp;

                    $route = $wp->query_vars['rest_route'] ?? '';
                    $method = $_SERVER['REQUEST_METHOD'] ?? '';

                    // Only expose the endpoints used by Contact Form 7 forms.
                    if( in_array($method, ['POST','GET']) && preg_match('#^/'.\WPCF7_REST_Controller::route_namespace.'/(.*)?$#', $route) )
                        return null;
                }

                return  $result;

            }, 11 );
        }
    }
}
