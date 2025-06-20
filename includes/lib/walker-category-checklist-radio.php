<?php

if(!class_exists('Walker_Category_Checklist'))
    require_once( ABSPATH . 'wp-admin/includes/class-walker-category-checklist.php' );

class Walker_Category_Checklist_Radio extends \Walker_Category_Checklist {

	public function start_el( &$output, $data_object, $depth = 0, $args = array(), $current_object_id = 0 ) {

        parent::start_el($output, $data_object, $depth, $args, $current_object_id);
        $output = str_replace('type="checkbox"', 'type="radio"', $output);
	}
}
