<?php

/**
 * Class
 */
class WPS_Performance
{
    public function __construct()
    {
        if ( !is_admin() ){

            remove_action( 'init', 'check_theme_switched', 99 );

            add_action('wp_footer', function (){

                if( WP_DEBUG )
                    echo '<!-- '.get_num_queries().' queries in '.timer_stop().' seconds. -->'.PHP_EOL;
            });
        }
    }
}
