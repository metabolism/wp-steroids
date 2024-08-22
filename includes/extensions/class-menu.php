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

        foreach ($this->config->get($register, []) as $location => $args)
        {
            if( is_array($args) ){

                $location = str_replace('-', '_', sanitize_title($location));
                register_nav_menu($location, __t($args['title']??$location));
            }
            else{

                $location = str_replace('-', '_', sanitize_title($location));
                register_nav_menu($location, __t($args));
            }
        }
    }

    public function addColumnForm(){
        global $_nav_menu_placeholder;
        $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
        ?>
        <div class="columndiv">
            <input type="hidden" value="column" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" />
            <input class="url-columndiv" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" type="hidden" value="#column"/>
            <input class="title-columndiv" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" type="hidden" value="<?=__('Column')?>"/>
            <p class="button-controls wp-clearfix">
                <span class="add-to-menu">
                    <input id="submit-columndiv" name="add-custom-menu-item" type="submit" class="button submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu' ); ?>"/>
                    <span class="spinner"></span>
                </span>
            </p>
        </div><!-- /.columndiv -->
        <?php
    }

    public function addArchiveForm(){

        global $_nav_menu_placeholder;
        $post_types = get_post_types( array( 'has_archive' => true ), 'object' );

        $items = [];
        $walker = new Walker_Nav_Menu_Checklist( false );
        $args = ['walker'=>$walker];

        foreach ( $post_types as $post_type ) {

            $_nav_menu_placeholder = ( 0 > $_nav_menu_placeholder ) ? (int) $_nav_menu_placeholder - 1 : -1;

            $items[] = (object)[
                'ID'           => 0,
                'object_id'    => $_nav_menu_placeholder,
                'object'       => $post_type->name,
                'post_content' => '',
                'post_excerpt' => '',
                'post_title'   => $post_type->labels->name,
                'classes'      => [],
                'post_type'    => 'nav_menu_item',
                'type'         => 'post_type_archive',
                'url' => get_post_type_archive_link($post_type->name),
            ];
        }

        $items = apply_filters("nav_menu_items_post_type_archives", $items, $args, 'post_type_archive');
        $checkbox_items = walk_nav_menu_tree( array_map('wp_setup_nav_menu_item', $items), 0, (object)$args);

        ?>
        <div id="posttype-archives" class="posttypediv">
            <div id="tabs-panel-archives" class="tabs-panel tabs-panel-view-all tabs-panel-active" role="region" aria-label="All Archives" tabindex="0">
                <ul id="archives-typechecklist" data-wp-lists="list:posttype-archives" class="categorychecklist form-no-clear">
                    <?= $checkbox_items ?>
                </ul>
            </div>
            <p class="button-controls wp-clearfix" data-items-type="posttype-archive">
                <span class="add-to-menu">
                    <input type="submit" class="button submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu' ); ?>"
                           name="add-post-type-menu-item" id="<?php echo esc_attr( "submit-posttype-archives" ); ?>"
                    />
                    <span class="spinner"></span>
                </span>
            </p>
        </div>
        <?php
    }

    public function addColumn()
    {
        global $wp_meta_boxes;

        $wp_meta_boxes['nav-menus']['side']['default']['add-archives'] = [
            'id' => 'add-archive',
            'title' => __('Post Type Archives'),
            'callback' => [$this, 'addArchiveForm'],
            'args' => ''
        ];

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
