<?php

use Dflydev\DotAccessData\Data;

/**
 * Class
 */
class WPS_Menu {

    /** @var Data $config */
    protected $config;

    /**
     * Register menus
     * @see Menu
     */
    public function addMenus()
    {
        $register = $this->config->get('menu.register', false);
        $register = $register ? 'menu.register' : 'menu';

        foreach ($this->config->get($register, []) as $location => $description)
        {
            $location = str_replace('-', '_', sanitize_title($location));
            register_nav_menu($location, __t($description));
        }
    }

    public function addColumnForm(){
        global $_nav_menu_placeholder, $nav_menu_selected_id;
        $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
        ?>
        <div class="colmundiv">
            <input type="hidden" value="column" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" />
            <input class="url-colmundiv" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" type="hidden" value="#column"/>
            <input class="title-colmundiv" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" type="hidden" value="<?=__('Column')?>"/>
            <p class="button-controls wp-clearfix">
                <span class="add-to-menu">
                    <input id="submit-colmundiv" name="add-custom-menu-item" type="submit" class="button submit-add-to-menu right" value="<?php esc_attr_e( 'Insert column after' ); ?>"/>
                    <span class="spinner"></span>
                </span>
            </p>
        </div><!-- /.colmundiv -->
        <?php
    }

    public function addColumn()
    {
        global $wp_meta_boxes;

        $wp_meta_boxes['nav-menus']['side']['default']['add-columns'] = [
            'id' => 'add-column',
            'title' => __('Column'),
            'callback' => [$this, 'addColumnForm'],
            'args' => ''
        ];
    }

    /**
     * ConfigPlugin constructor.
     */
    public function __construct()
    {
        global $_config;

        $this->config = $_config;

        add_action( 'init', [$this, 'addMenus']);
        add_action( 'admin_head-nav-menus.php', [$this, 'addColumn']);
    }
}
