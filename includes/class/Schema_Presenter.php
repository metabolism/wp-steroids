<?php

use Yoast\WP\SEO\Presenters\Schema_Presenter;

if( class_exists( 'Yoast\WP\SEO\Presenters\Schema_Presenter' ) ){

    class WPS_Schema_Presenter extends Schema_Presenter {

        public function present() {

            $deprecated_data = [
                '_deprecated' => 'Please use the "wpseo_schema_*" filters to extend the Yoast SEO schema data - see the WPSEO_Schema class.',
            ];

            /**
             * Filter: 'wpseo_json_ld_output' - Allows disabling Yoast's schema output entirely.
             *
             * @param mixed  $deprecated If false or an empty array is returned, disable our output.
             * @param string $empty
             */
            $return = \apply_filters( 'wpseo_json_ld_output', $deprecated_data, '' );
            if ( $return === [] || $return === false ) {
                return '';
            }

            /**
             * Action: 'wpseo_json_ld' - Output Schema before the main schema from Yoast SEO is output.
             */
            \do_action( 'wpseo_json_ld' );

            $schema = $this->get();
            if ( \is_array( $schema ) ) {
                $output = WPSEO_Utils::format_json_encode( $schema );
                $output = \str_replace( "\n", \PHP_EOL . "\t", $output );
                return '<script type="application/ld+json">' . $output . '</script>';
            }

            return '';
        }
    }
}
