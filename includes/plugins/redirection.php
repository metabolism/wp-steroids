<?php


/**
 * Class WPSmartCropProvider
 *
 * @package 
 */
class WPS_Redirection
{
	/**
	 * Construct
	 */
	public function __construct()
	{
        global $_config;

		$role = $_config->get('plugins.redirection.redirection_role', false);

		if( $role ){

			add_filter('redirection_role', function($cap) use($role) {
				return $role;
			});
		}
	}
}