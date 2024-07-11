<?php

/**
 * Class
 */
class WPS_CF7_Antispam {

    /**
     * constructor.
     */
    public function __construct()
    {
        if( is_admin() || wp_is_json_request() )
            return;

        add_action( 'init', function (){

            remove_action( 'init', 'run_cf7a', 11 );
            add_action( 'wpcf7_enqueue_scripts', 'run_cf7a' );

        }, 12);
    }
}
