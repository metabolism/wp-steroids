<?php


use Dflydev\DotAccessData\Data;

/**
 * Class 
 */
class WPS_Query {

    protected $config;

    /**
     * Add custom post type for taxonomy archive page
     * @param \WP_Query $query
     * @return \WP_Query
     */
    public function pre_get_posts( $query )
    {
        if( !$query->is_main_query() )
            return $query;

        global $wp_query;

        $object = $wp_query->get_queried_object();

        if ( $query->is_archive && is_object($object) )
        {
            if( get_class($object) == 'WP_Post_Type' ){

                if( $ppp = $this->config->get('post_type.'.$object->name.'.posts_per_page', false) )
                    $query->set( 'posts_per_page', $ppp );

                if( $orderby = $this->config->get('post_type.'.$object->name.'.orderby', false) )
                    $query->set( 'orderby', $orderby );

                if( $order = $this->config->get('post_type.'.$object->name.'.order', false) )
                    $query->set( 'order', $order );
            }
            elseif( get_class($object) == 'WP_Term' ){

                if( $ppp = $this->config->get('taxonomy.'.$object->taxonomy.'.posts_per_page', false) )
                    $query->set( 'posts_per_page', $ppp );

                if( $orderby = $this->config->get('taxonomy.'.$object->name.'.orderby', false) )
                    $query->set( 'orderby', $orderby );

                if( $order = $this->config->get('taxonomy.'.$object->name.'.order', false) )
                    $query->set( 'order', $order );
            }
        }

        if ( $query->is_tax && !get_query_var('post_type') )
        {
            global $wp_taxonomies;

            $post_type = ( isset($object->taxonomy, $wp_taxonomies[$object->taxonomy] ) ) ? $wp_taxonomies[$object->taxonomy]->object_type :[];

            $query->set('post_type', $post_type);
            $query->query['post_type'] = $post_type;
        }

        if( $query->is_search ) {

            if( $ppp = $this->config->get('search.posts_per_page', false) )
                $query->set( 'posts_per_page', $ppp );
        }

        // opti
        if( $post_type = $query->get('post_type') ){

            if( is_array($post_type) && count($post_type) == 1 ){

                $query->set('post_type', $post_type[0]);
                $query->query['post_type'] = $post_type[0];
            }
        }

        return $query;
    }

    public function search_in_meta(){

        add_filter( 'posts_distinct', function( $where ) {

            if ( is_search() )
                return "DISTINCT";

            return $where;
        });

        add_filter( 'posts_where', function( $where ) {

            global $wpdb;

            if ( is_search() ) {

                $where = preg_replace(
                    "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
                    "(".$wpdb->posts.".post_title LIKE $1) OR (".$wpdb->postmeta.".meta_value LIKE $1)", $where );
            }

            return $where;
        });

        add_filter('posts_join',  function( $join ) {

            global $wpdb;

            if ( is_search() )
                $join .=' LEFT JOIN '.$wpdb->postmeta. ' ON '. $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';

            return $join;
        });
    }


	/**
	 * constructor.
	 */
	public function __construct(){

        if( !is_admin() ){

            global $_config;
            $this->config = $_config;

            if( $this->config->get('search.use_metafields', false) )
                add_action( 'init', [$this, 'search_in_meta']);

            add_action( 'pre_get_posts', [$this, 'pre_get_posts'] );
        }
    }
}
