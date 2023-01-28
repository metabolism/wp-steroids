<?php

/**
 * Class
 */
class WPS_Editor {

    private $config;


    /**
     * Configure Tiny MCE first line buttons
     * @param $mce_buttons
     * @return array
     */
    public function TinyMceButtons( $mce_buttons )
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

        if( is_post_type_archive() && !is_admin() )
        {
            $args = [
                'id'    => 'edit',
                'title' => __t('Edit '.$object->label),
                'href'  => get_admin_url( null, '/edit.php?post_type='.$object->name ),
                'meta'   => ['class' => 'ab-item']
            ];

            $wp_admin_bar->add_node( $args );

            if( $this->config->get('post_type.'.$object->name.'.has_options') ){

                $args = [
                    'id'    => 'archive_options',
                    'title' => __t('Edit archive options'),
                    'href'  => get_admin_url( null, '/edit.php?post_type='.$object->name.'&page=options_'.$object->name ),
                    'meta'   => ['class' => 'ab-item']
                ];

                $wp_admin_bar->add_node( $args );
            }
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

        if( in_array('edit-comments.php', $this->config->get('remove_menu_page', [])) )
            $wp_admin_bar->remove_node('comments');
    }

    /**
     * Filter admin menu entries
     */
    public function adminMenu()
    {
        foreach ( $this->config->get('remove_menu_page', []) as $menu )
        {
            remove_menu_page($menu);
        }

        foreach ( $this->config->get('remove_submenu_page', []) as $menu=>$submenu )
        {
            remove_submenu_page($menu, $submenu);
        }

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
    function addCustomAdminHeader()
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
            add_filter('mce_buttons', [$this, 'TinyMceButtons']);
            add_action('admin_menu', [$this, 'adminMenu']);
            add_action('wp_dashboard_setup', [$this, 'disableDashboardWidgets']);
            add_action('admin_head', [$this, 'addCustomAdminHeader']);
            add_action('admin_init', [$this, 'adminInit'] );
            add_action( 'dashboard_glance_items', [$this, 'cptAtAGlance'] );

            add_filter( 'post_row_actions', [$this, 'rowActions'], 10, 2);
            add_filter( 'page_row_actions', [$this, 'rowActions'], 10, 2);

            add_filter('admin_body_class', function ( $classes ) {

                $data = get_userdata( get_current_user_id() );
                $caps = [];

                foreach($data->allcaps as $cap=>$value)
                    $caps[] = $value ? $cap : 'no-'.$cap;

                return implode(' ', $caps).$classes.(HEADLESS?' headless':'').(URL_MAPPING?' url-mapping':'');
            });
        }

        add_action('init', function (){

            if( is_admin_bar_showing() )
                wp_enqueue_style('wp_steroid_adminbar', WPS_PLUGIN_URL.'public/admin_bar.css');
        });


        add_action( 'password_protected_login_head', [$this, 'addCustomLoginHeader']);
        add_action( 'login_head', [$this, 'addCustomLoginHeader']);
        add_action( 'admin_bar_menu', [$this, 'editBarMenu'], 80);
    }
}
