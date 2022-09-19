<?php

/**
 * Class
 *
 * @package 
 */
class WPS_Smart_Crop
{
	/**
	 * Construct
	 */
	public function __construct()
	{
		if( !is_admin() ) {

			add_action('init', function() {

				if( class_exists('WP_Smart_Crop') ){

					$instance = \WP_Smart_Crop::Instance();
					remove_action('wp_enqueue_scripts', [$instance, 'wp_enqueue_scripts']);
				}
			});
		}
	}
}
