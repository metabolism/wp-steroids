<?php

/**
 * Class
 */
class WPS_Toolbar {


    /**
     * Add a maintenance button and checkbox
     */
    public function addLinks()
    {
        if( !current_user_can('editor') && !current_user_can('administrator') )
            return;

        if( defined('WP_ADMIN_LINKS') && WP_ADMIN_LINKS ){

            add_action( 'admin_bar_menu', function( $wp_admin_bar )
            {
                $links = explode(',', WP_ADMIN_LINKS);
                $title = defined('WP_ADMIN_LINKS_TITLE')?WP_ADMIN_LINKS_TITLE:__('Quick links', 'wp-steroids');

                $args = [
                    'id'    => 'quick_links',
                    'title' => '<span class="ab-icon"></span>'.$title,
                ];

                $wp_admin_bar->add_node( $args );

                foreach( $links as $link ){

                    $link = explode('|', trim($link));

                    $args = [
                        'parent' => 'quick_links',
                        'id' => md5($link[0]),
                        'title' => trim($link[1]??$link[0]),
                        'href' => trim($link[0]),
                        'meta' => [
                            'target' => '_blank'
                        ]
                    ];

                    $wp_admin_bar->add_node($args);
                }

            }, 999 );
        }
    }

    /**
     * Add a maintenance button and checkbox
     */
    public function addBuildButton()
    {
        if( !current_user_can('editor') && !current_user_can('administrator') )
            return;

		if( defined('WP_BUILD_HOOK') && WP_BUILD_HOOK ){

			add_action( 'admin_bar_menu', function( $wp_admin_bar )
			{
                $build_hook_url = apply_filters('wps_build_hook_url', WP_BUILD_HOOK);

                $build_hook_message = defined('WP_BUILD_MESSAGE') ? WP_BUILD_MESSAGE : __('Launch build ?', 'wp-steroids');
                $build_hook_message = apply_filters('wps_build_hook_message', $build_hook_message);

				$args = [
					'id'    => 'build',
					'title' => '<span class="ab-icon"></span>'.__('Build', 'wp-steroids'),
					'href'  => $build_hook_url,
                    'meta' => [
                        'title'=>$build_hook_message
                    ]
				];

				$wp_admin_bar->add_node( $args );

			}, 999 );
		}

        if( defined('WP_BUILD_BADGE') && WP_BUILD_BADGE ){

            add_action( 'rightnow_end', function( $wp_admin_bar )
            {
                $build_badge_url = apply_filters('wps_build_badge_url', WP_BUILD_BADGE);
                $build_badge_url_version = add_query_arg(['v'=>uniqid()], $build_badge_url);

                echo '<div class="wps-build-badge"><img src="'.$build_badge_url_version.'" data-url="'.esc_url($build_badge_url).'" id="wps-build-badge"/></div>';

            }, 999 );
        }
    }

    /**
     * WPS_Toolbar constructor.
     */
    public function __construct()
    {
        if( !is_admin() )
			return;

	    add_action( 'init', function (){
            $this->addBuildButton();
            $this->addLinks();
        });
    }
}
