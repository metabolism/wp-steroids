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
        if ( current_user_can('administrator') ) {
            $webuser = posix_getpwuid(posix_geteuid())['name'];
            $this->rchown(WP_UPLOADS_DIR, $webuser);
        }

        wp_redirect( get_admin_url(null, 'options-media.php' ));
        exit;
    }


    /**
     * Clean WP Head
     */
    public function cleanHeader()
    {
        global $_config;

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
     * SecurityPlugin constructor.
     */
    public function __construct()
    {
        /* @var Data $_config */
        global $_config;

        $this->config = $_config;

        //prevent .htaccess writing
        add_filter( 'flush_rewrite_rules_hard', '__return_false');

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
            add_action( 'admin_init', [$this, 'adminInit'] );
            add_action( 'wp_handle_upload_prefilter', [$this, 'cleanFilename']);
            add_filter( 'update_right_now_text', '__return_empty_string' );
            add_action( 'admin_head', [$this, 'hideUpdateNotice'], 1 );
        }
        else
        {
            if( !$this->config->get('security.rest_api', false) ){

                add_filter('rest_jsonp_enabled', '__return_false');

                add_filter( 'rest_authentication_errors', function( $result ) {

                    if ( true === $result || is_wp_error( $result ) )
                        return $result;

                    if ( ! is_user_logged_in() )
                        return new wp_error('restricted_rest_api_access','Rest API access have been restricted for security reasons');

                    return $result;
                });
            }

            if( !$this->config->get('security.xmlrpc', false) )
                add_filter( 'xmlrpc_enabled', '__return_false' );

            if( !$this->config->get('security.pings', false) )
                add_filter( 'pings_open', '__return_false');

            foreach (['html', 'xhtml', 'atom', 'rss2', 'rdf', 'comment', 'export'] as $type )
                add_filter( 'get_the_generator_'.$type, '__return_empty_string' );

            add_action( 'after_setup_theme', [$this, 'cleanHeader']);
            add_action( 'wp_footer', [$this, 'cleanFooter']);
            add_filter( 'robots_txt', '__return_empty_string' );

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
