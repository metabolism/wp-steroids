<?php

use Timber\Post;
/**
 * Class BlogPost
 */
class WPS_Post extends Post
{
    /**
     * @param array $options
     * @return string
     */
    public function excerpt(array $options = []): string
    {
        //prevent infinite loop
        if( $this->post_excerpt || !has_blocks($this->post_content) )
            return parent::excerpt(array_merge($options,['read_more'=>false]));
        else
            return false;
    }

    /**
     * Detect excerpt
     *
     * @return bool
     */
    public function hasExcerpt(){

        return !empty($this->post_excerpt);
    }

    /**
     * Mapper for Metabolism/WordpressBundle compatibility
     *
     * @return mixed
     */
    public function customField(string $field_name){

        return $this->meta($field_name);
    }
}