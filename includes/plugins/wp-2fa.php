<?php

/**
 * Class
 *
 * @package
 */
class WPS_WP_2FA
{

    /**
     * Construct
     */
    public function __construct()
    {
        //disable plugin on non admin page to prevent Symfony class collision
        if( !is_admin() ){

            add_filter( 'option_active_plugins', function( $plugins ){

                $myplugin = "wp-2fa/wp-2fa.php";

                $k = array_search( $myplugin, $plugins );

                unset( $plugins[$k] );

                return $plugins;
            });
        }
    }
}
