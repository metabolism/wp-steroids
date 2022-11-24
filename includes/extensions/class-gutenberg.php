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

	    wp_dequeue_style( 'wp-block-library' );
	    wp_dequeue_style( 'wp-block-library-theme' );
	    wp_dequeue_style( 'wc-block-style' ); // REMOVE WOOCOMMERCE BLOCK CSS
	    wp_dequeue_style( 'global-styles' ); // REMOVE THEME.JSON
    }

	/**
	 * @return void
	 */
	public function removeEditorBlockLibrary(){

	    wp_deregister_style('wp-reset-editor-styles');
	    wp_enqueue_style('wp-reset-editor-styles', WPS_PLUGIN_URL.'public/reset-editor-styles.css', ['common', 'forms']);
	}

	/**
	 * @param $allowed_block_types
	 * @param $editor_context
	 * @return int[]|string[]
	 */
	function removeCoreBlock($allowed_block_types, $editor_context ) {

		$blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

		foreach($blocks as $block=>$data){
			if( substr($block, 0, '5') == 'core/' )
				unset($blocks[$block]);
		}

		return array_keys($blocks);
	}

	/**
	 * @return void
	 */
	function addBlockEditorAssets() {

		global $_config;

		if ( $block_editor_style = $_config->get('gutenberg.block_editor_style', false) )
			wp_enqueue_style('block_editor_style',get_home_url().$block_editor_style);

		if ( $block_editor_script = $_config->get('gutenberg.block_editor_script', false) )
			wp_enqueue_script('block_editor_script',get_home_url().$block_editor_script);
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

		if( is_admin() ){

			if( $_config->get('gutenberg.replace_reset_styles', true) )
				add_action( 'enqueue_block_editor_assets', [$this, 'removeEditorBlockLibrary'], 100 );

			if ( $_config->get('gutenberg.remove_core_block', false) )
				add_filter( 'allowed_block_types_all', [$this, 'removeCoreBlock'], 25, 2 );

			add_action( 'enqueue_block_assets', [$this, 'addBlockEditorAssets'] );

            add_action( 'init', [$this, 'registerTemplate']);
        }
		else{

			if ( $_config->get('gutenberg.remove_block_library', true) )
				add_action( 'wp_enqueue_scripts', [$this, 'removeBlockLibrary'], 100 );

			if ( !$_config->get('gutenberg.load_remote_block_patterns', false) )
				add_action( 'should_load_remote_block_patterns', '__return_false' );
		}
    }
}
