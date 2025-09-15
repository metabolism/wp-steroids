<?php

use Dflydev\DotAccessData\Data;

/**
 * Class
 */
class WPS_Security {

    private $config;

    /**
     * hide dashboard update notices
     */
    public function hideUpdateNotice()
    {
        if (!current_user_can('update_core'))
            remove_action( 'admin_notices', 'update_nag', 3 );
    }

    /**
     * Filter explicit wp class
     * @param $classes
     * @return array
     */
    public function filterClass( $classes )
    {
        $to_remove = ['wp-singular'];
        $to_remove[] = 'wp-theme-' . sanitize_html_class( get_template() );

        if ( is_child_theme() )
            $to_remove[] = 'wp-child-theme-' . sanitize_html_class( get_stylesheet() );

        return array_diff($classes, $to_remove);
    }

    /**
     * Allow iframe for editor in WYSIWYG
     * @param $caps
     * @param $cap
     * @param $user_id
     * @return array
     */
    public function mapMetaCap( $caps, $cap, $user_id )
    {
        if ( !is_user_logged_in() ) return $caps;

        if ( 'unfiltered_html' === $cap ){

            if( $this->config->get('security.unfiltered_html', false) )
                $caps = ['unfiltered_html'];
        }

        if ('manage_privacy_options' === $cap && current_user_can('edit_others_pages') ) {

            $manage_name = is_multisite() ? 'manage_network' : 'manage_options';
            $caps = array_diff($caps, [ $manage_name ]);
        }

        return $caps;
    }


    /**
     * Clean WP Footer
     */
    public function cleanFooter()
    {
        wp_deregister_script( 'wp-embed' );
    }


    /**
     * Clean filename
     * @param $file
     * @return mixed
     */
    function cleanFilename($file) {

        $input = ['ß', '·'];
        $output = ['ss', '.'];

        if($file && isset($file['name'])){
            $path = pathinfo($file['name']);
            $new_filename = preg_replace('/.' . $path['extension'] . '$/', '', $file['name']);
            $new_filename = preg_replace('/-([0-9]+x[0-9]+)$/', '', $new_filename);
            $new_filename = str_replace( $input, $output, $new_filename );
            $file['name'] = sanitize_title($new_filename) . '.' . $path['extension'];
        }

        return $file;
    }


    /**
     * Recursive chown
     * @param string $dir
     * @param string $user
     */
    private function rchown($dir, $user)
    {
        $dir = rtrim($dir, "/");
        if ($items = glob($dir . "/*")) {
            foreach ($items as $item) {
                if (is_dir($item)) {
                    $this->rchown($item, $user);
                } else {
                    @chown($item, $user);
                }
            }
        }

        @chown($dir, $user);
    }


    /**
     * Try to fix permissions
     * @param string $type
     */
    private function permissions($type='all')
    {
        $uploads = wp_upload_dir();
        $upload_dir = $uploads['path'];

        if ( current_user_can('administrator') ) {

            $webuser = posix_getpwuid(posix_geteuid())['name'];
            $this->rchown($upload_dir, $webuser);
        }

        wp_redirect( get_admin_url(null, 'options-media.php' ));

        exit;
    }


    /**
     * Clean WP Head
     */
    public function cleanHeader()
    {
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3 );
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'print_emoji_detection_script', 7 );
        remove_action('wp_print_styles', 'print_emoji_styles' );
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('wp_head', 'wp_resource_hints', 2 );
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('template_redirect', 'rest_output_link_header', 11 );
        remove_action('template_redirect', 'wp_shortlink_header', 11 );

        add_filter('wp_headers', function($headers) {

            if(isset($headers['X-Pingback']))
                unset($headers['X-Pingback']);

            if(isset($headers['X-Powered-By']))
                unset($headers['X-Powered-By']);

            if(isset($headers['Server']))
                unset($headers['Server']);

            return $headers;
        });
    }


    /**
     * change admin footer text
     */
    public function adminFooterText($text)
    {
        if( defined('WP_PROXY_HOST') && WP_PROXY_HOST ){

            $text .= '<span id="footer-proxy">';

            $text .= 'Using proxy '.WP_PROXY_HOST;

            if( defined('WP_PROXY_PORT') && WP_PROXY_PORT )
                $text .= ':'.WP_PROXY_PORT;

            $text .= '</span>';
        }

        return $text;
    }


    /**
     * add admin parameters
     */
    public function adminInit()
    {
        if( !current_user_can('administrator') )
            return;

        if( isset($_GET['permissions']) )
            $this->permissions($_GET['type'] ?? 'all');

        add_settings_field('fix_permissions', __('Permissions', 'wp-steroids'), function(){

            echo '<a class="button button-primary" href="'.get_admin_url().'?permissions&type=uploads">'.__('Try to fix it', 'wp-steroids').'</a>';

        }, 'media');
    }


    /**
     * add admin parameters
     */
    public function redirect()
    {
        wp_redirect(get_home_url());
        exit;
    }


    /**
     * Disable WordPress auto update and check
     */
    protected function disableUpdate(){

        remove_action( 'admin_init', '_maybe_update_core' );
        remove_action( 'wp_version_check', 'wp_version_check' );
        remove_action( 'load-plugins.php', 'wp_update_plugins' );
        remove_action( 'load-update.php', 'wp_update_plugins' );
        remove_action( 'load-update-core.php', 'wp_update_plugins' );
        remove_action( 'admin_init', '_maybe_update_plugins' );
        remove_action( 'wp_update_plugins', 'wp_update_plugins' );
        remove_action( 'load-themes.php', 'wp_update_themes' );
        remove_action( 'load-update.php', 'wp_update_themes' );
        remove_action( 'load-update-core.php', 'wp_update_themes' );
        remove_action( 'admin_init', '_maybe_update_themes' );
        remove_action( 'wp_update_themes', 'wp_update_themes' );
        remove_action( 'update_option_WPLANG', 'wp_clean_update_cache' );
        remove_action( 'wp_maybe_auto_update', 'wp_maybe_auto_update' );
        remove_action( 'init', 'wp_schedule_update_checks' );

        add_filter( 'plugins_auto_update_enabled', '__return_false' );
    }

    /**
     * @return void
     */
    protected function configureRest(){

        if( $remove_core = $this->config->get('security.rest_api.remove_core', false) ) {

            $core_endpoints = is_array($remove_core) ? $remove_core : [
                'menu-items','blocks','templates','global-styles','navigation','font-families','statuses',
                'taxonomies','menus','wp_pattern_category','users','comments','search','block-renderer','block-types',
                'settings','themes','plugins','sidebars','widget-types','widgets','block-directory','pattern-directory',
                'block-patterns','menu-locations','font-collections','template-parts'
            ];

            $core_endpoints = array_map(function ($endpoint) { return '/wp/v2/'.$endpoint; }, $core_endpoints);

            add_filter( 'rest_endpoints', function( $routes ) use( $core_endpoints ) {

                foreach ( array_keys( $routes ) as $endpoint ) {

                    if( !str_starts_with($endpoint, '/wp/v2') and $endpoint != '/' ){

                        unset($routes[$endpoint]);
                    }
                    else{

                        foreach ( $core_endpoints as $core_endpoint ) {

                            if (0 === strpos($endpoint, $core_endpoint) ) {
                                unset($routes[$endpoint]);
                                break;
                            }

                            $patterns = ['/autosaves', '/revisions', '/post-process', '/edit',];

                            foreach ($patterns as $pattern) {

                                if ( strpos($endpoint, $pattern) ) {
                                    unset($routes[$endpoint]);
                                    break;
                                }
                            }
                        }
                    }
                }

                return $routes;
            });
        }

        if( $this->config->get('security.rest_api.ip_whitelist', false) ){

            add_action('rest_api_init', function() {

                if( !is_admin() ) {

                    $whitelist = array_filter(explode(',', $_ENV['REST_API_IP_WHITELIST']??''));
                    $whitelist = array_merge($whitelist, [ '127.0.0.1', "::1" ]);

                    if( ! in_array($_SERVER['REMOTE_ADDR'], $whitelist ) )
                        wp_send_json_error('REST API access has been restricted for security reasons. Ensure that your IP is whitelisted ('.$_SERVER['REMOTE_ADDR'].')');
                }
            });
        }
    }

    /**
     * Disable json and jsonp
     * @return void
     */
    protected function disableRest()
    {
        add_filter( 'rest_jsonp_enabled', '__return_false');

        add_filter( 'rest_authentication_errors', function( $result ) {

            if ( true === $result || is_wp_error( $result ) )
                return $result;

            if ( ! is_user_logged_in() )
                return new wp_error('restricted_rest_api_access','REST API access has been restricted for security reasons.');

            return $result;
        });
    }


    /**
     * SecurityPlugin constructor.
     */
    public function __construct()
    {
        /* @var Data $_config */
        global $_config;

        $this->config = $_config;

        //remove explicit wp class
        add_filter( 'body_class', [$this, 'filterClass'], 10, 2 );

        //prevent .htaccess writing
        add_filter( 'flush_rewrite_rules_hard', '__return_false');

        //prevent admin redirect from unknown url
        remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );

        //hide login error
        if( !WP_DEBUG ){

            add_filter( 'login_errors', function(){
                return __('Something is wrong!', 'wp-steroids');
            } );
        }

        if( $this->config->get('security.disable_update', true) )
            $this->disableUpdate();

        add_filter( 'map_meta_cap', [$this, 'mapMetaCap'], 1, 3 );
        add_filter( 'x_redirect_by', '__return_false' );

        if( is_admin() )
        {
            add_filter( 'admin_footer_text', [$this, 'adminFooterText'] );
            add_action( 'admin_init', [$this, 'adminInit'] );
            add_action( 'wp_handle_upload_prefilter', [$this, 'cleanFilename']);
            add_filter( 'update_right_now_text', '__return_empty_string' );
            add_action( 'admin_head', [$this, 'hideUpdateNotice'], 1 );
        }
        else
        {
            if( !$this->config->get('security.rest_api', false) )
                $this->disableRest();
            else
                $this->configureRest();

            if( !$this->config->get('security.xmlrpc', false) )
                add_filter( 'xmlrpc_enabled', '__return_false' );

            if( !$this->config->get('security.pings', false) )
                add_filter( 'pings_open', '__return_false');

            foreach (['html', 'xhtml', 'atom', 'rss2', 'rdf', 'comment', 'export'] as $type )
                add_filter( 'get_the_generator_'.$type, '__return_empty_string' );

            add_action( 'after_setup_theme', [$this, 'cleanHeader']);
            add_action( 'wp_footer', [$this, 'cleanFooter']);

            add_filter( 'robots_txt', '__return_empty_string' );
            add_filter( 'wp_speculation_rules_configuration', '__return_null' );

            remove_all_actions('do_favicon');

            add_action('init', function()
            {
                if ( isset($_GET['author']) )
                    $this->redirect();

                global $wp_rewrite;

                $wp_rewrite->feeds = array();
            });
        }
    }
}
