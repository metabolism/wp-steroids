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
    }
}
