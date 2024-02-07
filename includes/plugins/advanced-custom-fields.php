<?php

use Dflydev\DotAccessData\Data;

/**
 * Class
 */
class WPS_Advanced_Custom_Fields{

    /* @var Data $config */
    private $config;


    /**
     * Add settings to acf
     */
    public function addSettings()
    {
        $acf_settings = $this->config->get('acf.settings', []);

        foreach ($acf_settings as $name=>$value){

            if( acf_get_setting($name) !== $value )
                acf_update_setting($name, $value);
        }

        if( defined('GOOGLE_MAP_API_KEY') &&  acf_get_setting('google_api_key') !== GOOGLE_MAP_API_KEY )
            acf_update_setting('google_api_key', GOOGLE_MAP_API_KEY);

        $acf_user_settings = $this->config->get('acf.user_settings', []);

        foreach ($acf_user_settings as $name=>$value){

            if( acf_get_user_setting($name) !== $value )
                acf_update_user_setting($name, $value);
        }
    }


    /**
     * Add WordPress configuration 'options_page' fields as ACFHelper Options pages
     */
    public function addOptionPages()
    {
        if( function_exists('acf_add_options_page') )
        {
            $args = ['autoload' => true, 'page_title' => __t('Options'), 'menu_slug' => 'acf-options'];

            acf_add_options_page($args);

            $options = $this->config->get('options_page', []);

            //retro compat
            $options = array_merge($options, $this->config->get('acf.options_page', []));

            foreach ( $options as $name=>$args ){

                if( is_array($args) ){

                    $args = array_merge(['page_title'=>__t(ucfirst($name)), 'menu_slug'=>$name, 'autoload'=>true], $args);
                }
                else{

                    if( !empty($args) )
                        $name = $args;

                    $args = ['page_title'=>__t(ucfirst($name)), 'menu_slug'=>sanitize_title($name), 'autoload'=>true];
                }

                acf_add_options_sub_page($args);

                $this->addFields($args['menu_slug'], $args, 'group', 'options_page');
            }
        }
    }


    /**
     * Customize basic toolbar
     * @param $toolbars
     * @return array
     */
    public function editToolbars($toolbars){

        $custom_toolbars = $this->config->get('acf.toolbars', false);

        return $custom_toolbars ?: $toolbars;
    }


    /**
     * Add theme to field selection
     * @param $field
     * @return array
     */
    public function prepareField($field){

        $lock_max_length = $this->config->get('acf.input.lock_max_length', true);

        if( !$lock_max_length && ($field['type'] == 'text' ||  $field['type'] == 'textarea')){

            if( !empty($field['maxlength']??'') ){

                $field['maxlength_hint'] = $field['maxlength'];
                $field['maxlength'] = '';
            }
        }

        if( $field['type'] == 'select' && $field['_name'] == 'taxonomy'){

            $types = $this->config->get('template.taxonomy', []);
            $all_templates = [];

            foreach ($types as $type=>$templates){
                foreach ($templates as $key=>$name){
                    $all_templates['template_'.$type.':'.$key] = ucfirst(str_replace('_', ' ', $type)).' : '.$name;
                }
            }
            $field['choices'][__t('Template')] = $all_templates;
        }

        return $field;
    }

    /**
     * Disable database query for non editable field
     * @param $unused
     * @param $post_id
     * @param $field
     * @return string|null
     */
    public function preLoadValue($unused, $post_id, $field){

        if( is_array($field) && ($field['type'] == 'message' || $field['type'] == 'tab') )
            return '';

        return null;
    }


    /**
     * Filter preview sizes
     * @param $sizes
     * @return array
     */
    public function getImageSizes($sizes){

        return ['thumbnail'=>$sizes['thumbnail'], 'full'=>$sizes['full']];
    }


    /**
     * Change query to replace template by term slug
     * @param $args
     * @param $field
     * @param $post_id
     * @return mixed
     */
    public function filterPostsByTermTemplateMeta($args, $field, $post_id ){

        if( $field['type'] == 'relationship' && isset($field['taxonomy'])){

            foreach ($args['tax_query'] as $id=>&$taxonomy){

                if( is_array($taxonomy) && strpos($taxonomy['taxonomy'], 'template_') === 0){

                    $taxonomy['taxonomy'] = str_replace('template_','', $taxonomy['taxonomy']);

                    $terms = get_terms($taxonomy['taxonomy']);
                    $terms_by_template = [];
                    foreach ($terms as $term){
                        $template = get_term_meta($term->term_id, 'template');
                        if(!empty($template) )
                            $terms_by_template[$template[0]][] = $term->slug;
                    }

                    $terms = [];
                    foreach ($taxonomy['terms'] as $template){
                        if( isset($terms_by_template[$template]) )
                            $terms = array_merge($terms, $terms_by_template[$template]);
                    }

                    $taxonomy['terms'] = $terms;
                }
            }
        }

        return $args;
    }

    /**
     * @param $fields
     * @return mixed
     */
    public function load_fields($fields){

        global $post;

        if ($post && $post->ID && get_post_type($post->ID) == 'acf-field-group')
            return $fields;

        foreach ($fields as &$field){

            if( isset($field['placeholder']) )
                $field['placeholder'] = __t($field['placeholder']);

            if( $field['type'] == 'flexible_content'){

                $field['button_label'] = __t($field['button_label']);

                foreach ($field['layouts'] as &$layout)
                    $layout['label'] = __t($layout['label']);
            }
            elseif( $field['type'] == 'tab'){

                $field['label'] = __t($field['label']);
            }
            elseif( $field['type'] == 'true_false'){

                $field['message'] = __t($field['message']);
            }
            elseif( in_array($field['type'], ['select', 'radio', 'checkbox']) ){

                foreach($field['choices'] as $key=>&$value)
                    $value = __t($value);
            }
        }

        return $fields;
    }

    /**
     * @param $field
     * @return mixed
     */
    public function render_field($field) {

        if( in_array($field['type'], ['text','textarea','wysiwyg','inline_editor']) ){

            if( defined('GOOGLE_TRANSLATE_KEY') && GOOGLE_TRANSLATE_KEY )
                echo '<a class="wps-translate wps-translate--google" title="'.__t('Translate with Google').'"></a>';
            elseif( defined('DEEPL_KEY') && DEEPL_KEY )
                echo '<a class="wps-translate wps-translate--deepl" title="'.__t('Translate with Deepl').'"></a>';
        }

        return $field;
    }

    /**
     * @param $allowed_block_types
     * @param $block_editor_context
     * @return array|bool
     */
    public function allowedBlockTypes($allowed_block_types, $block_editor_context) {

        if ( empty( $block_editor_context->post ) || !$block_types = acf_get_store( 'block-types' ) )
            return $allowed_block_types;

        $blocks = $block_types->get();
        $field_groups = acf_get_field_groups();

        $allowed_block_types = [];

        foreach ($blocks as $name=>$block){

            foreach ($field_groups as $index=>$field_group){

                if(($field_group['location'][0][0]['value']??'') == $name){

                    unset($blocks[$name]);

                    if( acf_get_field_group_visibility($field_group, ['block'=>$name, 'post_id'=>get_the_ID()]) )
                        $allowed_block_types[] = $name;
                }
            }
        }

        return array_merge(array_unique($allowed_block_types), array_keys($blocks));
    }

    /**
     * @param $result
     * @param $rule
     * @param $screen
     * @param $field_group
     * @return false|mixed
     */
    public function matchRules($result, $rule, $screen, $field_group){

        if(isset($screen['block'])){

            $has_block = false;
            foreach($field_group['location'][0] as $location){

                if( $location['param'] == 'block'){

                    $has_block = true;
                    break;
                }
            }

            if( !$has_block )
                return false;
        }

        return $result;
    }

    /**
     * @param $block
     * @param $content
     * @param $is_preview
     * @return mixed|null
     */
    public function block_render_callback($block, $content = '', $is_preview = false){

        if( isset($block['post']) && $id = get_the_ID() ){

            $post = get_post($id);

            if( $post_cache = wp_cache_get($post->ID, 'posts') ){

                foreach ($block['post'] as $key=>$value){

                    if( isset($post_cache->$key) )
                        $post_cache->$key = $value;
                }

                wp_cache_set( $post->ID, $post_cache,'posts' );
            }

            if( isset($block['post']['thumbnail_id']) ){

                $thumbnail_id = get_post_thumbnail_id($post->ID);

                if( $meta_cache = wp_cache_get($post->ID, 'post_meta') )
                    $meta_cache['_thumbnail_id'] = [$block['post']['thumbnail_id']];

                wp_cache_set($post->ID, $meta_cache, 'post_meta');
            }
        }

        return apply_filters('block_render_callback', $block, $content, $is_preview);
    }

    public function generateHash($prefix, $key)
    {
        return $prefix.'_'.substr(md5($key), 0, 12);
    }

    /**
     * Adds Gutenberg blocks
     * @see https://www.advancedcustomfields.com/resources/acf_register_block_type/
     */
    public function addBlocks()
    {
        if( !function_exists('acf_register_block_type') )
            return;

        $render_template = $this->config->get('gutenberg.render_template', '');
        $preview_image = $this->config->get('gutenberg.preview_image', false);

        $upload_dir = wp_upload_dir();

        foreach ( $this->config->get('block', []) as $name => $args )
        {
            $block = [
                'name'              => $name,
                'title'             => __t($args['title']??$name),
                'description'       => __t($args['description']??''),
                'render_template'   => $args['render_template']??str_replace('{name}', $name, $render_template),
                'category'          => $args['category']??'layout',
                'icon'              => $args['icon']??'admin-comments',
                'mode'              => $args['mode']??'preview',
                'keywords'          => $args['keywords']??[],
                'post_types'        => $args['post_types']??[],
                'supports'          => $args['supports']??[],
                'front'             => $args['front']??true
            ];

            $block['render_callback'] = [$this, 'block_render_callback'];

            $block['supports']['align'] = boolval($args['supports']['align']??false);
            $block['supports']['align_text'] = boolval($args['supports']['align_text']??false);
            $block['supports']['align_content'] = boolval($args['supports']['align_content']??false);

            if( substr($args['icon']??'', -4) == '.svg' )
                $block['icon'] = file_get_contents(ABSPATH.'/'.$args['icon']);

            if( $_preview_image = $args['preview_image']??$preview_image ){

                if( is_string($_preview_image) )
                    $_preview_image = str_replace('{name}', $name, $preview_image);
                else
                    $_preview_image = $upload_dir['relative'].'/blocks/'.$name.'.jpg';


                $block['example'] = [
                    'attributes' => [
                        'mode' => 'preview',
                        'data' => [
                            '_preview_image' => $_preview_image,
                        ]
                    ]
                ];
            }

            acf_register_block_type($block);

            $this->addFields($name, $args, 'group', 'block');
        }
    }

    /**
     * @param $name
     * @param $args
     * @param $prefix
     * @param $location
     * @return void
     */
    public function addFields($name, $args, $prefix, $location)
    {
        if( isset($args['fields']) ){

            $fields = [];
            $key = $name;

            foreach ($args['fields'] as $field_name=>$field_args)
                $fields[] = $this->createField($key, $field_name, $field_args);

            $field_group = [
                'key' => $this->generateHash($prefix, $key),
                'title' => 'Fields',
                'fields' => $fields,
                'location' => [
                    [
                        [
                            'param' => $location,
                            'operator' => '==',
                            'value' => ($location=='block'?'acf/':'').$name
                        ]
                    ]
                ]
            ];

            if( $location == 'options_page')
                $field_group['style'] = 'seamless';

            acf_add_local_field_group($field_group);
        }
    }


    /**
     * @param $parent_name
     * @param $field_name
     * @param $field_args
     * @return array
     */
    public function createField($parent_name, $field_name, $field_args)
    {
        if( ($field_args['type']??'') == 'clone' ){

            $component = $this->shared_fields[$field_args['id']??$field_name]??[];
            unset($field_args['type'], $field_args['id']);

            $field_args = array_merge($component, $field_args);
        }

        $type = $field_args['type']??'text';

        $key = $parent_name.'_'.$field_name;

        $field = array_merge($field_args, [
            'key' => $this->generateHash('field', $key),
            'label' => $field_args['label']??ucfirst($field_name),
            'name' => $field_name,
            'type' => $type
        ]);

        $subfields = [];

        foreach ($field_args['sub_fields'] as $subfield_name=>$subfield_args)
            $subfields[] = $this->createField($key, $subfield_name, $subfield_args);

        $field['sub_fields'] = $subfields;

        return $field;
    }

    /**
     * Adds specific post types here
     * @see CustomPostType
     */
    public function addPostTypesArchivePage()
    {
        $registered_post_types = $this->config->get('post_type', []);

        foreach ( $registered_post_types as $post_type => &$args )
        {
            if( !in_array($post_type, ['post', 'page', 'edition']) )
            {
                if( isset($args['has_options']) && function_exists('acf_add_options_sub_page') ) {

                    $name = str_replace('_', ' ', $post_type);

                    if( is_bool($args['has_options']) ) {

                        $args = [
                            'page_title' 	=> ucfirst($name).' archive options',
                            'menu_title' 	=> __t('Archive options'),
                            'autoload'   	=> true
                        ];
                    }

                    $args['menu_slug']   = 'options_'.$post_type;
                    $args['parent_slug'] = 'edit.php?post_type='.$post_type;

                    acf_add_options_sub_page($args);

                    $this->addFields($args['menu_slug'], $args, 'group', 'options_page');
                }
            }
        }
    }

    /**
     * Add quick link top bar archive button
     * @param $wp_admin_bar
     */
    public function editBarMenu($wp_admin_bar)
    {
        $object = get_queried_object();

        if( is_post_type_archive() && !is_admin() && current_user_can( $object->cap->edit_posts ) )
        {
            if( $this->config->get('post_type.'.$object->name.'.has_options', false) ){

                $args = [
                    'id'    => 'archive_options',
                    'title' => __t('Edit archive options'),
                    'href'  => get_admin_url( null, '/edit.php?post_type='.$object->name.'&page=options_'.$object->name ),
                    'meta'   => ['class' => 'ab-item']
                ];

                $wp_admin_bar->add_node( $args );
            }
        }
    }


    /**
     * ACFPlugin constructor.
     */
    public function __construct()
    {
        if( !class_exists('ACF') )
            return;

        global $_config;

        $this->config = $_config;

        add_action('init', [$this, 'addBlocks']);

        add_filter('acf/pre_load_value', [$this, 'preLoadValue'], 10, 3);
        add_filter('acf/prepare_field', [$this, 'prepareField']);
        add_filter('acf/fields/relationship/query/name=items', [$this, 'filterPostsByTermTemplateMeta'], 10, 3);
        add_filter('acf/get_image_sizes', [$this, 'getImageSizes'] );

        add_filter('acf/get_field_label', [WPS_Translation::class, 'translate'], 9);
        add_filter('acf/load_fields', [$this, 'load_fields'], 9);

        if( $path = $this->config->get('acf.json_path', '/config/packages/acf') ){

            add_filter('acf/settings/save_json', function() use($path){ return ABSPATH.'../..'.$path; });
            add_filter('acf/settings/load_json', function() use($path){ return [ABSPATH.'../..'.$path]; });
        }

        add_action( 'admin_bar_menu', [$this, 'editBarMenu'], 80);

        // When viewing admin
        if( is_admin() )
        {
            // Setup ACFHelper Settings
            add_action( 'acf/init', [$this, 'addSettings'] );
            add_action( 'acf/init', [$this, 'addPostTypesArchivePage'] );
            add_filter( 'acf/fields/wysiwyg/toolbars' , [$this, 'editToolbars']  );
            add_action( 'init', [$this, 'addOptionPages'] );
            add_filter( 'acf/settings/show_admin', function() {
                return current_user_can('administrator');
            });

            add_filter( 'acf/location/match_rule', [$this, 'matchRules'], 10, 4);

            add_filter( 'acf/location/screen', function($screen) {

                if( !isset($screen['post_id']) && isset($screen['block']) && $post_id = get_the_ID())
                    $screen['post_id'] = $post_id;

                return $screen;
            });

            add_filter( 'allowed_block_types_all', [$this, 'allowedBlockTypes'], 99, 2 );

            if( !is_main_site() )
                add_filter('acf/render_field', [$this, 'render_field']);
        }
    }
}
