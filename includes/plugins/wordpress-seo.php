<?php

/**
 * Class
 *
 * @package
 */
class WPS_Wordpress_Seo
{
	public static $preventRecursion=false;

	/**
	 * Disable editor options for seo taxonomy edition
	 * @param $settings
	 * @param $editor_id
	 * @return mixed
	 */
	public function editorSettings( $settings, $editor_id ){

		if ( $editor_id == 'description' && class_exists('WPSEO_Taxonomy') && \WPSEO_Taxonomy::is_term_edit( $GLOBALS['pagenow'] ) ) {

			$settings[ 'tinymce' ] = false;
			$settings[ 'wpautop' ] = false;
			$settings[ 'media_buttons' ] = false;
			$settings[ 'quicktags' ] = false;
			$settings[ 'default_editor' ] = '';
			$settings[ 'textarea_rows' ] = 4;
		}

		return $settings;
	}


	/**
	 * Allow editor to edit theme and wpseo options
	 */
	public function updateEditorRole(){

		$role_object = get_role( 'editor' );

		if( !$role_object->has_cap('wpseo_edit_advanced_metadata') )
			$role_object->add_cap( 'editor', 'wpseo_edit_advanced_metadata' );

		if( !$role_object->has_cap('wpseo_manage_options') )
			$role_object->add_cap( 'editor', 'wpseo_manage_options' );
	}


	/**
	 * Init admin
	 */
	public function init(){

		$this->updateEditorRole();
	}


	/**
	 * Remove trailing slash & query parameters
	 * @param $canonical
	 * @return mixed
	 */
	public function filterCanonical($canonical) {

		if( is_archive() ){
			$canon_page = get_pagenum_link();
			$canonical = explode('?', $canon_page);
			return $canonical[0];
		}

		$canonical = explode('?', $canonical);

		return (substr($canonical[0], -1) == '/') ? substr($canonical[0], 0, -1) : $canonical[0];
	}


	/**
	 * Add primary flagged term in first position
     * @param $clauses
	 * @param $taxonomies
     * @param $args
	 * @return array
	 */
	public function changeTermsOrder($clauses, $taxonomies, $args){

        if( self::$preventRecursion )
            return $clauses;

        if ( !class_exists('WPSEO_Primary_Term') )
            return $clauses;

        if( count($args['object_ids']??[]) != 1 || count($args['taxonomy']??[]) != 1 )
            return $clauses;

        self::$preventRecursion = true;

        $taxonomies = array_values($args['taxonomy']);
        $object_ids = array_values($args['object_ids']);

        $wpseo_primary_term = new \WPSEO_Primary_Term( $taxonomies[0], $object_ids[0]);
			$primary_term_id = $wpseo_primary_term->get_primary_term();

        if( $primary_term_id && !empty($clauses['orderby']) )
            $clauses['orderby'] = 'ORDER BY CASE WHEN t.term_id='.$primary_term_id.' THEN 0 ELSE 1 END, '.str_replace('ORDER BY ', '', $clauses['orderby']);

        self::$preventRecursion = false;

		return $clauses;
	}


	/**
	 * return true if wpseo title is filled
	 * @param $postID
	 * @return bool
	 */
	public static function hasTitle($postID){

		return strlen(get_post_meta($postID, '_yoast_wpseo_title', true)) > 1;
	}


	/**
	 * return true if wpseo description is filled
	 * @param $postID
	 * @return bool
	 */
	public static function hasDescription($postID){

		return strlen(get_post_meta($postID, '_yoast_wpseo_metadesc', true)) > 1;
	}


	/**
	 * add sitemap_index.xml to robots.txt
	 * @param $output
	 * @return string
	 */
	public static function cleanUpRobots( $output ) {

		$output = str_replace("# START YOAST BLOCK\n# ---------------------------\n", '', $output);
		$output = str_replace("# ---------------------------\n# END YOAST BLOCK", '', $output);

		return $output;
	}

    /**
     * @param $allowed_block_types
     * @param $editor_context
     * @return int[]|string[]
     */
    function removeCoreBlock($allowed_block_types, $editor_context ) {

        if( !is_array($allowed_block_types) )
            return $allowed_block_types;

        $to_remove = [];

        foreach($allowed_block_types as $block){
            if( substr($block, 0, '6') == 'yoast/' || substr($block, 0, '10') == 'yoast-seo/' )
                $to_remove[] = $block;
        }

        $allowed_block_types = array_diff($allowed_block_types, $to_remove);

        return array_keys($allowed_block_types);
    }



	/**
	 * Construct
	 */
	public function __construct()
	{
		add_action('admin_init', [$this, 'init'] );
		add_filter('terms_clauses', [$this, 'changeTermsOrder'], 10, 3);

        add_filter( 'wpseo_sitemap_exclude_taxonomy', function( $value, $taxonomy ) {

            $taxonomy = get_taxonomy($taxonomy);
            return !$taxonomy->publicly_queryable;

        }, 10, 2 );

		if( is_admin() ) {

            add_filter('allowed_block_types_all', [$this, 'removeCoreBlock'], 26, 2 );
			add_filter('wp_editor_settings', [$this, 'editorSettings'], 10, 2);
			add_filter('wpseo_metabox_prio', function (){ return 'low'; });
		}
        else{

            add_filter('wpseo_debug_markers', '__return_false' );
            add_filter('wpseo_canonical', [$this, 'filterCanonical']);
            add_filter('robots_txt', [$this, 'cleanUpRobots'], 999999, 1 );
		}
	}
}
