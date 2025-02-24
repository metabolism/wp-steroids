<?php

use Timber\Timber;

/**
 * Class
 *
 * @package
 */
class WPS_Timber
{
    /**
     * Construct
     */
    public function __construct()
    {
        if (!class_exists('Timber\Timber'))
            return;

        add_action('init', function (){

            if( !class_exists('WPS_Post') )
                include_once WPS_PATH.'/includes/class/Post.php';

            add_filter('timber/post/class', function () {

                return WPS_Post::class;
            });

            if( !class_exists('WPS_Menu_Item') )
                include_once WPS_PATH.'/includes/class/MenuItem.php';

            add_filter('timber/menuitem/class', function () {

                return WPS_Menu_Item::class;
            });
        });
    }
}
