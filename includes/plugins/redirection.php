<?php


/**
 * Class
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

			add_filter('redirection_role', function() use($role) {
				return $role;
			});
		}
	}
}