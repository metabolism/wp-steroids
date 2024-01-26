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

    public function add_sticky_posts($posts, $wp_query)
    {
        $q = $wp_query->query_vars;
        $post_type = $wp_query->get( 'post_type' );

        $page = absint( $q['paged'] );

        if ( ! $page )
            $page = 1;

        if( $wp_query->is_main_query() || $page > 1 || !post_type_supports( $post_type, 'sticky' ) )
            return $posts;

        // Code copied from class-wp-query.php line 3471

        $sticky_posts = get_option( 'sticky_posts' );

        if ( is_array( $sticky_posts ) && ! empty( $sticky_posts ) && ! $q['ignore_sticky_posts'] ) {
            $num_posts     = count( $posts );
            $sticky_offset = 0;
            // Loop over posts and relocate stickies to the front.
            for ( $i = 0; $i < $num_posts; $i++ ) {
                if ( in_array( $posts[ $i ]->ID, $sticky_posts, true ) ) {
                    $sticky_post = $posts[ $i ];
                    // Remove sticky from current position.
                    array_splice( $posts, $i, 1 );
                    // Move to front, after other stickies.
                    array_splice( $posts, $sticky_offset, 0, array( $sticky_post ) );
                    // Increment the sticky offset. The next sticky will be placed at this offset.
                    ++$sticky_offset;
                    // Remove post from sticky posts array.
                    $offset = array_search( $sticky_post->ID, $sticky_posts, true );
                    unset( $sticky_posts[ $offset ] );
                }
            }

            // If any posts have been excluded specifically, Ignore those that are sticky.
            if ( ! empty( $sticky_posts ) && ! empty( $q['post__not_in'] ) ) {
                $sticky_posts = array_diff( $sticky_posts, $q['post__not_in'] );
            }

            // Fetch sticky posts that weren't in the query results.
            if ( ! empty( $sticky_posts ) ) {
                $stickies = get_posts(
                    array(
                        'post__in'               => $sticky_posts,
                        'post_type'              => $post_type,
                        'post_status'            => 'publish',
                        'posts_per_page'         => count( $sticky_posts ),
                        'suppress_filters'       => $q['suppress_filters'],
                        'cache_results'          => $q['cache_results'],
                        'update_post_meta_cache' => $q['update_post_meta_cache'],
                        'update_post_term_cache' => $q['update_post_term_cache'],
                        'lazy_load_term_meta'    => $q['lazy_load_term_meta'],
                    )
                );

                foreach ( $stickies as $sticky_post ) {
                    array_splice( $posts, $sticky_offset, 0, array( $sticky_post ) );
                    ++$sticky_offset;
                }
            }
        }

        //limit count to requested
        return array_slice($posts,0, $q['posts_per_page']);
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

            add_filter( 'posts_results', [$this, 'add_sticky_posts'], 10, 2 );
            add_action( 'pre_get_posts', [$this, 'pre_get_posts'] );
            add_filter( 'terms_clauses', [$this, 'terms_clauses'], 99999, 3);
            add_filter( 'posts_results', [$this, 'preview_access'], 10, 2 );
        }
    }
}
