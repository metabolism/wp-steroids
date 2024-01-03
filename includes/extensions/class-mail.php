<?php


/**
 * Class
 */
class WPS_Mail {

	/**
	 *
	 */
	public function emailSenderRegister(){

        // register a new section
        add_settings_section('wps_email_sender_settings_section', __('Emails', 'wp-steroids'), function (){

            printf('%s %s %s', '<p>', __('WordPress Default Mail Sender Name and Email Address', 'wp-steroids'), '</p>');

        }, 'general');

        // register a new field in the "wps_email_sender_settings_section" section
        add_settings_field('wps_email_sender_name', __('Sender Name','wp-steroids'), function (){

            $wps_email_sender_name = get_option('wps_email_sender_name');
            printf('<input name="wps_email_sender_name" type="text" class="regular-text" value="%s" placeholder="%s"/>', $wps_email_sender_name, __('Wordpress', 'wp-steroids'));

        }, 'general', 'wps_email_sender_settings_section');

        // register a new setting for sender name field
        register_setting('general', 'wps_email_sender_name');

        // register a new field in the "wps_email_sender_settings_section" section
        add_settings_field('wps_sender_email_address', __('Sender Email', 'wp-steroids'), function (){

            $sitename = wp_parse_url( network_home_url(), PHP_URL_HOST );
            $from_email = 'wordpress@'.$sitename;

            $wps_sender_email_address = get_option('wps_sender_email_address');
            printf('<input name="wps_sender_email_address" type="email" class="regular-text" value="%s" placeholder="'.$from_email.'"/>', $wps_sender_email_address);

        }, 'general', 'wps_email_sender_settings_section');

        // register a new setting for email address field
        register_setting('general', 'wps_sender_email_address');
    }

    /**
     * @param $email_address
     * @return mixed
     */
    function senderFromEmail($email_address) {

        if( $wps_sender_email_address = get_option('wps_sender_email_address') )
            return $wps_sender_email_address;

        return $email_address;

    }

    /**
     * @param $name
     * @return mixed
     */
    function senderFromName($name) {

        if( $wps_email_sender_name = get_option('wps_email_sender_name') )
            return $wps_email_sender_name;

        return $name;
    }

	/**
	 * WPS_Mail constructor.
	 */
	public function __construct(){

        add_action('admin_init', [$this, 'emailSenderRegister']);
        
        /**
         * Change Wordpress Default Mail Sender Name and email
         */
        add_filter('wp_mail_from', [$this, 'senderFromEmail'], 9);
        add_filter('wp_mail_from_name', [$this, 'senderFromName'], 9);
    }
}
