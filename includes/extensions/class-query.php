<?php


/**
 * Class
 */
class WPS_Query {

    protected $config;

    /**
     * Filter the terms clauses
     *
     * @param $clauses array
     * @param $taxonomy string
     * @param $args array
     * @return array
     */
    public function terms_clauses( $clauses, $taxonomy, $args )
    {
        if ( isset($args['post_type']) )
            $args['post_types'] = [$args['post_type']];

        if ( isset($args['post_types']) ) {

            global $wpdb;

            $post_types = implode("','", array_map('esc_sql', (array) $args['post_types']));

            // allow for arrays
            if ( is_array($args['post_types']) )
                $post_types = implode( "','", $args['post_types'] );

            $clauses['join'] .= " INNER JOIN $wpdb->term_relationships AS r ON r.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN $wpdb->posts AS p ON p.ID = r.object_id";
            $clauses['where'] .= " AND p.post_type IN ('". $post_types. "') GROUP BY t.term_id";
        }

        return $clauses;
    }

    /**
     * Add custom post type for taxonomy archive page
     * @param \WP_Query $query
     * @return void
     */
    public function pre_get_posts( $query )
    {
        if( !$query->is_main_query() ){

            if( !$query->get('post_status') && current_user_can('administrator') ){

                $query->set('post_status', ['publish','draft','pending','private']);
                $query->query['post_status'] =  ['publish','draft','pending','private'];
            }

            return;
        }

        $object = $query->get_queried_object();

        if ( $query->is_archive && is_object($object) )
        {
            $class = get_class($object);

            if( in_array($class, ['WP_Post_Type','WP_Term']) ){

                $class = $class == 'WP_Post_Type' ? 'post_type' : 'taxonomy';

                if( $ppp = $this->config->get($class.'.'.$object->name.'.posts_per_page', false) ){

                    $query->set( 'posts_per_page', $ppp );
                    $query->query[ 'posts_per_page'] = $ppp;
                }

                if( $orderby = $this->config->get($class.'.'.$object->name.'.orderby', false) ){

                    $query->set( 'orderby', $orderby );
                    $query->query[ 'orderby'] = $orderby;
                }

                if( $order = $this->config->get($class.'.'.$object->name.'.order', false) ){

                    $query->set( 'order', $order );
                    $query->query[ 'order'] = $order;
                }
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
                    "(".$wpdb->posts.".post_title LIKE $1) OR (".$wpdb->postmeta.".meta_value LIKE $1) OR (".$wpdb->posts.".ID LIKE $1)", $where );
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

    public function preview_access( $posts ) {

        if ( !$posts || !count($posts) )
            return $posts;

        $post = $posts[0];

        if( is_main_query() ){

            if( $post->post_status == 'draft' && current_user_can('read_draft_posts') )
                $post->post_status = 'publish';
            elseif( $post->post_status == 'pending' && current_user_can('read_pending_posts') )
                $post->post_status = 'publish';
            elseif( $post->post_status == 'future' && current_user_can('read_future_posts') )
                $post->post_status = 'publish';
        }

        return $posts;
    }

    /**
     * @param $orderby
     * @param WP_Query $q
     * @return string
     */
    public function custom_order($orderby, \WP_Query $q)
    {
        if( 'last_word' === $q->get( 'orderby' ) && $get_order = $q->get( 'order' ) )
        {
            if( in_array( strtoupper( $get_order ), ['ASC', 'DESC'] ) )
            {
                global $wpdb;
                $orderby = " SUBSTRING_INDEX( {$wpdb->posts}.post_title, ' ', -1 ) " . $get_order;
            }
        }

        return $orderby;
    }

    /**
     * @param $orderby
     * @return string
     */
    public function add_sticky_posts($orderby)
    {
        global $wpdb;

        $sticky_posts = get_option( 'sticky_posts' );

        if( empty($sticky_posts) || empty($orderby) )
            return $orderby;

        return 'CASE WHEN '.$wpdb->posts.'.ID IN ('.implode(',', $sticky_posts).') THEN -1 ELSE 0 END, '.$orderby;
    }


    /**
     * @param $query
     * @return array
     */
    public function wp_link_query_args($query)
    {
        $post_types = get_post_types( array( 'public' => true ), 'objects' );

        foreach ($post_types as $post_type=>$args){

            if( (!$args->publicly_queryable || !$args->query_var) && $key = array_search($post_type, $query['post_type']) ){
                unset($query['post_type'][$key]);
            }
        }

        return $query;
    }


    /**
     * constructor.
     */
    public function __construct(){

        global $_config;
        $this->config = $_config;

        if( !is_admin() ){
            
            add_action( 'pre_get_posts', [$this, 'pre_get_posts'] );
            add_filter( 'terms_clauses', [$this, 'terms_clauses'], 99999, 3);
            add_filter( 'posts_results', [$this, 'preview_access'], 10, 2 );
            add_filter( 'posts_orderby', [$this, 'custom_order'], 10, 2 );
        }

        if( $this->config->get('search.use_metafields', false) )
            $this->search_in_meta();

        add_filter( 'wp_link_query_args', [$this, 'wp_link_query_args'] );
        add_filter( 'posts_orderby', [$this, 'add_sticky_posts'], 10, 2 );
    }
}
