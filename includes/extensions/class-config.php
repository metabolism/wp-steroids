<?php

use Dflydev\DotAccessData\Data;

/**
 * Class
 */
class WPS_Config {

    /** @var Data $config */
    protected $config;
    protected $support;
    protected $loaded=false;

    /**
     * Get plural from name
     * @param $name
     * @param bool $space
     * @return string
     */
    public function plural($name, $space=true)
    {
        $name = substr($name, -1) == 's' ? ($space?'':'all ').$name : (substr($name, -1) == 'y' && !in_array(substr($name, -2, 1), ['a','e','i','o','u']) ? substr($name, 0, -1).'ies' : $name.'s');

        if( $space )
            return $name;
        else
            return str_replace(' ', '_', $name);
    }


    /**
     * setup configuration
     */
    public function configureContentType()
    {
        $this->addPostTypes();
        $this->addTaxonomies();
        $this->addRewriteRules();

        $this->updateSearchStructure();
        $this->updatePageStructure();

        $this->loaded = true;
    }

    /**
     * Adds specific post types here
     * @see CustomPostType
     */
    public function addPostTypes()
    {
        $default_args = [
            'public' => true,
            'map_meta_cap' => true,
            'has_archive' => true,
            'rewrite' => [
                'pages'=>true,
                'paged'=>false,
                'feeds'=>false
            ],
            'supports' => [],
            'menu_position' => 25
        ];

        $is_admin = is_admin();

        $current_blog_id = get_current_blog_id();

        $registered_post_types = $this->config->get('post_type', []);

        if( !is_array($registered_post_types) )
            return;

        foreach ( $registered_post_types as $post_type => &$args )
        {
            if( !in_array($post_type, ['post', 'page', 'edition']) )
            {
                if( (isset($args['enable_for_blogs']) && !in_array($current_blog_id, (array)$args['enable_for_blogs'])) || (isset($args['disable_for_blogs']) && in_array($current_blog_id, (array)$args['disable_for_blogs'])))
                    continue;

                $args = array_merge($default_args, $args);
                $name = str_replace('-', ' ', str_replace('_', ' ', $args['labels']['singular_name']??$post_type));
                $names = $args['labels']['name']??$this->plural($name);

                $labels = [
                    'name' => ucfirst($names),
                    'singular_name' => ucfirst($name),
                    'add_new' => 'Add '.$name,
                    'add_new_item' => 'Add '.$name,
                    'edit_item' => 'Edit '.$name,
                    'new_item' => 'New '.$name,
                    'view_item' => 'View '.$name,
                    'view_items' => 'View '.$names,
                    'search_items' => 'Search '.$names,
                    'not_found' => ucfirst($name).' not found',
                    'not_found_in_trash' => 'No '.$name.' found in Trash',
                    'parent_item_colon' => 'Parent '.$name,
                    'all_items' =>'All '.$names,
                    'archives' =>ucfirst($names).' Archives',
                    'attributes' =>ucfirst($name).' Attributes',
                    'insert_into_item' =>'Insert into '.$name,
                    'uploaded_to_this_item' =>'Uploaded to this '.$name,
                    'filter_items_list' =>'Filter '.$names.' list',
                    'items_list_navigation' =>ucfirst($names).' list navigation',
                    'items_list' =>ucfirst($names).' list',
                    'item_published' =>ucfirst($name).' published',
                    'item_published_privately' =>ucfirst($name).' published privately',
                    'item_reverted_to_draft' =>ucfirst($name).' reverted to draft',
                    'item_trashed' =>ucfirst($name).' trashed',
                    'item_scheduled' =>ucfirst($name).' scheduled',
                    'item_updated' =>ucfirst($name).' updated',
                    'item_link' =>ucfirst($name).' Link',
                    'item_link_description' =>'A link to a '.$name,
                ];

                if( isset($args['labels']) )
                    $args['labels'] = array_merge($labels, $args['labels']);
                else
                    $args['labels'] = $labels;

                foreach ( $args['labels'] as $key=>$value )
                    $args['labels'][$key] = __t($value);

                if( isset($args['menu_icon']) )
                    $args['menu_icon'] = 'dashicons-'.$args['menu_icon'];

                if( isset($args['capability_type']) && !isset($args['capabilities']) ){

                    if( is_string($args['capability_type']) )
                        $args['capability_type'] = [$args['capability_type'], $this->plural($args['capability_type'], false)];
                    else
                        $args['capability_type'] = [$post_type, $this->plural($post_type, false)];
                }

                if( is_bool($args['rewrite']) && $args['rewrite'] )
                    $args['rewrite'] = ['slug'=>$post_type];

                if( is_string($args['rewrite']) )
                    $args['rewrite'] = ['slug'=> $args['rewrite']];

                if( is_array($args['rewrite']) )
                    $args['rewrite']['paged'] = false;

                $slug = $this->getSlug( $post_type );

                if( !empty($slug) && is_array($args['rewrite']) )
                    $args['rewrite']['slug'] = $slug;

                if( $args['has_archive'] ){

                    $archive = get_option( $post_type. '_rewrite_archive' );

                    if( !empty($archive) )
                        $args['has_archive'] = $archive;

                    if( !isset($args['rewrite']['pages']) && is_array($args['rewrite']) )
                        $args['rewrite']['pages'] = true;

                    if( !isset($args['rewrite']['feeds']) && is_array($args['rewrite']) )
                        $args['rewrite']['feeds'] = false;
                }

                if( HEADLESS && !URL_MAPPING ){

                    $args['publicly_queryable'] = false;
                }
                else{

                    preg_match_all('/{.+?}/', $slug, $toks);

                    if( count($toks) && count($toks[0]) ){

                        $rule = '^'.$slug.'/([^/]+)/?$';

                        foreach ($toks[0] as $tok)
                            $rule = str_replace($tok, '[^/]+', $rule);

                        add_rewrite_rule($rule, 'index.php?'.$post_type.'=$matches[1]', 'top');
                    }
                }

                if( isset($args['query_var']) && !$args['query_var'] ){

                    $args['show_in_nav_menus'] = false;
                    $args['exclude_from_search'] = false;
                }

                if( isset($args['publicly_queryable']) && !$args['publicly_queryable'] ){

                    $args['show_in_nav_menus'] = false;
                    $args['query_var'] = false;
                    $args['exclude_from_search'] = false;
                    $args['rewrite'] = false;
                }

                register_post_type($post_type, $args);

                if( $is_admin && !$this->loaded )
                {
                    if( isset($args['columns']) )
                    {
                        $columns_arr = [];

                        foreach ( $args['columns'] as $id=>$column ){

                            if( is_int($id) )
                                $columns_arr[$column] = ucfirst(str_replace('_', ' ', $column));
                            else
                                $columns_arr[$id] = ucfirst(str_replace('_', ' ', $id));
                        }

                        $args['custom_columns'] = $columns_arr;

                        add_filter ( 'manage_'.$post_type.'_posts_columns', function ( $columns ) use ( $args )
                        {
                            $columns = array_merge ( $columns, $args['custom_columns']);

                            if( isset($columns['date']) ){

                                $date = $columns['date'];
                                unset($columns['date']);
                                $columns['date'] = $date;
                            }

                            return $columns;
                        });

                        add_action ( 'manage_'.$post_type.'_posts_custom_column', function ( $column, $post_id ) use ( $args )
                        {
                            if( isset($args['custom_columns'][$column]) )
                            {
                                if( $column == 'thumbnail'){

                                    if( in_array('thumbnail', $args['supports']) ){

                                        $thumbnail = get_the_post_thumbnail($post_id, 'thumbnail');
                                        echo '<a class="attachment-thumbnail-container">'.$thumbnail.$thumbnail.'</a>';
                                    }
                                    else{

                                        $thumbnail_id = get_post_meta( $post_id, 'thumbnail', true );

                                        if( is_string($thumbnail_id) ){

                                            echo '<a class="attachment-thumbnail-container"><img class="attachment-thumbnail size-thumbnail wp-post-image" src="'.$thumbnail_id.'"><img class="attachment-thumbnail size-thumbnail wp-post-image" src="'.$thumbnail_id.'"></a>';
                                        }
                                        elseif($thumbnail_id){

                                            $image = wp_get_attachment_image_src($thumbnail_id);

                                            if( $image && count($image) )
                                                echo '<a class="attachment-thumbnail-container"><img class="attachment-thumbnail size-thumbnail wp-post-image" src="'.$image[0].'"><img class="attachment-thumbnail size-thumbnail wp-post-image" src="'.$image[0].'"></a>';
                                        }
                                    }
                                }
                                else{

                                    $params = $args['columns'][$column]??'';
                                    $value = get_post_meta( $post_id, $column, true );

                                    if( $params == 'bool' ){

                                        $value = boolval($value)?'☑':0;
                                        $params = '';
                                    }
                                    elseif( $value && is_numeric($value) )
                                        $value = str_replace(',00', '', number_format($value, 2, ',', ' '));

                                    if( $value )
                                        echo __t($value).(!empty($params)?' '.$params:'');
                                }
                            }
                        }, 10, 2 );
                    }
                }
            }
        }
    }


    /**
     * Add post type support
     */
    public function addPostTypeSupport()
    {
        $support = $this->config->get('post_type_support', []);

        foreach ($support as $post_type=>$features){

            if( is_array($features) ){

                foreach ($features as $feature )
                    add_post_type_support( $post_type, $feature);
            }
            else{

                add_post_type_support( $post_type, $features);
            }
        }
    }

    /**
     * Add theme support
     */
    public function defineThemeSupport()
    {
        $support = $this->config->get('theme_support', []);

        foreach ($support as $feature){

            if( $feature == 'post_thumbnails' || $feature == 'post_thumbnail' || $feature == 'thumbnail')
                $feature = 'post-thumbnails';

            if( is_array($feature) && !empty($feature) ){

                $key = array_keys($feature)[0];
                $params = $feature[$key];

                @add_theme_support( $key, $params);
            }
            else{

                @add_theme_support( $feature );
            }
        }

        $disabled = ['disable-custom-colors'];

        foreach ($disabled as $feature){

            if( !in_array($feature, $this->support) )
                add_theme_support($feature);
        }
    }


    /**
     * Register sidebars
     * @see Menu
     */
    public function addSidebars()
    {
        foreach ($this->config->get('sidebar', []) as $id => $params)
        {
            $params['id'] = $id;
            register_sidebar($params);
        }
    }

    /**
     * Preprocess slug
     */
    private function getSlug($entity){

        if( !$slug = wp_cache_get( $entity, 'rewrite_slug' ) ){

            $slug = get_option( $entity. '_rewrite_slug' );

            $slug = preg_replace('/^%/', '{', preg_replace('/%$/', '}', $slug));
            $slug = preg_replace('/%\//', '}/', preg_replace('/\/%/', '/{', $slug));

            wp_cache_set( $entity, $slug, 'rewrite_slug' );
        }

        return $slug;
    }


    /**
     * Adds Custom taxonomies
     * @see Taxonomy
     */
    public function addTaxonomies(){

        $default_args = [
            'map_meta_cap' => true,
            'public' => true,
            'hierarchical' => true,
            'rewrite' => [],
            'show_admin_column' => true
        ];

        $registered_taxonomies = $this->config->get('taxonomy', []);

        if( !is_array($registered_taxonomies) )
            return;

        foreach ( $registered_taxonomies as $taxonomy => &$args ) {

            if( !in_array($taxonomy, ['category', 'tag', 'edition', 'theme', 'type']) ) {

                $args = array_merge($default_args, $args);

                $name = str_replace('-', ' ', str_replace('_', ' ', $args['labels']['singular_name']??$taxonomy));
                $names = $args['labels']['name']??$this->plural($name);

                $labels = [
                    'name' => ucfirst($names),
                    'singular_name' => ucfirst($name),
                    'search_items' => 'Search in ' . $names,
                    'popular_items' => 'Popular ' . $names,
                    'all_items' => 'All ' . $names,
                    'parent_item' => 'Parent ' . $name,
                    'add_new_item' => 'Add a ' . $name,
                    'edit_item' => 'Edit ' . $name,
                    'not_found' => ucfirst($name) . ' not found',
                ];

                if( !isset($args['capabilities']) && isset($args['capability_type']) ){

                    if( is_string($args['capability_type']) )
                        $capability = $this->plural($args['capability_type'], false);
                    else
                        $capability = $this->plural($taxonomy, false);

                    $args['capabilities'] = [
                        'manage_terms' => 'manage_'.$capability,
                        'edit_terms'   => 'edit_'.$capability,
                        'delete_terms' => 'delete_'.$capability,
                        'assign_terms' => 'assign_'.$capability
                    ];
                }

                if( is_bool($args['rewrite']) && $args['rewrite'] )
                    $args['rewrite'] = ['slug'=>$taxonomy];

                if( is_string($args['rewrite']) )
                    $args['rewrite'] = ['slug'=> $args['rewrite']];

                if( !isset($args['rewrite']['feed']) )
                    $args['rewrite']['feed'] = false;

                $slug = $this->getSlug( $taxonomy );

                if( !empty($slug) && is_array($args['rewrite']) )
                    $args['rewrite']['slug'] = $slug;

                if (isset($args['labels']))
                    $args['labels'] = array_merge($labels, $args['labels']);
                else
                    $args['labels'] = $labels;

                foreach ( $args['labels'] as $key=>$value )
                    $args['labels'][$key] = __t($value);

                if (isset($args['object_type'])) {

                    $object_type = $args['object_type'];
                    unset($args['object_type']);

                } else {

                    $object_type = 'post';
                }

                if( HEADLESS && !URL_MAPPING )
                    $args['publicly_queryable'] = false;

                if( isset($args['publicly_queryable']) && !$args['publicly_queryable'] ){

                    $args['show_in_nav_menus'] = false;
                    $args['query_var'] = false;
                    $args['rewrite'] = false;
                }

                register_taxonomy($taxonomy, $object_type, $args);

            } else{

                wp_die($taxonomy. ' is not allowed, reserved keyword');
            }
        }
    }

    /**
     * @param $taxonomy
     * @return int[]|string|string[]|WP_Error|WP_Term[]
     */
    private function getSlugs($taxonomy){

        $taxonomy = str_replace('{', '', str_replace('}', '', $taxonomy));

        $slugs = get_terms(['taxonomy'=> $taxonomy,'fields' => 'slugs']);

        if( is_wp_error($slugs) )
            return ['default'];

        return $slugs;
    }

    public function addRewriteRules(){

        if( HEADLESS )
            return;

        $taxonomies = $this->config->get('taxonomy', []);

        if( is_array($taxonomies) ){

            foreach ( $taxonomies as $taxonomy => $args )
            {
                $slug = $this->getSlug( $taxonomy );

                if( $slug === '{empty}'){

                    $slugs = $this->getSlugs($taxonomy);

                    add_rewrite_rule('^('.implode('|', $slugs).')$', 'index.php?'.$taxonomy.'=$matches[1]', 'top');
                }
                else{

                    preg_match_all('/\/{.+?}/', $slug, $toks);

                    if( count($toks) && count($toks[0]) ){

                        $rule = '^'.$slug.'/([^/]+)/?$';
                        $has_parent = false;

                        foreach ($toks[0] as $tok){

                            $has_parent = $has_parent || $tok == '/{parent}';

                            if( $tok != '/{parent}' )
                                $rule = str_replace($tok, '/[^/]+', $rule);
                        }

                        if( $has_parent ){

                            add_rewrite_rule(str_replace('/{parent}', '/[^/]+', $rule), 'index.php?'.$taxonomy.'=$matches[1]', 'top');
                            add_rewrite_rule(str_replace('/{parent}', '', $rule), 'index.php?'.$taxonomy.'=$matches[1]', 'top');
                        }
                        else{

                            add_rewrite_rule($rule, 'index.php?'.$taxonomy.'=$matches[1]', 'top');
                        }
                    }
                }
            }
        }

        $post_types = $this->config->get('post_type', []);

        if( is_array($post_types) ){

            foreach ( $this->config->get('post_type', []) as $post_type => $args )
            {
                $slug = $this->getSlug( $post_type );

                preg_match_all('/{.+?}/', $slug, $toks);

                if( count($toks) && count($toks[0]) ){

                    $rule = '^'.$slug.'/([^/]+)/?$';

                    foreach ($toks[0] as $i=>$tok){

                        if( substr($slug, 0, 1) == '{' && $i==0 ){

                            if( $slugs = $this->getSlugs($tok) )
                                $rule = str_replace($tok, '('.implode('|', $slugs).')', $rule);
                        }
                        else{

                            $rule = str_replace($tok, '[^/]+', $rule);
                        }
                    }

                    add_rewrite_rule($rule, 'index.php?'.$post_type.'=$matches[1]', 'top');
                }
            }
        }
    }

    /**
     * Adds User role
     * https://codex.wordpress.org/Function_Reference/add_role
     */
    public function addRoles()
    {
        if( is_admin() && current_user_can('administrator') && ($_GET['populate_roles']??false) ){

            if ( !function_exists( 'populate_roles' ) )
                require_once( ABSPATH . 'wp-admin/includes/schema.php' );

            populate_roles();
        }

        foreach ( $this->config->get('role', []) as $role => $args )
        {
            if( is_admin() && current_user_can('administrator') && ($_GET['reload_role']??false) )
                remove_role($role);

            if( !empty($args['inherit']??'') ){

                $inherited_role = get_role( $args['inherit'] );
                $args['capabilities'] = array_merge($inherited_role->capabilities, $args['capabilities']??[]);
            }

            add_role($role, $args['display_name'], $args['capabilities']);
        }

        add_filter( 'editable_roles', function($all_roles){

            if( !current_user_can('administrator') )
                unset($all_roles['administrator']);

            return $all_roles;
        });
    }

    public function LoadPermalinks()
    {
        $updated = false;

        add_settings_section('page_rewrite', '', '__return_empty_string','permalink');

        if( isset( $_POST['page_rewrite_slug'] ) && !empty($_POST['page_rewrite_slug']) )
        {
            update_option( 'page_rewrite_slug', $_POST['page_rewrite_slug'], true );
            $updated = true;
        }

        add_settings_field( 'page_rewrite_slug', 'Page base',function ()
        {
            $value = get_option( 'page_rewrite_slug' );
            echo '<input type="text" value="' . esc_attr( $value ) . '" name="page_rewrite_slug" placeholder="page" id="page_rewrite_slug" class="regular-text" />';

        }, 'permalink', 'page_rewrite' );

        add_settings_section('search_rewrite', '', '__return_empty_string','permalink');

        if( isset( $_POST['search_rewrite_slug'] ) && !empty($_POST['search_rewrite_slug']) )
        {
            update_option( 'search_rewrite_slug', $_POST['search_rewrite_slug'], true );
            $updated = true;
        }

        add_settings_field( 'search_rewrite_slug', 'Search base',function ()
        {
            $value = get_option( 'search_rewrite_slug' );
            echo '<input type="text" value="' . esc_attr( $value ) . '" name="search_rewrite_slug" placeholder="search" id="search_rewrite_slug" class="regular-text" />';

        }, 'permalink', 'search_rewrite' );

        add_settings_section('custom_post_type_rewrite', 'Custom post type', '__return_empty_string','permalink');

        foreach ( get_post_types(['public'=> true, '_builtin' => false], 'objects') as $post_type=>$args )
        {
            foreach( ['slug', 'archive'] as $type)
            {
                if( ($type == 'slug' && is_post_type_viewable($post_type)) || ($type == 'archive' && $args->has_archive ))
                {
                    if( isset( $_POST[$post_type. '_rewrite_'.$type] ) && !empty($_POST[$post_type. '_rewrite_'.$type]) )
                    {
                        update_option( $post_type. '_rewrite_'.$type, $_POST[$post_type. '_rewrite_'.$type], true );
                        $updated = true;
                    }

                    add_settings_field( $post_type. '_rewrite_'.$type, __t( ucfirst(str_replace('_', ' ', $post_type)).' '.$type ),function () use($post_type, $type)
                    {
                        $value = get_option( $post_type. '_rewrite_'.$type );
                        if(empty($value))
                            $value = $this->config->get('post_type.'.$post_type.($type=='slug'?'.rewrite.slug':'has_archive'), $post_type);

                        echo '<input type="text" value="' . esc_attr( $value ) . '" name="'.$post_type.'_rewrite_'.$type.'" placeholder="'.$post_type.'" id="'.$post_type.'_rewrite_'.$type.'" class="regular-text" />';

                        if( $type == 'slug' ){

                            $taxonomy_objects = get_object_taxonomies( $post_type );
                            if( !empty($taxonomy_objects) )
                                echo '<p class="description">You can add %'.implode('%, %', $taxonomy_objects).'%</p>';
                        }

                    }, 'permalink', 'custom_post_type_rewrite' );
                }
            }
        }

        add_settings_section('custom_taxonomy_rewrite', 'Custom taxonomy', '__return_empty_string','permalink');

        foreach ( get_taxonomies(['public'=> true, '_builtin' => false], 'objects') as $taxonomy=>$args )
        {
            if( !is_taxonomy_viewable($taxonomy) )
                continue;

            if( isset( $_POST[$taxonomy. '_rewrite_slug'] ) && !empty($_POST[$taxonomy. '_rewrite_slug']) )
            {
                update_option( $taxonomy. '_rewrite_slug', $_POST[$taxonomy. '_rewrite_slug'], true );
                $updated = true;
            }

            add_settings_field( $taxonomy. '_rewrite_slug', __t( ucfirst(str_replace('_', ' ', $taxonomy)).' base' ),function () use($taxonomy)
            {
                $value = get_option( $taxonomy. '_rewrite_slug' );
                if(empty($value))
                    $value = $this->config->get('taxonomy.'.$taxonomy.'.rewrite.slug', $taxonomy);

                echo '<input type="text" value="' . esc_attr( $value ) . '" name="'.$taxonomy.'_rewrite_slug" placeholder="'.$taxonomy.'" id="'.$taxonomy.'_rewrite_slug" class="regular-text" />';
                echo '<p class="description">You can add %parent% or use %empty%</p>';

            }, 'permalink', 'custom_taxonomy_rewrite' );
        }

        if( $updated ){

            global $wp_rewrite;
            $wp_rewrite->flush_rules(false);

            do_action('reset_cache');
        }
    }


    public function addTableViews()
    {
        foreach ( $this->config->get('table', []) as $name => $args )
        {
            $default_args = [
                'page_title' => ucfirst($name),
                'menu_title' => ucfirst($name),
                'capability' => 'activate_plugins',
                'singular'   => $name,
                'menu_icon'  => 'editor-table',
                'plural'     => $this->plural($name),
                'per_page'   => 20,
                'position'   => 30,
                'export'     => true
            ];

            $args = array_merge($default_args, $args);
            $args['menu_icon'] = 'dashicons-'.$args['menu_icon'];

            $table = new WPS_List_Table($name, $args);

            add_action('admin_menu', function() use($name, $table, $args) {

                add_menu_page($args['page_title'], $args['menu_title'], $args['capability'], 'table_'.$name, function() use($table, $args)
                {
                    $table->init();
                    $table->prepare_items();
                    $table->display();

                }, $args['menu_icon'], $args['position']);
            });
        }
    }

    /**
     * Disable category
     */
    public function disableFeatures()
    {
        if( !in_array('post', $this->support) ){

            register_post_type('post', ['public'=>false]);
            remove_permastruct('post');
        }

        if( !in_array('page', $this->support) ){

            register_post_type('page', ['public'=>false]);
            remove_permastruct('page');
        }

        if( !in_array('category', $this->support) ){

            register_taxonomy( 'category', 'post', ['public'=>false]);
            remove_permastruct('category');
        }

        if( !in_array('tag', $this->support) ){

            register_taxonomy( 'post_tag', 'post', ['public'=>false] );
            remove_permastruct('post_tag');
        }

        if( in_array('edit-comments.php', (array)$this->config->get('remove_menu_page', [])) ){

            remove_post_type_support( 'post', 'comments' );
            remove_post_type_support( 'page', 'comments' );
        }
    }


    /**
     * Update permalink if structure is custom
     * @param $post_link
     * @param $post
     * @return string|string[]
     */
    public  function updatePostTypePermalink($post_link, $post){

        if ( is_object( $post ) ){

            preg_match_all('/\/{.+?}/', $post_link, $toks);

            if( count($toks) && count($toks[0]) ){

                foreach ($toks[0] as $tok){

                    $taxonomy = str_replace('}', '', str_replace('/{', '', $tok));
                    $terms = wp_get_object_terms( $post->ID, $taxonomy, ['fields'=>'slugs', 'number'=>1] );

                    if( !is_wp_error($terms) && count($terms) )
                        $post_link = str_replace( '{'.$taxonomy.'}', $terms[0], $post_link );
                    else
                        $post_link = str_replace( '{'.$taxonomy.'}', 'default', $post_link );
                }
            }
        }

        return $post_link;
    }


    /**
     * Update permalink if structure is custom
     * @param $term_link
     * @param $term
     * @return string|string[]
     */
    public  function updateTermPermalink($term_link, $term){

        if ( is_object( $term ) ){

            preg_match_all('/\/{.+?}/', $term_link, $toks);

            if( count($toks) ){

                foreach ($toks[0] as $tok){

                    $match = str_replace('}', '', str_replace('/{', '', $tok));

                    if( $match == 'parent' ){

                        if( $term->parent ){
                            $parent_term = get_term($term->parent, $term->taxonomy);

                            if( $parent_term )
                                $term_link = str_replace( '{'.$match.'}', $parent_term->slug, $term_link );
                        }
                    }

                    $term_link = str_replace( '/{'.$match.'}', '', $term_link );
                }
            }
        }

        return $term_link;
    }

    public function updatePageStructure(){

        $value = get_option( 'page_rewrite_slug' );

        if( !empty($value) ){

            global $wp_rewrite;

            $page_structure = $wp_rewrite->get_page_permastruct();
            $wp_rewrite->page_structure = $value.'/'.$page_structure;

            if( $value == 'page' && $wp_rewrite->pagination_base == 'page' ){

                $wp_rewrite->pagination_base = 'paged';
                $wp_rewrite->comments_pagination_base = 'comment-paged';
            }
        }
    }

    public function updateSearchStructure(){

        global $wp_rewrite, $wp_search_base;

        $search_slug = get_option( 'search_rewrite_slug' );

        if( empty($wp_search_base) )
            $wp_search_base = $wp_rewrite->search_base;

        if( isset($wp_rewrite->search_structure) )
            unset($wp_rewrite->search_structure);

        if( !empty($search_slug) ){

            $wp_rewrite->search_base = $search_slug;
        }
        else{

            $wp_rewrite->search_base = $wp_search_base;
        }
    }

    /**
     * @param $is_viewable
     * @param $post_type
     * @return bool
     */
    public function isPostTypeViewable($is_viewable, $post_type){

        return $is_viewable && ($post_type->query_var || $post_type->_builtin);
    }


    /**
     * ConfigPlugin constructor.
     */
    public function __construct()
    {
        global $_config;

        $this->config = $_config;
        $this->support = $this->config->get('support', []);

        if( $jpeg_quality = $this->config->get('image.compression', false) )
            add_filter( 'wp_editor_set_quality', function() use ($jpeg_quality){ return $jpeg_quality; });

        // Global init action
        add_action( 'init', function()
        {
            $this->disableFeatures();

            $this->defineThemeSupport();
            $this->addPostTypeSupport();

            $this->configureContentType();

            $this->addSidebars();
            $this->addRoles();

            add_filter('is_post_type_viewable', [$this, 'isPostTypeViewable'], 10 ,2);

            if( !HEADLESS || URL_MAPPING ){

                add_filter( 'post_type_link', [$this, 'updatePostTypePermalink'], 10, 2);
                add_filter( 'term_link', [$this, 'updateTermPermalink'], 10, 2);
            }

            if( is_admin() ){

                if( current_user_can('editor') || current_user_can('administrator') )
                    $this->addTableViews();

                if( $editor_style = $this->config->get('editor_style', false) )
                    add_editor_style( $editor_style );
            }
        });

        add_action('switch_blog', function($new_blog_id, $prev_blog_id){

            global $wp_rewrite;

            if( $new_blog_id != $prev_blog_id && $wp_rewrite )
                $this->configureContentType();

        }, 10, 2);

        // When viewing admin
        if( is_admin() )
        {

            if( !HEADLESS || URL_MAPPING )
                add_action( 'load-options-permalink.php', [$this, 'LoadPermalinks']);
        }
    }
}
