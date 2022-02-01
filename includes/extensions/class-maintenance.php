<?php

use Dflydev\DotAccessData\Data;


/**
 * Class 
 */
class WPS_Maintenance {

    /**
     * Add maintenance button and checkbox
     */
    public function addMaintenanceMode()
    {
        if( !current_user_can('editor') && !current_user_can('administrator') )
            return;

        if( is_admin() )
        {
            add_action( 'admin_init', function(){

                add_settings_field('maintenance_field', __('Maintenance'), function(){

                    echo '<input type="checkbox" id="maintenance_field" name="maintenance_field" value="1" ' . checked( 1, get_option('maintenance_field'), false ) . ' />'.__('Enable');

                }, 'general');

                register_setting('general', 'maintenance_field');
            });
        }

        if( wp_maintenance_mode(true) ){

            add_action( 'admin_bar_menu', function( $wp_admin_bar )
            {
                $args = [
                    'id' => 'maintenance',
                    'title' => '<span style="position: fixed; left: 0; top: 0; width: 100%; background: #ff7600; height: 2px; z-index: 99999"></span>'.__('Maintenance'),
                    'href'  => get_admin_url( null, '/options-general.php#maintenance_field' )
                ];

                $wp_admin_bar->add_node( $args );

            }, 999 );
        }

    }

    /**
     * MaintenancePlugin constructor.
     */
    public function __construct()
    {
       global $_config;

        if( $_config->get('maintenance', true) )
            add_action( 'init', [$this, 'addMaintenanceMode']);
    }
}



/**
 * @param bool $strict
 * @return bool
 */
function wp_maintenance_mode($strict = false)
{
    if ($strict)
        return get_option('maintenance_field', false);
    else
        return !current_user_can('editor') && !current_user_can('administrator') && get_option('maintenance_field', false);
}
