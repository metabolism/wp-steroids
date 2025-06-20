<?php


/**
 * Class
 */
class WPS_Title {

    public function betterTitle($title){

        if ( is_search() ) {

            $search = get_query_var( 's' );
            $title = sprintf( __( 'Search Results for &#8220;%s&#8221;' ), strip_tags( $search ));
        }

        return $title;
    }

	/**
	 * constructor.
	 */
	public function __construct(){

        add_filter( 'wp_title', [$this, 'betterTitle'] );
	}
}
