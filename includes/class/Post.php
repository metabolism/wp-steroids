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
        if( $this->post_excerpt || !has_blocks($this->post_content) )
            return parent::excerpt(array_merge($options,['read_more'=>false]));
        else
            return false;
    }
}