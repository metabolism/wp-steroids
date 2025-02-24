<?php

use Timber\MenuItem;

if ( class_exists('Timber\MenuItem') ) {

    class WPS_Menu_Item extends MenuItem
    {
        public function aria_label()
        {
            return $this->meta('_menu_item_aria_label');
        }
    }
}
