<?php

use Timber\Post;

if ( class_exists('Timber\Post') ) {

    class WPS_Post extends Post
    {
        /**
         * @param array $options
         * @return string
         */
        public function excerpt(array $options = []): string
        {
            //prevent infinite loop
            if ($this->post_excerpt || !has_blocks($this->post_content))
                return parent::excerpt(array_merge($options, ['read_more' => false]));
            else
                return false;
        }

        /**
         * @return string
         */
        public function template(): string
        {
            return get_page_template_slug($this->id);
        }

        /**
         * Detect excerpt
         *
         * @return bool
         */
        public function hasExcerpt()
        {
            return !empty($this->post_excerpt);
        }

        /**
         * Mapper for Metabolism/WordpressBundle compatibility
         *
         * @return mixed
         */
        public function customField(string $field_name)
        {
            return $this->meta($field_name);
        }

        /**
         * Mapper for Metabolism/WordpressBundle compatibility
         *
         * @return mixed
         */
        public function term($query_args = [], $options = [])
        {
            if (!is_array($query_args) || isset($query_args[0]))
                $query_args = ['taxonomy' => $query_args];

            $query_args['number'] = 1;

            $terms = $this->terms($query_args, $options);

            return $terms[0] ?? null;
        }
    }
}
