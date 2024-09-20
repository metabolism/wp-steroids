<?php

/**
 * Class
 */
class WPS_Editor {

    private $config;


    /**
     * Allow non-breakable space
     * @param $init
     * @return array
     */
    public function tinyMceInit( $init )
    {
        $init['entities'] = '160,nbsp,38,amp,60,lt,62,gt';
        $init['entity_encoding'] = 'named';

        return $init;
    }

    /**
     * Configure Tiny MCE first line buttons
     * @param $mce_buttons
     * @return array
     */
    public function tinyMceButtons( $mce_buttons )
    {
        $mce_buttons = $this->config->get('mce_buttons', ['formatselect','bold','italic','underline','sup','strikethrough','bullist','numlist','blockquote','hr','alignleft',
            'aligncenter','alignright','alignjustify','link','unlink','wp_more','spellchecker','wp_adv','dfw']);

        return $mce_buttons;
    }

    /**
     * @return void
     */
    public function cptAtAGlance() {

        // Custom post types counts
        $post_types = get_post_types(['_builtin' => false, 'public'=> true], 'objects' );

        foreach ( $post_types as $post_type ) {
            $num_posts = wp_count_posts( $post_type->name );
            $num = number_format_i18n( $num_posts->publish );
            $text = _n( $post_type->labels->singular_name, $post_type->labels->name, $num_posts->publish );
            if ( current_user_can( 'edit_posts' ) ) {
                $num = '<li class="post-count dashicons-before '.$post_type->menu_icon.'"><a href="edit.php?post_type=' . $post_type->name . '">' . $num . ' ' . $text . '</a></li>';
            }
            echo $num;
        }
    }


    /**
     * Add quick link top bar archive button
     * @param $wp_admin_bar
     */
    public function editBarMenu($wp_admin_bar)
    {
        $object = get_queried_object();

        if( is_post_type_archive() && !is_admin() && current_user_can( $object->cap->edit_posts ) )
        {
            $args = [
                'id'    => 'edit',
                'title' => __t($object->labels->edit_items),
                'href'  => get_admin_url( null, '/edit.php?post_type='.$object->name ),
                'meta'   => ['class' => 'ab-item']
            ];

            $wp_admin_bar->add_node( $args );
        }

        global $pagenow;

        if( is_admin() && 'edit.php' === $pagenow && isset($_GET['post_type'], $_GET['page']) && $_GET['page'] == "options_".$_GET['post_type'] ){

            $object = get_post_type_object($_GET['post_type']);

            $args = [
                'id'    => 'archive',
                'title' => __t($object->labels->view_items),
                'href'  => get_post_type_archive_link( $_GET['post_type']),
                'meta'   => ['class' => 'ab-item']
            ];

            $wp_admin_bar->add_node( $args );
        }

        $wp_admin_bar->remove_node('themes');
        $wp_admin_bar->remove_node('updates');
        $wp_admin_bar->remove_node('wp-logo');

        if( in_array('edit-comments.php', (array)$this->config->get('remove_menu_page', [])) )
            $wp_admin_bar->remove_node('comments');
    }

    /**
     * Filter admin menu entries
     */
    public function adminMenu()
    {
        foreach ( (array)$this->config->get('remove_menu_page', []) as $menu )
        {
            remove_menu_page($menu);
        }

        foreach ( (array)$this->config->get('remove_submenu_page', []) as $menu=>$submenu )
        {
            if( is_array($submenu) )
                remove_submenu_page(key($submenu), reset($submenu));
            else
                remove_submenu_page($menu, $submenu);
        }

        if( !current_user_can('administrator') )
            remove_submenu_page('themes.php', 'themes.php');

        if( HEADLESS && !URL_MAPPING ){

            remove_submenu_page('options-general.php', 'options-reading.php');
            remove_submenu_page('options-general.php', 'options-permalink.php');
        }

        global $submenu;

        if ( isset( $submenu[ 'themes.php' ] ) )
        {
            foreach ( $submenu[ 'themes.php' ] as $index => $menu_item )
            {
                if ( in_array( 'customize', $menu_item ) )
                    unset( $submenu[ 'themes.php' ][ $index ] );
            }

            if( empty($submenu[ 'themes.php' ]) )
                remove_menu_page('themes.php');
        }
    }


    /**
     * Disable widgets
     */
    function disableDashboardWidgets()
    {
        remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );   // Incoming Links
        remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );          // Plugins
        remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );        // Quick Press
        remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );            // WordPress blog
        remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );          // Other WordPress News
        remove_action( 'welcome_panel', 'wp_welcome_panel' );                // Remove WordPress Welcome Panel
    }


    /**
     * add Custom css
     */
    function customAdminScripts()
    {
        wp_enqueue_script('wps-admin', WPS_PLUGIN_URL.'public/admin.js', ['jquery', 'jquery-ui-resizable'], WPS_VERSION, true);
        wp_enqueue_style('wps-admin-bar', WPS_PLUGIN_URL.'public/admin_bar.css', [], WPS_VERSION, false);
        wp_enqueue_style('wps-admin', WPS_PLUGIN_URL.'public/admin.css', [], WPS_VERSION, false);

        $object = [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'enable_translation' => false
        ];

        if( !is_main_site() ){

            if( defined('GOOGLE_TRANSLATE_KEY') && GOOGLE_TRANSLATE_KEY )
                $object['enable_translation'] = "google";
            elseif( defined('DEEPL_KEY') && DEEPL_KEY )
                $object['enable_translation'] = "deepl";
        }

        wp_localize_script( 'wps-admin', 'wps', $object );
    }


    /**
     * add Custom css
     */
    function addCustomLoginHeader()
    {
        echo '<link rel="stylesheet" href="'.WPS_PLUGIN_URL.'public/login.css"/>';
    }

    /**
     * Update editor role
     */
    public function adminInit()
    {
        $role_object = get_role( 'editor' );

        if( !$role_object->has_cap('edit_theme_options') )
            $role_object->add_cap( 'edit_theme_options' );

    }

    /**
     * @param $actions
     * @param $post
     * @return mixed
     */
    public function rowActions($actions, $post ) {

        $post_type_object = get_post_type_object(get_post_type($post));

        if (!$post_type_object->query_var && !$post_type_object->_builtin)
            unset($actions['view']);

        return $actions;
    }

    /**
     * @param $results
     * @param $query
     * @return mixed
     */
    function linkQueryTermLinking($results, $query ) {

        if( !($query['s']??false) || ($query['offset']??0) )
            return $results;

        $taxonomies = get_taxonomies(['publicly_queryable' => true]);

        $terms = get_terms( $taxonomies, [
            'name__like' => $query['s'],
            'number'     => 20,
            'hide_empty' => true,
        ]);

        $charset = get_bloginfo('charset');

        //* Terms
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ):
            foreach( $terms as $term ):
                $results[] = [
                    'ID'        => 'term-' . $term->term_id,
                    'title'     => html_entity_decode($term->name, ENT_QUOTES, $charset) ,
                    'permalink' => get_term_link($term->term_id , $term->taxonomy) ,
                    'info'      => get_taxonomy($term->taxonomy)->labels->singular_name,
                ];
            endforeach;
        endif;

        $match = '/' . remove_accents( $query['s'] ) . '/i';

        foreach( $query['post_type'] as $post_type ) :

            $pt_archive_link = get_post_type_archive_link($post_type);
            $pt_obj = get_post_type_object($post_type);

            if ( $pt_archive_link !== false && $pt_obj->has_archive !== false ) : // Add only post type with 'has_archive'
                if ( preg_match( $match, remove_accents( $pt_obj->labels->name ) ) > 0 ) :
                    $results[] = [
                        'ID' => $pt_obj->has_archive,
                        'title' => trim( esc_html( strip_tags($pt_obj->labels->name) ) ) ,
                        'permalink' => $pt_archive_link,
                        'info' => 'Archive'
                    ];
                endif;
            endif; //end post type archive links in link_query
        endforeach;

        return $results;
    }

    /**
     * @param $mimes
     * @return mixed
     */
    public function uploadMimes($mimes )
    {
        $mimes['json'] = 'text/plain';

        return $mimes;
    }

    /**
     * @param $classes
     * @return mixed
     */
    public function addBodyClass($classes)
    {
        $data = get_userdata( get_current_user_id() );

        $caps = [];

        foreach($data->allcaps as $cap=>$value)
            $caps[] = $value ? $cap : 'no-'.$cap;

        $roles = $this->config->get('role', []);

        foreach ($data->roles as $role){

            if( $capabilities = $roles[$role]['capabilities']??false ){

                foreach ($capabilities as $cap=>$value)
                    $caps[] = $value ? $cap : 'no-'.$cap;
            }
        }

        $caps = array_unique($caps);

        global $post;
        $current_screen = get_current_screen();

        if($current_screen->base === 'post' && $post)
            $classes .= ' single-'.$post->post_type;

        return implode(' ', $caps).$classes.(HEADLESS?' headless':'').(URL_MAPPING?' url-mapping':'');
    }

    /**
     * @return void
     */
    public function add_meta_boxes($post_type)
    {
        if( current_user_can( 'edit_others_posts' ) && post_type_supports( $post_type, 'sticky' ) ){

            add_meta_box('wps_settings', __('Settings'), function($post) {

                if ( current_user_can( 'edit_others_posts' ) && post_type_supports( $post->post_type, 'sticky' ) ) {

                    $sticky_checkbox_checked = is_sticky( $post->post_id ) ? 'checked="checked"' : '';
                    echo '<span id="sticky-span" style="margin-left:0"><input id="sticky" name="sticky" type="checkbox" value="sticky" ' . $sticky_checkbox_checked . ' /> <label for="sticky" class="selectit">' . __( 'Make this post sticky' ) . '</label><br /></span>';
                }
            } , null, 'side', 'high');
        }
    }

    /**
     * Editor constructor.
     */
    public function __construct()
    {
        global $_config;

        $this->config = $_config;

        add_action( 'wp_before_admin_bar_render', function() {
            global $wp_admin_bar;
            $wp_admin_bar->remove_menu('customize');
        } );

        if( is_admin() )
        {

            add_filter( 'upload_mimes', [$this, 'uploadMimes']);
            add_filter( 'wp_link_query', [$this, 'linkQueryTermLinking'], 99, 2 );
            add_filter( 'mce_buttons', [$this, 'tinyMceButtons']);
            add_filter( 'post_row_actions', [$this, 'rowActions'], 10, 2);
            add_filter( 'page_row_actions', [$this, 'rowActions'], 10, 2);
            add_filter( 'admin_body_class', [$this, 'addBodyClass']);
            add_filter( 'tiny_mce_before_init', [$this,'tinyMceInit']);

            add_action( 'add_meta_boxes', [$this, 'add_meta_boxes'] );
            add_action( 'admin_menu', [$this, 'adminMenu'], 99);
            add_action( 'wp_dashboard_setup', [$this, 'disableDashboardWidgets']);
            add_action( 'admin_print_footer_scripts', [$this, 'customAdminScripts']);
            add_action( 'admin_init', [$this, 'adminInit'] );
            add_action( 'dashboard_glance_items', [$this, 'cptAtAGlance'] );
        }

        add_action('init', function (){

            if( is_admin_bar_showing() )
                wp_enqueue_style('wp_steroid_adminbar', WPS_PLUGIN_URL.'public/admin_bar.css');

        }, 99);

        add_action( 'password_protected_login_head', [$this, 'addCustomLoginHeader']);
        add_action( 'login_head', [$this, 'addCustomLoginHeader']);
        add_action( 'admin_bar_menu', [$this, 'editBarMenu'], 80);
    }
}
