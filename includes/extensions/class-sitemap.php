<?php

use Dflydev\DotAccessData\Data;

/**
 * Class
 */
class WPS_Sitemap {

    private $config;


    /**
     * Sitemap constructor.
     */
    public function __construct()
    {
        add_filter('wp_sitemaps_add_provider', function ($provider, $name) {

            if( $name == 'users')
                return null;

            return $provider;

        }, 10, 2);
    }
}
