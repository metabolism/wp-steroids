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

            if( !is_wp_error($term) ) {

                //get original translations
                $translations = get_option('msls_term_'.$_GET['tag_ID'],[]);

                // get the original meta
                $meta = get_term_meta($_GET['tag_ID']);

                // get the original language
                $language = self::getLocale();

                $translations[$language] = $_GET['tag_ID'];

                // return to target blog
                restore_current_blog();

                //filter parent
                $parent = $this->getOriginalId($current_site_id, $main_site_id, $term->parent, 'taxonomy');

                //insert term
                $inserted_tag_id = wp_insert_term($term->name, $term->taxonomy, ['description'=>$term->description, 'parent'=>$parent]);

                if( is_wp_error($inserted_tag_id) )
                    wp_die($inserted_tag_id->get_error_message());

                $inserted_tag_id = $inserted_tag_id['term_id'];

                // register original term
                add_option('msls_term_'.$inserted_tag_id, $translations, '', 'no');

                // add and filter meta
                foreach($meta as $key=>$value){

                    $value = maybe_unserialize($value[0]);

                    add_term_meta($inserted_tag_id, $key, $value);

                    if( empty($value) )
                        continue;

                    if( $key === '_thumbnail_id' ) {

                        $original_id = $this->getOriginalId($current_site_id, $main_site_id, $value, 'image');
                        update_term_meta($inserted_tag_id, $key, $original_id);
                    }
                    elseif( class_exists('ACF') ){

                        if( str_starts_with($key, '_') && str_starts_with($value, 'field_') ) {

                            $field = get_field_object($value);

                            if( in_array($field['type']??'', ['image', 'file', 'post_object', 'taxonomy', 'relationship', 'gallery']) ) {

                                $meta_key = substr($key, 1);
                                $meta_value = maybe_unserialize($meta[$meta_key][0]??'');

                                if( is_array($meta_value) ){

                                    $new_meta_values = [];

                                    foreach ($meta_value as $_meta_value)
                                        $new_meta_values[] = $this->getOriginalId($current_site_id, $main_site_id, $_meta_value, $field['type']);

                                    update_term_meta($inserted_tag_id, $meta_key, array_filter($new_meta_values));
                                }
                                else{

                                    $new_meta_value = $this->getOriginalId($current_site_id, $main_site_id, $meta_value, $field['type']);
                                    update_term_meta($inserted_tag_id, $meta_key, $new_meta_value);
                                }
                            }
                        }
                    }
                }

                // get the target language
                $language = self::getLocale();

                $translations[$language] = $inserted_tag_id;

                //update other terms
                foreach ( get_sites() as $site ) {

                    if ( (int) $site->blog_id !== $current_site_id ) {

                        switch_to_blog( $site->blog_id );

                        $blog_language = self::getLocale();

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
            else{

                restore_current_blog();
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

            // get the original terms
            $taxonomies = get_object_taxonomies( $post['post_type']);
            $terms = wp_get_post_terms($_GET['post_id'], $taxonomies);

            if( !is_wp_error($post) ) {

                //remove tags and parent
                unset($post['tags_input']);

                //get original translations
                $translations = get_option('msls_'.$_GET['post_id'],[]);

                // get the original meta
                $meta = get_post_meta($_GET['post_id']);

                // get the original language, fallback to en
                $language = self::getLocale();

                $translations[$language] = $_GET['post_id'];

                // empty id field, to tell WordPress that this will be a new post
                $post['ID'] = '';

                // return to target blog
                restore_current_blog();

                //filter parent
                $post['post_parent'] = $this->getOriginalId($current_site_id, $main_site_id, $post['post_parent'], 'post_object');

                // insert the post as draft
                $post['post_status'] = 'draft';

                // parse blocks
                if( has_blocks($post['post_content']) && class_exists('ACF') ) {

                    $blocks = parse_blocks($post['post_content']);

                    foreach ($blocks as &$block) {

                        foreach ($block['attrs']['data']??[] as $key => $value) {

                            //filter fields
                            if ( str_starts_with($key, '_') && str_starts_with($value, 'field_') ) {

                                $field = get_field_object($value);

                                if (in_array($field['type'] ?? '', ['image', 'file', 'post_object', 'taxonomy', 'relationship', 'gallery'])) {

                                    $_key = substr($key, 1);
                                    $_value = $block['attrs']['data'][$_key] ?? '';

                                    if (is_array($_value)) {

                                        $new_values = [];

                                        foreach ($_value as $__value)
                                            $new_values[] = $this->getOriginalId($current_site_id, $main_site_id, $__value, $field['type']);

                                        $block['attrs']['data'][$_key] = array_filter($new_values);
                                    }
                                    else {

                                        $new_value = $this->getOriginalId($current_site_id, $main_site_id, $_value, $field['type']);
                                        $block['attrs']['data'][$_key] = $new_value;
                                    }
                                }
                            }
                        }
                    }

                    $post['post_content'] = serialize_blocks($blocks);
                }

                $inserted_post_id = wp_insert_post($post);

                if (is_wp_error($inserted_post_id))
                    wp_die($inserted_post_id->get_error_message());

                // update raw content
                $wpdb->update($wpdb->posts, ['post_name' => '', 'post_content' => $post['post_content']], ['ID' => $inserted_post_id]);

                // register original post
                add_option('msls_' . $inserted_post_id, $translations, '', 'no');

                // add and filter meta
                foreach($meta as $key=>$value){

                    $value = maybe_unserialize($value[0]);

                    add_post_meta($inserted_post_id, $key, $value);

                    if( empty($value) )
                        continue;

                    if( $key === '_thumbnail_id' ) {

                        $original_id = $this->getOriginalId($current_site_id, $main_site_id, $value, 'image');
                        update_post_meta($inserted_post_id, $key, $original_id);
                    }
                    elseif( class_exists('ACF') ){

                        if( str_starts_with($key, '_') && str_starts_with($value, 'field_') ) {

                            $field = get_field_object($value);

                            if( in_array($field['type']??'', ['image', 'file', 'post_object', 'taxonomy', 'relationship', 'gallery']) ) {

                                $meta_key = substr($key, 1);
                                $meta_value = maybe_unserialize($meta[$meta_key][0]??'');

                                if( is_array($meta_value) ){

                                    $new_meta_values = [];

                                    foreach ($meta_value as $_meta_value)
                                        $new_meta_values[] = $this->getOriginalId($current_site_id, $main_site_id, $_meta_value, $field['type']);

                                    update_post_meta($inserted_post_id, $meta_key, array_filter($new_meta_values));
                                }
                                else{

                                    $new_meta_value = $this->getOriginalId($current_site_id, $main_site_id, $meta_value, $field['type']);
                                    update_post_meta($inserted_post_id, $meta_key, $new_meta_value);
                                }
                            }
                        }
                    }
                }

                // get the target language
                $language = self::getLocale();

                $translations[$language] = $inserted_post_id;

                // add terms
                foreach ($terms as $term){

                    if( $term_id = $this->getOriginalId($current_site_id, $main_site_id, $term->term_id, 'taxonomy') )
                        wp_add_object_terms($inserted_post_id, [$term_id], $term->taxonomy);
                }

                //update other posts
                foreach ( get_sites() as $site ) {

                    if ( (int) $site->blog_id !== $current_site_id ) {

                        switch_to_blog( $site->blog_id );

                        $blog_language = self::getLocale();

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
            else{

                restore_current_blog();
            }
        }
    }

    /**
     * @param $current_site_id
     * @param $main_site_id
     * @param $_value
     * @param $type
     * @return int|null
     */
    private function getOriginalId($current_site_id, $main_site_id, $_value, $type){

        if( empty($_value) || !isset($_GET['blog_id']) )
            return null;

        global $wpdb;

        if( in_array($type, ['image', 'file', 'gallery']) ){

            if( $current_site_id == $main_site_id ) {

                // switch to origin blog
                switch_to_blog($_GET['blog_id']);
                $original_id = get_post_meta($_value, '_wp_original_attachment_id', true);
                restore_current_blog();

                if( $original_id )
                    return $original_id;
            }
            else {

                $attachments = get_posts(['numberposts'=>1, 'post_type'=>'attachment', 'meta_value'=>$_value, 'meta_key'=>'_wp_original_attachment_id', 'fields'=>'ids']);

                if( count($attachments) )
                    return $attachments[0];
            }
        }
        elseif( in_array($type, ['post_object', 'taxonomy', 'relationship'])){

            $lang = self::getLocale();

            $value = null;
            $option_name = $type == 'taxonomy' ? 'msls_term_%d' : 'msls_%d';

            // switch to origin blog
            switch_to_blog($_GET['blog_id']);

            $options = $wpdb->get_var( $wpdb->prepare("SELECT `option_value` FROM $wpdb->options WHERE `option_name` LIKE '$option_name'", $_value) );
            $options = maybe_unserialize($options);

            if( isset($options[$lang]) )
                $value = $options[$lang];

            restore_current_blog();

            return $value;
        }

        return null;
    }

    function get_edit_new($path){

        global $current_blog;

        $args = parse_url($path);
        parse_str($args['query']??'', $args);

        if( !isset($args['msls_id']) ){

            if( isset($_GET['post'], $args['post_type']) )
                $args['msls_id'] = $_GET['post'];
            elseif( isset($_GET['tag_ID'], $args['taxonomy']) )
                $args['msls_id'] = $_GET['tag_ID'];
            else
                return $path;
        }

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


    public function syncIds(){

        $msls_options = get_blog_option( get_current_blog_id(), 'msls' );
        $copy_blog_id = $msls_options['blog_id'];
        $current_blog_id = get_current_blog_id();

        //erase blog id to prevent multiple sync
        unset($msls_options['blog_id']);
        update_blog_option(get_current_blog_id(), 'msls', $msls_options);

        // update current blog
        global $wpdb;
        $options = $wpdb->get_results( "SELECT * FROM $wpdb->options WHERE `option_name` LIKE 'msls_%'" );

        $copy_lang = self::getLocale($copy_blog_id);

        $languages = [];

        foreach ($options as $option){

            $name = str_replace('msls_', '', $option->option_name);
            $id = str_replace('term_', '', $name);

            $value = maybe_unserialize($option->option_value);

            if( is_array($value) ){

                //build a references array for other languages
                foreach ($value as $lang=>$target_id){

                    if( $name != $id )
                        $languages[$lang]['term_'.$target_id] = $id;
                    else
                        $languages[$lang][$target_id] = $id;
                }

                $value[$copy_lang] = $id;
                update_option($option->option_name, $value);
            }
        }

        $current_lang = self::getLocale($current_blog_id);

        //copy on other blogs
        foreach (get_sites() as $site){

            if( $site->blog_id == $current_blog_id )
                continue;

            switch_to_blog($site->blog_id);

            $options = $wpdb->get_results( "SELECT * FROM $wpdb->options WHERE `option_name` LIKE 'msls_%'" );
            $lang = self::getLocale($site->blog_id);

            foreach ($options as $option){

                $name = str_replace('msls_', '', $option->option_name);

                $value = maybe_unserialize($option->option_value);

                if( is_array($value) && $target_id = $languages[$lang][$name]??false ){

                    $value[$current_lang] = $target_id;
                    update_option($option->option_name, $value);
                }
            }

            restore_current_blog();
        }
    }

    public function syncSettings(){

        $current_blog_id = get_current_blog_id();
        ?>
        <table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row"><label for="rewrite_page">Copy ids from</label></th>
                <td>
                    <select name="msls[blog_id]">
                        <option value=""></option>
                        <?php foreach (get_sites() as $site): ?>
                            <?php if($site->blog_id != $current_blog_id):?>
                                <option value="<?=$site->blog_id?>"><?=self::getLocale($site->blog_id)?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
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

                    $args['post_status'] = ['publish', 'pending', 'draft', 'future', 'private'];
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

                add_action( 'msls_admin_register', function ($class){

                    add_settings_section( 'sync', 'Sync', [ $this, 'syncSettings' ], $class );
                });

                $msls_options = get_blog_option( get_current_blog_id(), 'msls' );

                if( !empty($msls_options['blog_id']??'') )
                    $this->syncIds();
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

                if( $url && strpos($url, 'http') === false )
                    return null;

                return $url;
            }, 10, 2);
        }
    }
}
