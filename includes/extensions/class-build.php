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

		if( defined('BUILD_HOOK') && BUILD_HOOK ){

			add_action( 'admin_bar_menu', function( $wp_admin_bar )
			{
				$args = [
					'id'    => 'build',
					'title' => '<span class="ab-icon"></span>'.__('Build', 'wp-steroids'),
					'href'  => BUILD_HOOK
				];

				$wp_admin_bar->add_node( $args );

			}, 999 );
		}

		if( defined('BUILD_BADGE') && BUILD_BADGE ){

			add_action( 'rightnow_end', function( $wp_admin_bar )
			{
				echo '<div class="wps-build-badge"><b>Build</b> <img src="'.BUILD_BADGE.'&v='.uniqid().'" data-url="'.BUILD_BADGE.'" id="wps-build-badge"/></div>';

			}, 999 );
		}
    }

    /**
     * MaintenancePlugin constructor.
     */
    public function __construct()
    {
        if( !is_admin() )
			return;

	    add_action( 'init', [$this, 'addBuildButton']);
    }
}
