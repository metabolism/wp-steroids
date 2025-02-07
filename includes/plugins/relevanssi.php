<?php

/**
 * Class
 *
 * @package
 */
class WPS_Relevanssi
{
	public function setFoundPosts(){

		if( !function_exists('relevanssi_do_query') )
			return;

		add_filter( 'posts_pre_query', function( $posts, $query ) {

			if ( $query->query_vars['relevanssi']??false ) {

				$posts = relevanssi_do_query( $query );
				$query->relevanssi_found_posts = $query->found_posts;
			}

			return $posts;

		}, 10, 2 );

		add_filter( 'found_posts', function( $found_posts, $query ){

			return $query->relevanssi_found_posts??$query->found_posts;

		}, 20, 2 );
	}

	/**
	 * Construct
	 */
	public function __construct()
	{
        global $_config;

        $capability = $_config->get('plugins.relevanssi.options_capability', false);

        if( $capability ){

            add_filter('relevanssi_options_capability', function() use($capability) {
                return $capability;
            });
        }

		add_action('init', [$this, 'setFoundPosts']);
	}
}
