<?php

/**
 * Class 
 */
class WPS_Rewrite {

    private $config;

	/**
	 * Remove rules based on config
	 * @param $wp_rewrite
	 */
	public function remove($wp_rewrite ){

        $remove = $this->config->get('rewrite_rules.remove', []);

        foreach (['rules', 'extra_rules_top'] as $item){

            foreach ($wp_rewrite->$item as $rule=>$rewrite){

                if( in_array('attachment', $remove) && (strpos($rule, '/attachment/') !== false || strpos($rewrite, 'attachment=') !== false) )
                    unset( $wp_rewrite->$item[$rule] );

                if( in_array('embed', $remove) && strpos($rule, '/embed/') !== false )
                    unset( $wp_rewrite->$item[$rule] );

                if( in_array('feed', $remove) && (strpos($rule, '/(feed|rdf|rss|rss2|atom)/') !== false || strpos($rule, '/feed/') !== false) )
                    unset( $wp_rewrite->$item[$rule] );

                if( in_array('trackback', $remove) && strpos($rule, '/trackback/') !== false )
                    unset( $wp_rewrite->$item[$rule] );

                if( in_array('comment', $remove) && strpos($rule, '/comment-page-') !== false )
                    unset( $wp_rewrite->$item[$rule] );
            }
        }
    }

	/**
	 * RewritePlugin constructor.
	 */
	function __construct() {

        global $_config;

		$this->config = $_config;

		add_action( 'generate_rewrite_rules', [$this, 'remove'] );
	}
}
