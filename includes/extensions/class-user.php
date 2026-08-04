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

    public function __construct(){

        global $_config;

        $this->config = $_config;

        add_action( 'admin_init', [$this, 'init'] );
    }
}
