<?php

/**
 * Class
 *
 * @package
 */
class WPS_Relevanssi
{
    public function fixFoundPosts(){

        if( !function_exists('relevanssi_do_query') )
            return;

        add_filter( 'posts_pre_query', function( $check, $query ) {

            if ( $query->query_vars['relevanssi']??true ) {

                $posts = relevanssi_do_query( $query );
                $query->relevanssi_found_posts = $query->found_posts;

                return $posts;
            }

            return $check;

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
        add_action('init', [$this, 'fixFoundPosts']);
    }
}