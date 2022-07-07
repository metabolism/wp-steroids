<?php


/**
 * Class
 */
class WPS_Url {

	/**
	 * Remove link when there is no template support
	 */
	public function removeAdminBarLinks(){

		global $wp_admin_bar;
		$wp_admin_bar->remove_menu('view-site');
		$wp_admin_bar->remove_menu('site-name');
	}


	/**
	 * Remove link when there is no template support
	 * @param $html
	 * @return string|string[]|null
	 */
	public function applyUrlMapping($html){

		$html = preg_replace('/<span id="sample-permalink"><a href="(.*)">(.*)<span/', '<span id="sample-permalink"><a href="'.URL_MAPPING.'$1">'.URL_MAPPING.'$2<span', $html);
		$html = preg_replace('/<a id="sample-permalink" href="(.*)">(.*)<\/a>/', '<a id="sample-permalink" href="'.URL_MAPPING.'$1">'.URL_MAPPING.'$2</a>', $html);
		return $html;
	}


	/**
	 * Redirect admin after login
	 * @return void
	 */
	public function redirectAdmin(){

        global $_config;

        foreach ( $_config->get('role', []) as $role => $args )
        {
            if( isset($args['redirect_to']) ){

                add_filter( 'login_redirect', function($redirect_to, $requested_redirect_to, $user) use ($role, $args){

                    if( !is_wp_error($user) && in_array($role, $user->roles) )
                        return get_admin_url(null, $args['redirect_to']);

                    return $redirect_to;

                }, 10, 3);
            }
        }
	}


	/**
	 * UrlPlugin constructor.
	 */
	public function __construct(){

		if( HEADLESS ){

			add_action( 'wp_before_admin_bar_render', [$this, 'removeAdminBarLinks'] );

			if( URL_MAPPING ){
				add_filter('get_sample_permalink_html', [$this, 'applyUrlMapping'] );
			}
		}

		$this->redirectAdmin();
    }
}
