<?php

/**
 * Class
 */
class WPS_Cache_Control {

    public function cacheControlSendHeaders(){

        global $cache_control_options;

        if( empty($cache_control_options) || !function_exists('cache_control_send_http_header') )
            return;
        
        $post_types = get_post_types(['publicly_queryable'=> true,'_builtin' => false]);
        $taxonomies = get_taxonomies(['publicly_queryable'=> true,'_builtin' => false]);
        $queried_object = get_queried_object();

        if ( cache_control_nocacheables() ){

            $directive = cache_control_build_directive_header( FALSE, FALSE, FALSE, FALSE );
        }
        elseif( is_post_type_archive($post_types) ){

            $directive = cache_control_build_directive_from_option($queried_object->name.'_archive');
        }
        elseif( is_singular($post_types) ){

            $directive = cache_control_build_directive_from_option($queried_object->post_type);
        }
        elseif( is_tax($taxonomies) ){

            $directive = cache_control_build_directive_from_option($queried_object->taxonomy);
        }
        else{

            $directive = cache_control_select_directive();
        }

        cache_control_send_http_header( $directive );
    }

    public function init(){

        global $cache_control_options;

        if( empty($cache_control_options) || !function_exists('cache_control_send_http_header') )
            return;

        $post_types = get_post_types( ['publicly_queryable'=> true,'_builtin' => false], 'objects');

        foreach ($post_types as $post_type){

            if( !isset($cache_control_options[$post_type->name]) ){

                $cache_control_options[$post_type->name] = [
                    'id'         => $post_type->name,
                    'name'       => $post_type->label,
                    'max_age'    => 600,
                    's_maxage'   => 0,
                    'staleerror' => 0,
                    'stalereval' => 0
                ];
            }

            if( $post_type->has_archive && !isset($cache_control_options[$post_type->name.'_archive']) ){

                $cache_control_options[$post_type->name.'_archive'] = [
                    'id'         => $post_type->name.'_archive',
                    'name'       => $post_type->labels->all_items,
                    'max_age'    => 600,
                    's_maxage'   => 0,
                    'staleerror' => 0,
                    'stalereval' => 0
                ];
            }
        }

        $taxonomies = get_taxonomies( ['publicly_queryable'=> true,'_builtin' => false], 'objects');

        foreach ($taxonomies as $taxonomy){

            if( !isset($cache_control_options[$taxonomy->name]) ){

                $cache_control_options[$taxonomy->name] = [
                    'id'         => $taxonomy->name,
                    'name'       => $taxonomy->label,
                    'max_age'    => 600,
                    's_maxage'   => 0,
                    'staleerror' => 0,
                    'stalereval' => 0
                ];
            }
        }
    }

    public function __construct()
    {
        add_action('init', [$this, 'init']);

        remove_action( 'template_redirect', 'cache_control_send_headers' );
        add_action( 'template_redirect', [$this, 'cacheControlSendHeaders'] );
    }
}