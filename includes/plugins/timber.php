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

            $post_types = get_post_types(['public' => true]);
            unset($post_types['attachment']);

            $custom_classmap = array_fill_keys($post_types, WPS_Post::class);

            add_filter('timber/post/classmap', function ($classmap) use($custom_classmap) {

                return array_merge($classmap, $custom_classmap);
            });
        });
    }
}
