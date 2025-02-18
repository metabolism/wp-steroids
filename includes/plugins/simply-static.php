<?php

/**
 * Class
 *
 * @package
 */
class WPS_Simply_Static
{
    public function __construct()
    {
        add_filter( 'clean_url', function ($good_protocol_url, $original_url){

            if( str_contains($original_url, '/simply-static-') && defined('CONTENT_DIR') ){

                $base_url = is_multisite() ? network_home_url() : get_home_url();

                $root_path = str_replace(CONTENT_DIR, '', WP_CONTENT_DIR);
                return str_replace($root_path, $base_url, $original_url);
            }

            return $original_url;

        }, 10, 2);
    }
}