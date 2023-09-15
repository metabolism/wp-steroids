<?php


/**
 * Class
 */
class WPS_Notice {
    
    /**
     * Check symlinks and folders
     */
    public function adminNotices(){
        
        $currentScreen = get_current_screen();
        
        if( !current_user_can('administrator') || $currentScreen->base != 'dashboard' )
            return;
        
        global $wpdb, $table_prefix;
        
        if( ($_GET['fix']??false) == 'database' ){
            
            $wpdb->update($table_prefix."options", ['option_value' => WP_SITEURL], ['option_name' => 'siteurl']);
            $wpdb->update($table_prefix."options", ['option_value' => WP_HOME], ['option_name' => 'home']);
        }
        
        $notices = [];
        $errors = [];
        
        $siteurl = $wpdb->get_var("SELECT option_value FROM `".$table_prefix."options` WHERE `option_name` = 'siteurl'");
        $homeurl = $wpdb->get_var("SELECT option_value FROM `".$table_prefix."options` WHERE `option_name` = 'home'");
        
        if( str_replace('/edition','', $siteurl) !== str_replace('/edition','', $homeurl) )
            $errors[] = 'Site url host and Home url host are different, please check your database configuration';
        
        if( strpos($homeurl, '/edition' ) !== false )
            $errors[] = 'Home url must not contain /edition, please check your database configuration';
        
        if( strpos($homeurl, WP_HOME ) === false )
            $errors[] = 'Home url host is different from current host, please check your database configuration';
        
        if( strpos($siteurl, WP_HOME ) === false )
            $errors[] = 'Site url host is different from current host, please check your database configuration';
        
        if( !empty($errors))
            $errors[] = '<a href="?fix=database">Fix database now</a>';
        
        if( !empty($errors) )
            echo '<div class="error"><p>'.implode('<br/>', $errors ).'</p></div>';
        
        if( !empty($notices) )
            echo '<div class="updated"><p>'.implode('<br/>', $notices ).'</p></div>';
    }
    
    
    /**
     * Add debug info
     */
    public function debugInfo(){
        
        add_action( 'admin_bar_menu', function( $wp_admin_bar )
        {
            $args = [
                'id'    => 'debug',
                'title' => '<span style="position: fixed; left: 0; top: 0; width: 100%; background: #df0f0f; height: 2px; z-index: 99999"></span>',
                'href' => '#'
            ];
            
            $wp_admin_bar->add_node( $args );
            
        }, 9999 );
    }
    
    
    /**
     * NoticePlugin constructor.
     */
    public function __construct()
    {
        if( is_admin() )
        {
            add_action( 'admin_notices', [$this, 'adminNotices']);
            
            if( WP_DEBUG )
                add_action( 'init', [$this, 'debugInfo']);
        }
    }
}
