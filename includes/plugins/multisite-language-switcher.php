<?php

use lloc\Msls\MslsPlugin;

/**
 * Class
 *
 * @package
 */
class WPS_Multisite_Language_Switcher {

    public static function getLocale($blog_id=false){

        if( !$blog_id )
            $blog_id = get_current_blog_id();

        $WPLANG = get_blog_option($blog_id, 'WPLANG' );

        if( empty($WPLANG) )
            $WPLANG = 'en_US';

        return $WPLANG;
    }

    public function load_edit_tags(){

        if( isset($_GET['blog_id'], $_GET['tag_ID'], $_GET['clone']))
        {
            $main_site_id = get_main_network_id();
            $current_site_id = get_current_blog_id();

            // switch to origin blog
            switch_to_blog($_GET['blog_id']);

            // get the original $term
            $term = get_term($_GET['tag_ID']);

            if( !is_wp_error($term) )
            {
                //get original translations
                $translations = get_option('msls_term_'.$_GET['tag_ID'],[]);

                // get the original meta
                $meta = get_term_meta($_GET['tag_ID']);

                // get the original language, fallback to en
                $language = get_option('WPLANG');
                $language = empty($language)?'en_US':$language;

                $translations[$language] = $_GET['tag_ID'];

                // return to target blog
                restore_current_blog();

                $inserted_tag_id = wp_insert_term($term->name, $term->taxonomy, ['description'=>$term->description]);

                if( is_wp_error($inserted_tag_id) )
                    wp_die($inserted_tag_id->get_error_message());

                $inserted_tag_id = $inserted_tag_id['term_id'];

                // register original term
                add_option('msls_term_'.$inserted_tag_id, $translations, '', 'no');

                // add and filter meta
                foreach($meta as $key=>$value){

                    $value = maybe_unserialize($value[0]);

                    if(empty($value))
                        continue;

                    if( function_exists('get_field_object') && is_string($value) )
                    {
                        $field = get_field_object($value);

                        if( isset($field['type']) && in_array($field['type'], ['image', 'file']) )
                        {
                            $meta_key = substr($key, 1);
                            $meta_value = $meta[$meta_key][0]??'';

                            if( !empty($meta_value)){

                                if( $current_site_id == $main_site_id )
                                {
                                    switch_to_blog($_GET['blog_id']);
                                    $original_id = get_post_meta($meta_value, '_wp_original_attachment_id', true);
                                    restore_current_blog();

                                    if( $original_id )
                                    {
                                        $meta[$meta_key][0] = $original_id;
                                        update_post_meta($inserted_tag_id, substr($key, 1), $original_id);

                                        continue;
                                    }
                                }
                                else
                                {
                                    $attachments = get_posts(['numberposts'=>1, 'post_type'=>'attachment', 'meta_value'=>$meta_value, 'meta_key'=>'_wp_original_attachment_id', 'fields'=>'ids']);

                                    if( count($attachments) ) {

                                        $meta[$meta_key][0] = $attachments[0];
                                        update_post_meta($inserted_tag_id, substr($key, 1), $attachments[0]);

                                        continue;
                                    }
                                }
                            }
                        }
                    }

                    update_term_meta($inserted_tag_id, $key, $value);
                }

                // get the target language, fallback to us
                $language = get_option('WPLANG');
                $language = empty($language)?'en_US':$language;

                $translations[$language] = $inserted_tag_id;

                //update other terms
                foreach ( get_sites() as $site ) {

                    if ( (int) $site->blog_id !== $current_site_id ) {

                        switch_to_blog( $site->blog_id );

                        $blog_language = get_option('WPLANG');
                        $blog_language = empty($blog_language)?'en_US':$blog_language;

                        foreach ($translations as $_language=>$tag_id){

                            if( $_language == $blog_language ){

                                $_translations = $translations;

                                unset($_translations[$blog_language]);
                                update_option('msls_term_'.$tag_id, $_translations);
                            }
                        }

                        // return to original blog
                        restore_current_blog();
                    }
                }

                // return to edit term
                wp_redirect( get_admin_url($current_site_id, 'term.php?taxonomy='.$term->taxonomy.'&tag_ID='.$inserted_tag_id));
                exit;
            }
        }
    }

    public function load_post_new(){

        global $wpdb;

        if( isset($_GET['blog_id'], $_GET['post_id'], $_GET['clone']))
        {
            $main_site_id = get_main_network_id();
            $current_site_id = get_current_blog_id();

            // switch to origin blog
            switch_to_blog($_GET['blog_id']);

            // get the original post
            $post = get_post($_GET['post_id'], ARRAY_A);

            if( !is_wp_error($post) )
            {
                //remove tags and parent
                unset($post['tags_input'], $post['post_parent']);

                //get original translations
                $translations = get_option('msls_'.$_GET['post_id'],[]);

                // get the original meta
                $meta = get_post_meta($_GET['post_id']);

                // get the original language, fallback to en
                $language = get_option('WPLANG');
                $language = empty($language)?'en_US':$language;

                $translations[$language] = $_GET['post_id'];

                // empty id field, to tell WordPress that this will be a new post
                $post['ID'] = '';

                // return to target blog
                restore_current_blog();

                // insert the post as draft
                $post['post_status'] = 'draft';

                // parse block
                $blocks = parse_blocks($post['post_content']);

                foreach ($blocks as $block){

                    if( class_exists('ACF') && !empty($block['attrs']) ){

                        foreach ( $block['attrs']['data']??[] as $key=>$value ){

                            if( substr($key, 0, 1) == '_' && substr($value, 0, 6) == 'field_' ){

                                $field = get_field_object($value);

                                if( isset($field['type']) && in_array($field['type'], ['image', 'file']) )
                                {
                                    $_key  = substr($key, 1);
                                    $_value = $block['attrs']['data'][$_key]??'';

                                    if( !empty($_value)){

                                        if( $current_site_id == $main_site_id )
                                        {
                                            switch_to_blog($_GET['blog_id']);
                                            $original_id = get_post_meta($_value, '_wp_original_attachment_id', true);
                                            restore_current_blog();

                                            if( $original_id )
                                                $post['post_content'] = str_replace('"'.$_key.'":'.$_value, '"'.$_key.'":'.$original_id, $post['post_content']);
                                        }
                                        else
                                        {
                                            $attachments = get_posts(['numberposts'=>1, 'post_type'=>'attachment', 'meta_value'=>$_value, 'meta_key'=>'_wp_original_attachment_id', 'fields'=>'ids']);

                                            if( count($attachments) )
                                                $post['post_content'] = str_replace('"'.$_key.'":'.$_value, '"'.$_key.'":'.$attachments[0], $post['post_content']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $inserted_post_id = wp_insert_post($post);

                if( is_wp_error($inserted_post_id) )
                    wp_die($inserted_post_id->get_error_message());

                // update raw content
                $wpdb->update($wpdb->posts, ['post_name'=>'', 'post_content'=>$post['post_content']], ['ID'=>$inserted_post_id]);

                // register original post
                add_option('msls_'.$inserted_post_id, $translations, '', 'no');

                // add and filter meta
                foreach($meta as $key=>$value){

                    $value = maybe_unserialize($value[0]);

                    if(empty($value))
                        continue;

                    if($key === '_thumbnail_id' && is_string($value)) {

                        if( $current_site_id == $main_site_id )
                        {
                            switch_to_blog($_GET['blog_id']);
                            $original_id = get_post_meta($value, '_wp_original_attachment_id', true);
                            restore_current_blog();

                            update_post_meta($inserted_post_id, $key, $original_id);
                        }
                        else
                        {
                            $attachments = get_posts(['numberposts'=>1, 'post_type'=>'attachment', 'meta_value'=>$value, 'meta_key'=>'_wp_original_attachment_id', 'fields'=>'ids']);

                            if( count($attachments) )
                                update_post_meta($inserted_post_id, $key, $attachments[0]);
                        }
                    }
                    else{

                        if( function_exists('get_field_object') && is_string($value) )
                        {
                            $field = get_field_object($value);

                            if( isset($field['type']) && in_array($field['type'], ['image', 'file']) )
                            {
                                $meta_key = substr($key, 1);
                                $meta_value = $meta[$meta_key][0]??'';

                                if( !empty($meta_value)){

                                    if( $current_site_id == $main_site_id )
                                    {
                                        switch_to_blog($_GET['blog_id']);
                                        $original_id = get_post_meta($meta_value, '_wp_original_attachment_id', true);
                                        restore_current_blog();

                                        if( $original_id )
                                        {
                                            $meta[$meta_key][0] = $original_id;
                                            update_post_meta($inserted_post_id, substr($key, 1), $original_id);

                                            continue;
                                        }
                                    }
                                    else
                                    {
                                        $attachments = get_posts(['numberposts'=>1, 'post_type'=>'attachment', 'meta_value'=>$meta_value, 'meta_key'=>'_wp_original_attachment_id', 'fields'=>'ids']);

                                        if( count($attachments) ) {

                                            $meta[$meta_key][0] = $attachments[0];
                                            update_post_meta($inserted_post_id, substr($key, 1), $attachments[0]);

                                            continue;
                                        }
                                    }
                                }
                            }
                        }

                        update_post_meta($inserted_post_id, $key, $value);
                    }
                }

                // get the target language, fallback to us
                $language = get_option('WPLANG');
                $language = empty($language)?'en_US':$language;

                $translations[$language] = $inserted_post_id;

                //update other posts
                foreach ( get_sites() as $site ) {

                    if ( (int) $site->blog_id !== $current_site_id ) {

                        switch_to_blog( $site->blog_id );

                        $blog_language = get_option('WPLANG');
                        $blog_language = empty($blog_language)?'en_US':$blog_language;

                        foreach ($translations as $_language=>$post_id){

                            if( $_language == $blog_language ){

                                $_translations = $translations;

                                unset($_translations[$blog_language]);
                                update_option('msls_'.$post_id, $_translations);
                            }
                        }

                        // return to original blog
                        restore_current_blog();
                    }
                }

                // return to edit page
                wp_redirect( get_admin_url($current_site_id, 'post.php?post='.$inserted_post_id.'&action=edit'));
                exit;
            }
        }
    }

    function get_edit_new($path){

        global $current_blog;

        $args = parse_url($path);
        parse_str($args['query']??'', $args);

        if( !isset($args['msls_id']) )
            return $path;

        if( isset($args['taxonomy']) ) {

            $path = add_query_arg([
                'clone' => 'true',
                'blog_id' => $current_blog->blog_id,
                'tag_ID' => $args['msls_id']
            ], $path);
        }
        elseif( isset($args['post_type']) ) {

            $path = add_query_arg([
                'clone' => 'true',
                'blog_id' => $current_blog->blog_id,
                'post_id' => $args['msls_id']
            ], $path);
        }

        return $path;
    }

    function wp_admin_bar_my_sites_menu( $wp_admin_bar ) {

        // Don't show for logged out users or single site mode.
        if ( ! is_user_logged_in() || ! is_multisite() )
            return;

        // Show only when the user has at least one site, or they're a super admin.
        if ( count( $wp_admin_bar->user->blogs ) < 1 && ! current_user_can( 'manage_network' ) )
            return;

        if ( $wp_admin_bar->user->active_blog )
            $my_sites_url = get_admin_url( $wp_admin_bar->user->active_blog->blog_id, 'my-sites.php' );
        else
            $my_sites_url = admin_url( 'my-sites.php' );

        $blogname = self::getLocale();
        $lang = explode('_', $blogname);

        $wp_admin_bar->add_node(
            array(
                'id'    => 'languages',
                'parent' => 'top-secondary',
                'title' => '<span class="flag-icon flag-icon-'.$lang[0].'"></span>'.__( strtoupper($lang[0]) ),
                'href'  => $my_sites_url,
            )
        );

        if ( current_user_can( 'manage_network' ) ) {
            $wp_admin_bar->add_group(
                array(
                    'parent' => 'languages',
                    'id'     => 'languages-super-admin',
                )
            );

            $wp_admin_bar->add_node(
                array(
                    'parent' => 'languages-super-admin',
                    'id'     => 'languages-admin',
                    'title'  => __( 'Network Admin' ),
                    'href'   => network_admin_url(),
                )
            );
        }

        // Add site links.
        $wp_admin_bar->add_group(
            array(
                'parent' => 'languages',
                'id'     => 'languages-list',
                'meta'   => array(
                    'class' => current_user_can( 'manage_network' ) ? 'ab-sub-secondary' : '',
                ),
            )
        );

        $current_blog_id = get_current_blog_id();

        foreach ( (array) $wp_admin_bar->user->blogs as $blog ) {

            if( $blog->userblog_id != $current_blog_id ){

                switch_to_blog( $blog->userblog_id );

                $blogname = self::getLocale();

                if ( ! $blogname )
                    $blogname = preg_replace( '#^(https?://)?(www.)?#', '', get_home_url() );

                $menu_id = 'language-' . $blog->userblog_id;
                $lang = explode('_', $blogname);

                $title = '<span class="flag-icon flag-icon-'.$lang[0].'"></span>'.__( strtoupper($lang[0]) );

                if ( is_admin() ) {

                    $wp_admin_bar->add_node(
                        array(
                            'parent' => 'languages-list',
                            'id'     => $menu_id,
                            'title'  => $title,
                            'href'   => admin_url()
                        )
                    );

                } else {

                    $wp_admin_bar->add_node(
                        array(
                            'parent' => 'languages-list',
                            'id'     => $menu_id,
                            'title'  => $title,
                            'href'   => home_url(),
                        )
                    );
                }

                restore_current_blog();
            }
        }
    }


    /**
     * MSLSProvider constructor.
     */
    public function __construct(){

        if( is_multisite() && defined('MSLS_PLUGIN_VERSION') ){

            if( is_admin() ) {

                global $_config;

                add_action( 'init', function (){

                    add_filter('msls_metabox_post_select_title', function (){ return __( 'Language Switcher', 'wp-steroids' ); });
                });

                add_filter( 'msls_meta_box_render_select_hierarchical', function ($args){

                    $args['post_status'] = 'publish,draft';
                    return $args;
                });

                if( $_config->get('multisite.clone_post', false) ){

                    add_filter( 'msls_admin_icon_get_edit_new', [$this, 'get_edit_new']);
                    add_action( 'load-post-new.php', [$this, 'load_post_new']);
                    add_action( 'load-edit-tags.php', [$this, 'load_edit_tags']);
                }

                add_filter('admin_body_class', function ( $classes ) {

                    return $classes.' multisite-language-switcher';
                });
            }
            else{

                add_action( 'init', function (){

                    if( is_admin_bar_showing() ) {

                        $ver = defined('MSLS_PLUGIN_VERSION') ? constant('MSLS_PLUGIN_VERSION') : false;
                        wp_enqueue_style('msls-flags', MslsPlugin::plugins_url('css-flags/css/flag-icon.min.css'), [], $ver);
                    }
                });

                add_filter( 'body_class', function ( $classes ) {

                    $classes[] = 'multisite-language-switcher';
                    return $classes;
                });
            }

            add_action( 'init', function (){

                remove_action( 'admin_bar_menu', [ MslsPlugin::class, 'update_adminbar' ], 999 );
            });


            add_action( 'admin_bar_menu', [$this, 'wp_admin_bar_my_sites_menu'], 10 );

            //todo: find why $url is buggy
            add_filter( 'mlsl_output_get_alternate_links', function ($url, $blog){

                if( strpos($url, 'http') === false )
                    return null;

                return $url;
            }, 10, 2);
        }
    }
}
