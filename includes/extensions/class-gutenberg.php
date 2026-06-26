<?php

/**
 * Class
 */
class WPS_Gutenberg
{
	/**
	 * @return void
	 */
	public function removeBlockLibrary(){

        wp_dequeue_style('core-block-supports');
	    wp_dequeue_style( 'wp-block-library' );
	    wp_dequeue_style( 'wp-block-library-theme' );
        wp_dequeue_style( 'classic-theme-styles' );
	    wp_dequeue_style( 'wc-block-style' ); // REMOVE WOOCOMMERCE BLOCK CSS
	    wp_dequeue_style( 'global-styles' ); // REMOVE THEME.JSON
    }

	/**
	 * @return void
	 */
	function enqueueBlockEditorAssets() {

        global $_config;

        wp_enqueue_script( 'custom-editor', WPS_PLUGIN_URL . 'public/js/editor.js', ['wp-blocks', 'wp-dom', 'wp-edit-post'], WPS_VERSION, true );
        wp_localize_script('custom-editor', 'wpsEditor', ['class'=>[], 'config'=>$_config->get('gutenberg', [])]);
    }

	/**
	 * @param $allowed_block_types
	 * @param $editor_context
	 * @return int[]|string[]
	 */
	function removeCoreBlocks($allowed_block_types, $editor_context ) {

        if( !is_array($allowed_block_types) )
            $allowed_block_types = WP_Block_Type_Registry::get_instance()->get_all_registered();

		foreach($allowed_block_types as $block=>$data){
			if( substr($block, 0, '5') == 'core/' )
				unset($allowed_block_types[$block]);
        }

		return array_keys($allowed_block_types);
	}

	/**
	 * @param $allowed_block_types
	 * @param $editor_context
	 * @return int[]|string[]
	 */
	function removePluginBlocks($allowed_block_types, $editor_context ) {

        global $_config;
        $to_remove = $_config->get('gutenberg.remove_plugin_block', []);

        if( !is_array($allowed_block_types) ){

            $allowed_block_types = WP_Block_Type_Registry::get_instance()->get_all_registered();
            $allowed_block_types = array_keys($allowed_block_types);
        }

		return array_diff($allowed_block_types, $to_remove);
	}

    /**
	 * @return void
	 */
	function addBlockAssets() {

        global $_config;

        if( is_multisite() )
            $base_url = network_home_url();
        else
            $base_url = get_home_url();

        if( $block_editor_script = $_config->get('gutenberg.block_editor_script', false) ){

            $jsUrl = apply_filters('block_editor_settings_theme_script', $base_url.$block_editor_script);
            wp_enqueue_script('block_editor_script', $jsUrl, [], null);
        }

        if ( $block_editor_style = $_config->get('gutenberg.block_editor_style', false) ){

            $cssUrl = apply_filters('block_editor_settings_theme_css', $base_url.$block_editor_style);
            wp_enqueue_style('block_editor_style', $cssUrl, [], null);
        }

        wp_enqueue_script('block_editor_iframe', WPS_PLUGIN_URL.'public/js/iframe.js', [], WPS_VERSION);
        wp_enqueue_style('block_editor_reset', WPS_PLUGIN_URL.'public/css/reset-editor-styles.css', [], WPS_VERSION);
    }

    /**
     * @return void
     */
    public function registerTemplate(){

        global $_config;

        if( $template = $_config->get('post_type.page.template', false) ){

            $post_type_object = get_post_type_object( 'page' );
            $post_type_object->template = $template;
        }

        if( $template = $_config->get('post_type.post.template', false) ){

            $post_type_object = get_post_type_object( 'post' );
            $post_type_object->template = $template;
        }
    }

    public function __construct()
    {
	    global $_config;

        add_filter( 'run_wptexturize', '__return_false' );

        if ( $_config->get('gutenberg.disable_classic_theme_styles', true) )
            remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );

        if ( !$_config->get('gutenberg.load_remote_block_patterns', false) )
            add_action( 'should_load_remote_block_patterns', '__return_false' );

        if ( $_config->get('gutenberg.remove_core_block', false) )
            add_filter( 'allowed_block_types_all', [$this, 'removeCoreBlocks'], 25, 2 );

        if ( $_config->get('gutenberg.remove_plugin_block', false) )
            add_filter( 'allowed_block_types_all', [$this, 'removePluginBlocks'], 25, 2 );

		if( is_admin() ){

			if ( $width = $_config->get('gutenberg.preview_width', false) ){

                add_action( 'admin_head', function () use($width){
                    echo '<style>.block-editor-inserter__preview-container{ width: '.$width.'px } </style>';
                } );
            }

            add_action( 'enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets'] );
            add_action( 'enqueue_block_assets', [$this, 'addBlockAssets'] );
            add_action( 'init', [$this, 'registerTemplate']);
        }
		else{

			if ( $_config->get('gutenberg.remove_block_library', true) ){

				add_action( 'wp_enqueue_scripts', [$this, 'removeBlockLibrary'], 100 );
                add_action( 'wp_footer', [$this, 'removeBlockLibrary']);
            }
		}
    }
}
