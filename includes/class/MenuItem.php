<?php

use Timber\MenuItem;

if ( class_exists('Timber\MenuItem') ) {

    class WPS_Menu_Item extends MenuItem
    {
        public function aria_label()
        {
            return $this->meta('_menu_item_aria_label');
        }

        public function anchor()
        {
            return $this->meta('_menu_item_anchor');
        }

        public function link()
        {
            $anchor = $this->anchor();
            $link = parent::link();

            if( $anchor )
                $link .= '#'.ltrim($anchor, '#');

            return $link;
        }
    }
}
