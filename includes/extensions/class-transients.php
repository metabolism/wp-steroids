<?php

use Ifsnop\Mysqldump as IMysqldump;

/**
 * Class
 */
class WPS_Transients {

    public function clean($type){

        if( !in_array($type, ['feed', 'browser', 'wp_remote_block_patterns']) )
            return;

        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_{$type}_%' OR option_name LIKE '_transient_timeout_{$type}_%'");
        $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_{$type}_%' OR meta_key LIKE '_site_transient_timeout_{$type}_%'");
    }

    /**
     * add admin parameters
     */
    public function adminInit()
    {
        if( !current_user_can('administrator') )
            return;

        if( isset($_GET['clean_transients']) )
            $this->clean($_GET['clean_transients']);

        add_settings_field('clean_transients', __('Transients', 'wp-steroids'), function(){

            echo '<a class="button button-primary" href="'.get_admin_url(null, 'options-general.php').'?clean_transients=feed">'.__('Clean feed', 'wp-steroids').'</a> ';
            echo '<a class="button button-primary" href="'.get_admin_url(null, 'options-general.php').'?clean_transients=browser">'.__('Clean browser', 'wp-steroids').'</a> ';
            echo '<a class="button button-primary" href="'.get_admin_url(null, 'options-general.php').'?clean_transients=wp_remote_block_patterns">'.__('Clean remove block patterns', 'wp-steroids').'</a> ';

        }, 'general');
    }


    /**
     * Constructor
     */
    public function __construct()
    {
        if( !is_admin() || !WP_DEBUG )
            return;

        add_action( 'admin_init', [$this, 'adminInit'] );
    }
}

