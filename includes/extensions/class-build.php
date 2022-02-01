<?php

/**
 * Class 
 */
class WPS_Build {


    /**
     * Add maintenance button and checkbox
     */
    public function addBuildButton()
    {
        if( !current_user_can('editor') && !current_user_can('administrator') )
            return;

        add_action( 'admin_bar_menu', function( $wp_admin_bar )
        {
            $args = [
                'id'    => 'build',
                'title' => '<span class="ab-icon"></span>'.__('Build'),
                'href'  => BUILD_HOOK
            ];

            $wp_admin_bar->add_node( $args );

        }, 999 );
    }

    /**
     * MaintenancePlugin constructor.
     */
    public function __construct()
    {
        if( is_admin() && defined('BUILD_HOOK') )
            add_action( 'init', [$this, 'addBuildButton']);
    }
}