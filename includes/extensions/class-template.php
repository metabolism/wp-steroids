<?php


class WPS_Template {

    /**
     * The array of templates that this plugin tracks.
     */
    protected $config;


    /**
     * Add template to page/post template selector
     */
    public function addPostTemplates() {

        $templates = $this->config->get('template', []);

        // Add a filter to the wp 4.7 version attributes metabox
        foreach ($templates as $post_type=>$template)
        {
            add_filter( 'theme_'.$post_type.'_templates', function($post_templates) use($template){

                return array_merge($post_templates, $template);
            });
        }
    }


    /**
     * Initializes the plugin by setting filters and administration functions.
     * @param  $term_id
     */
    public function saveTaxonomy( $term_id ) {

        if( wp_term_is_shared($term_id) || !isset( $_POST['term_template'] ) )
            return;

        update_term_meta($term_id, 'template', $_POST['term_template']);
    }


    /**
     * Initializes the plugin by setting filters and administration functions.
     * @param \WP_Term $tag
     */
    public function termTemplateSelect( $tag ) {

        $types = $this->config->get('template.taxonomy.'.$tag->taxonomy, []);
        $type = get_term_meta($tag->term_id, 'template', true);

        ?><tr class="form-field">
        <th scope="row" valign="top"><label for="term_template"><?=__('Template', 'wp-steroids')?></label></th>
        <td>
            <select name="term_template" id="term_template">
                <option value="default"><?php _e('None'); ?></option>
                <?php
                foreach ($types as $value=>$label){
                    echo '<option value="'.$value.'" '.($type==$value?'selected':'').'>'.$label.'</option>';
                }
                ?>
            </select>
        </td>
        </tr><?php
    }


    /**
     * Add template to term
     */
    public function addTermTemplates() {

        $term_types = $this->config->get('template.taxonomy', []);

        // Add a filter to the wp 4.7 version attributes metabox
        foreach ($term_types as $taxonomy=>$types)
        {
            add_action( $taxonomy . '_edit_form_fields', [$this, 'termTemplateSelect'] );
        }

        add_action( 'edit_term', [$this, 'saveTaxonomy'] );
    }

    /**
     * Initializes the plugin by setting filters and administration functions.
     */
    public function __construct() {

        if( !is_admin() )
            return;

        global $_config;

        $this->config = $_config;

        $this->addPostTemplates();
        $this->addTermTemplates();
    }
}



function get_taxonomy_templates($taxonomy){

    global $_config;
    return $_config->get('template.taxonomy.'.$taxonomy, []);
}
