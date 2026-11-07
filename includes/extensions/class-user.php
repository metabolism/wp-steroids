<?php


/**
 * Class
 */
class WPS_User {

    private $config;

    function render_custom_user_profile_css() {

        $styles = [];

        $styles[] = '.user-keyboard-shortcuts-wrap { display: none !important; }';

        if ( !$this->config->get('user_profile.keyboard_shortcuts', false) )
            $styles[] = '.user-user-login-wrap .description{ display: none !important; }';

        if ( !$this->config->get('user_profile.app_passwords', false) )
            $styles[] = '#application-passwords-section { display: none !important; }';

        if ( !$this->config->get('user_profile.admin_bar_option', false) )
            $styles[] = '.show-admin-bar { display: none !important; }';

        if ( !$this->config->get('user_profile.comment_shortcuts', false) )
            $styles[] = '.user-comment-shortcuts-wrap { display: none !important; }';

        $contact_methods = $this->config->get('user_profile.contact_methods', []);

        if( !is_array($contact_methods) )
            $contact_methods = [];

        if ( !in_array('url', $contact_methods) )
            $styles[] = '.user-url-wrap { display: none !important; }';

        if (!empty($styles))
            echo '<style>' . implode(' ', $styles) . '</style>';
    }

    /**
     * User constructor.
     */
    public function init(){

        if ( !$this->config->get('user_profile.color_scheme', false) )
            remove_action('admin_color_scheme_picker', 'admin_color_scheme_picker');

        if ( $this->config->get('user_profile.syntax_highlighting', false) ){

            add_filter('user_can_richedit', '__return_true');

            add_action('admin_enqueue_scripts', function() {
                wp_deregister_script('code-editor');
            });
        }

        add_filter('user_contactmethods', function($methods) {

            $contact_methods = $this->config->get('user_profile.contact_methods', []);

            if( !is_array($contact_methods) )
                $contact_methods = [];

            foreach ($methods as $method=>$title) {

                if( !in_array($method, $contact_methods) )
                    unset($methods[$method]);
            }

            return $methods;
        }, 99);

        add_action('admin_head-user-edit.php', [$this, 'render_custom_user_profile_css']);
        add_action('admin_head-profile.php', [$this, 'render_custom_user_profile_css']);
    }

    /**
     * Handle Actions
     * https://codex.wordpress.org/Function_Reference/add_role
     */
    public function handleActions(){

        if( !is_admin() || !current_user_can('administrator') )
            return;

        $action = $_GET['action'] ?? '';

        if (!in_array($action, ['wps_user_reload', 'wps_user_populate'], true))
            return;

        if (!check_admin_referer($action))
            return;

        if( $action == 'wps_user_populate' ){

            if ( !function_exists( 'populate_roles' ) )
                require_once( ABSPATH . 'wp-admin/includes/schema.php' );

            populate_roles();
        }

        if( $action == 'wps_user_reload' ){

            foreach ( $this->config->get('role', []) as $role => $args ) {

                remove_role($role);

                if( !empty($args['inherit']??'') ){

                    $inherited_role = get_role( $args['inherit'] );
                    $args['capabilities'] = array_merge($inherited_role->capabilities, $args['capabilities']??[]);
                }

                add_role($role, $args['display_name'], $args['capabilities']);
            }
        }

        if( str_starts_with($_GET['action']??'', 'wps_user_') ){

            wp_redirect(admin_url('users.php'));
            exit;
        }
    }

    public function roleToolbarButtons($toolbar)
    {
        global $pagenow;

        if ($pagenow !== 'users.php' || !current_user_can('administrator'))
            return;

        $actions = [
            'reload' => 'Reload user roles',
            'populate' => 'Populate roles',
        ];

        foreach ($actions as $operation => $label) {
            $toolbar->add_node([
                'id' => 'wps-roles-'.$operation,
                'title' => $label,
                'href' => add_query_arg([
                    'action' => 'wps_user_'.$operation,
                    '_wpnonce' => wp_create_nonce('wps_user_'.$operation),
                    'operation' => $operation,
                ], admin_url('users.php')),
            ]);
        }
    }

    public function __construct(){

        global $_config;

        $this->config = $_config;

        add_action('admin_bar_menu', [$this, 'roleToolbarButtons'], 100);
        add_action('admin_init', [$this, 'init'] );
        add_action('init', [$this, 'handleActions']);

        add_filter('editable_roles', function($all_roles){

            if( !current_user_can('administrator') )
                unset($all_roles['administrator']);

            return $all_roles;
        });
    }
}