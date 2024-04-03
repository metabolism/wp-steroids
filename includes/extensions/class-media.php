<?php

use Dflydev\DotAccessData\Data;

/**
 * Class
 */
class WPS_Media {

    /* @var Data $config */
    protected $config;

    protected $prevent_recursion;

    private static $upload_dir;

    /**
     * Quickly upload file
     * @param string $file
     * @param array $allowed_type
     * @param string $path
     * @param int $max_size
     * @return array|\WP_Error
     */
    public static function upload($file='file', $allowed_type = ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'], $path='/user', $max_size=1048576){

        if(empty($_FILES[$file]))
            return new \WP_Error('empty', 'File '.$file.' is empty');

        $file = $_FILES[$file];

        if ($file['error'] !== UPLOAD_ERR_OK)
            return new \WP_Error('error_upload', 'There was an error uploading your file.');

        if ($file['size'] > $max_size)
            return new \WP_Error('file_size', 'The file is too large');

        $mime_type = mime_content_type($file['tmp_name']);

        if( !in_array($mime_type, $allowed_type) )
            return new \WP_Error('file_format', 'Sorry, this file format is not permitted');

        $name = preg_replace("/[^A-Z0-9._-]/i", "_", basename( $file['name']) );

        $target_file = '/'.uniqid().'_'.$name;
        $upload_dir = WP_UPLOADS_DIR.$path;

        if( !is_dir($upload_dir) )
            mkdir($upload_dir, 0755, true);

        if( !is_writable($upload_dir) )
            return new \WP_Error('right', 'Upload directory is not writable.');

        if( move_uploaded_file($file['tmp_name'], $upload_dir.$target_file) )
            return ['filename' => str_replace('..', '', UPLOADS).$path.$target_file, 'original_filename' => basename( $file['name']), 'type' => $mime_type ];
        else
            return new \WP_Error('move', 'There was an error while writing the file.');
    }


    /**
     * delete attachment reference on other blog
     * @param $data
     * @param $attachment_ID
     * @return mixed
     */
    public function updateAttachment($data, $attachment_ID )
    {
        if( $this->prevent_recursion || !isset($_REQUEST['action']) || $_REQUEST['action'] != 'image-editor')
            return $data;

        $this->prevent_recursion = true;

        global $wpdb;

        $main_site_id = get_main_network_id();
        $current_site_id = get_current_blog_id();

        $original_attachment_id = $main_site_id == $current_site_id ? $attachment_ID : get_post_meta( $attachment_ID, '_wp_original_attachment_id', true );

        if( !$original_attachment_id )
            return $data;

        foreach ( get_sites() as $site ) {

            if ( (int) $site->blog_id !== $current_site_id ) {

                switch_to_blog( $site->blog_id );

                if( $main_site_id == $site->blog_id )
                {
                    wp_update_attachment_metadata($original_attachment_id, $data);
                }
                else
                {
                    $results = $wpdb->get_results( "select `post_id` from $wpdb->postmeta where `meta_value` = '$original_attachment_id' AND `meta_key` = '_wp_original_attachment_id'", ARRAY_A );

                    if( !empty($results) )
                        wp_update_attachment_metadata($results[0]['post_id'], $data);
                }
            }
        }

        switch_to_blog($current_site_id);

        $this->prevent_recursion = false;

        return $data;
    }


    /**
     * delete attachment reference on other blog
     * @param $attachment_ID
     */
    public function deleteAttachment( $attachment_ID )
    {
        if( $this->prevent_recursion )
            return;

        $this->prevent_recursion = true;

        global $wpdb;

        $main_site_id = get_main_network_id();
        $current_site_id = get_current_blog_id();

        $original_attachment_id = $main_site_id == $current_site_id ? $attachment_ID : get_post_meta( $attachment_ID, '_wp_original_attachment_id', true );

        if( !$original_attachment_id )
            return;

        foreach ( get_sites() as $site ) {

            if ( (int) $site->blog_id !== $current_site_id ) {

                switch_to_blog( $site->blog_id );

                if( $main_site_id == $site->blog_id )
                {
                    wp_delete_attachment($original_attachment_id);
                }
                else
                {
                    $results = $wpdb->get_results( "select `post_id` from $wpdb->postmeta where `meta_value` = '$original_attachment_id' AND `meta_key` = '_wp_original_attachment_id'", ARRAY_A );
                    if( !empty($results) )
                        wp_delete_attachment($results[0]['post_id']);
                }

            }
        }

        switch_to_blog($current_site_id);

        $this->prevent_recursion = false;
    }


    /**
     * update attachment to other blog by reference
     * @param $attachment_ID
     * @param $path
     * @param $new_path
     * @return void
     */
    public function mediaConvert( $attachment_ID, $path, $new_path )
    {
        $current_site_id = get_current_blog_id();

        $old_name = basename( $path );
        $new_name = basename( $new_path );
        $new_url = wp_get_attachment_url( $attachment_ID );

        $replaces = [$old_name => $new_name];

        $thumbs = wp_get_attachment_metadata( $attachment_ID );

        foreach( $thumbs['sizes'] as $img ){

            $old_thumb = str_replace( $new_name, $old_name, $img['file'] );
            $replaces[$old_thumb] = $img['file'];
        }

        global $wpdb;

        foreach ( get_sites() as $site ) {

            if ( (int) $site->blog_id !== $current_site_id ) {

                switch_to_blog( $site->blog_id );

                $results = $wpdb->get_results( "select `post_id` from $wpdb->postmeta where `meta_value` = '$attachment_ID' AND `meta_key` = '_wp_original_attachment_id'", ARRAY_A );

                if( !empty($results) ){

                    $post_id = $results[0]['post_id'];
                    wp_update_post(['ID' => $post_id, 'post_mime_type' => 'image/jpeg']);
                    $wpdb->update( $wpdb->posts, ['guid' => $new_url ], ['ID' => $post_id ], ['%s'], ['%d']);

                    $attach_data = wp_generate_attachment_metadata( $post_id, $new_path );
                    update_post_meta( $post_id, '_wp_attachment_metadata', $attach_data );
                }

                foreach( $replaces as $old => $new ){

                    $wpdb->query("UPDATE {$wpdb->posts} SET post_content = REPLACE( post_content, '/{$old}', '/{$new}') WHERE post_content LIKE '%/{$old}%'");
                    $wpdb->query("UPDATE {$wpdb->postmeta} SET meta_value = REPLACE( meta_value, '/{$old}', '/{$new}') WHERE meta_value LIKE '%/{$old}%'");
                    $wpdb->query("UPDATE {$wpdb->options} SET option_value = REPLACE( option_value, '/{$old}', '/{$new}') WHERE option_value LIKE '%/{$old}%'");
                }

                do_action('media_replace_value', $attachment_ID, $replaces);
            }
        }

        switch_to_blog($current_site_id);
    }


    /**
     * add attachment to other blog by reference
     * @param $attachment_ID
     * @return void
     */
    public function addAttachment( $attachment_ID )
    {
        if( $this->prevent_recursion )
            return;

        $this->prevent_recursion = true;

        $attachment = get_post( $attachment_ID );
        $current_site_id = get_current_blog_id();
        $main_site_id = get_main_network_id();

        if( !$attachment )
            return $attachment_ID;

        $attr = [
            'post_mime_type' => $attachment->post_mime_type,
            'filename'       => $attachment->guid,
            'post_title'     => $attachment->post_title,
            'post_status'    => $attachment->post_status,
            'post_parent'    => 0,
            'post_content'   => $attachment->post_content,
            'guid'           => $attachment->guid,
            'post_date'      => $attachment->post_date
        ];

        $file = get_attached_file( $attachment_ID );
        $attachment_metadata = wp_get_attachment_metadata( $attachment_ID );

        if( !$attachment_metadata )
            $attachment_metadata = wp_generate_attachment_metadata( $attachment_ID, $file );

        if(!isset($attachment_metadata['file']) ){

            $file = get_post_meta( $attachment_ID, '_wp_attached_file', true );
            $attachment_metadata['file'] = _wp_get_attachment_relative_path( $file ) . basename( $file );
        }

        $original_id = false;

        foreach ( get_sites() as $site ) {

            if ( (int) $site->blog_id !== $current_site_id ) {

                switch_to_blog( $site->blog_id );

                // check if post is already synced
                $attachment = get_posts(['post_type'=>'attachment', 'meta_key' => '_wp_original_attachment_id', 'meta_value' => $attachment_ID, 'fields'=>'ids']);

                if( !count($attachment) )
                {
                    // check if a post with the same file exist
                    $attachment = get_posts(['post_type'=>'attachment','fields'=>'ids',
                        'meta_query' => [
                            'relation' => 'AND',
                            [
                                'key'     => '_wp_attached_file',
                                'value'   => $attachment_metadata['file']
                            ],
                            [
                                'key'     => '_wp_original_attachment_id',
                                'compare' => 'NOT EXISTS'
                            ]
                        ]
                    ]);

                    if( !count($attachment) )
                    {
                        $inserted_id = wp_insert_attachment( $attr, $file );
                        if ( !is_wp_error($inserted_id) )
                        {
                            wp_update_attachment_metadata( $inserted_id, $attachment_metadata );

                            if( $main_site_id != $site->blog_id )
                                update_post_meta( $inserted_id, '_wp_original_attachment_id', $attachment_ID );
                            else
                                $original_id = $inserted_id;
                        }
                    }
                    else
                    {
                        if( $main_site_id != $site->blog_id )
                            update_post_meta( $attachment[0], '_wp_original_attachment_id', $attachment_ID );
                        else
                            $original_id = $attachment[0];
                    }
                }
                else
                {
                    if( $main_site_id != $site->blog_id )
                        $original_id = $attachment[0];
                }
            }
        }

        switch_to_blog( $current_site_id );

        if( $main_site_id != $current_site_id && $original_id )
            update_post_meta( $attachment_ID, '_wp_original_attachment_id', $original_id );

        $this->prevent_recursion = false;
    }


    /**
     * Unset thumbnail image
     * @param $post_ID
     * @return void
     */
    public function editAttachment($post_ID )
    {
        if( $this->prevent_recursion )
            return;

        $this->prevent_recursion = true;

        global $wpdb;

        $main_site_id = get_main_network_id();
        $current_site_id = get_current_blog_id();

        $original_attachment_id = $main_site_id == $current_site_id ? $post_ID : get_post_meta( $post_ID, '_wp_original_attachment_id', true );

        if( !$original_attachment_id || empty( $_REQUEST['attachments'] ) || empty( $_REQUEST['attachments'][ $post_ID ] ) )
            return;

        $attachment_data = $_REQUEST['attachments'][ $post_ID ];

        foreach ( get_sites() as $site ) {

            $attachement_id = false;
            if ( (int) $site->blog_id !== $current_site_id ) {

                switch_to_blog( $site->blog_id );

                if( $main_site_id == $site->blog_id ) {
                    $attachement_id = $original_attachment_id;
                }
                else
                {
                    $results = $wpdb->get_results( "select `post_id` from $wpdb->postmeta where `meta_value` = '$original_attachment_id' AND `meta_key` = '_wp_original_attachment_id'", ARRAY_A );

                    if( !empty($results) )
                        $attachement_id = $results[0]['post_id'];
                }

                if( $attachement_id ){

                    foreach ($attachment_data as $key=>$value)
                        update_post_meta( $attachement_id, $key, $value );
                }
            }
        }

        switch_to_blog($current_site_id);

        $this->prevent_recursion = false;
    }


    /**
     * add network parameters
     */
    public function wpmuOptions()
    {
        // Remove generated thumbnails option
        $thumbnails = $this->getThumbnails(true);

        if( count($thumbnails) )
        {
            echo '<h2>Images</h2>';
            echo '<table id="thumbnails" class="form-table"><tbody>';
            echo '<tr>
				<th scope="row">'.__('Generated thumbnails', 'wp-steroids').'</th>
				<td><a class="button button-primary" href="'.get_admin_url().'?clear_all_thumbnails">Remove '.count($thumbnails).' images</a></td>
			</tr>';

            if( $this->config->get('multisite.shared_media', false) )
                echo '<tr>
				<th scope="row">'.__('Multisite', 'wp-steroids').'</th>
				<td><a class="button button-primary" href="'.get_admin_url().'?syncronize_images">Synchronize images</a></td>
			</tr>';

            echo '</tbody></table>';
        }
    }


    /**
     * add admin parameters
     */
    public function adminInit()
    {
        if( !current_user_can('administrator') )
            return;

        if( isset($_GET['clear_thumbnails']) )
            $this->clearThumbnails();

        if( isset($_GET['clear_all_thumbnails']) )
            $this->clearThumbnails(true);

        if( isset($_GET['syncronize_images']) )
            $this->syncMedia();

        // Remove generated thumbnails option
        add_settings_field('clean_image_thumbnails', __('Generated thumbnails', 'wp-steroids'), function(){

            $thumbnails = $this->getThumbnails();

            if( count($thumbnails) )
                echo '<a class="button button-primary" href="'.get_admin_url().'?clear_thumbnails">'.__('Remove', 'wp-steroids').' '.count($thumbnails).' images</a>';
            else
                echo __('Nothing to remove', 'wp-steroids');

        }, 'media');
    }


    /**
     * Get all thumbnails
     * @param bool $all
     * @return array
     */
    private function getThumbnails($all=false)
    {
        $folder = wp_upload_dir();
        $folder = $folder['basedir'];

        if( is_multisite() && get_current_blog_id() != 1 && !$this->config->get('multisite.shared_media', false) && !$all )
            $folder = $folder. '/sites/' . get_current_blog_id() . '/';

        $file_list = [];

        if( is_dir($folder) )
        {
            $dir = new \RecursiveDirectoryIterator($folder);
            $ite = new \RecursiveIteratorIterator($dir);
            $files = new \RegexIterator($ite, '/(?!.*150x150).*-[0-9]+x[0-9]+(-c-default|-c-center)?(-[a-z0-9]*)?\.[a-z]{3,4}$/', \RegexIterator::GET_MATCH);
            $file_list = [];

            foreach($files as $file) {
                if( file_exists($file[0]) )
                    $file_list[] = $file[0];
            }
        }

        return $file_list;
    }


    /**
     * Remove all thumbnails
     * @param bool $all
     */
    private function clearThumbnails($all=false)
    {
        if ( current_user_can('administrator') && (!$all || is_super_admin()) )
        {
            $thumbnails = $this->getThumbnails($all);

            foreach($thumbnails as $file){
                if( file_exists($file) )
                    unlink($file);
            }
        }

        clearstatcache();

        wp_redirect( get_admin_url(null, $all?'network/settings.php':'options-media.php') );
        exit;
    }


    /**
     * Synchronize media across multisite instance
     */
    private function syncMedia()
    {
        if ( current_user_can('administrator') && is_super_admin() )
        {
            set_time_limit(0);

            $main_site_id = get_main_network_id();
            $current_site_id = get_current_blog_id();

            global $wpdb;

            switch_to_blog( $main_site_id );
            $results = $wpdb->delete( $wpdb->postmeta, ['meta_key' => '_wp_original_attachment_id']);
            restore_current_blog();

            $network_site_url = trim(network_site_url(), '/');

            foreach ( get_sites() as $site ) {

                switch_to_blog( $site->blog_id );

                //clean guid
                $home_url = get_home_url();
                $wpdb->query("UPDATE $wpdb->posts SET `guid` = REPLACE(guid, '$network_site_url$home_url', '$network_site_url') WHERE `guid` LIKE '$network_site_url$home_url%'");
                $wpdb->query("UPDATE $wpdb->posts SET `guid` = REPLACE(guid, '$home_url', '$network_site_url') WHERE `guid` LIKE '$home_url%' and `post_type`='attachment'");

                $original_attachment_ids = get_posts(['post_type'=>'attachment', 'meta_key' => '_wp_original_attachment_id', 'meta_compare' => 'NOT EXISTS', 'posts_per_page' => -1, 'fields'=>'ids']);

                foreach ($original_attachment_ids as $original_attachment_id)
                    $this->addAttachment($original_attachment_id);

                //clean duplicated posts
                $wpdb->query("DELETE p1 FROM $wpdb->posts p1 INNER JOIN $wpdb->posts p2 WHERE p1.ID > p2.ID AND p1.post_title = p2.post_title AND p1.`post_type`='attachment' AND p2.`post_type`='attachment'");
                $wpdb->query("DELETE pm FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL");
            }

            switch_to_blog($current_site_id);
        }

        wp_redirect( get_admin_url(null, 'network/settings.php') );
        exit;
    }


    /**
     * This function is to replace PHP's extremely buggy realpath().
     * @param string $path The original path, can be relative etc.
     * @param string $separator The separator.
     * @return string The resolved path, it might not exist.
     */
    private function realpath($path, $separator=DIRECTORY_SEPARATOR){

        $paths = explode($separator, $path);

        foreach ($paths as $key=>$path){
            if( $path == '..'){
                unset($paths[$key-1]);
                unset($paths[$key]);
            }
        }

        return implode($separator, $paths);
    }

    /**
     * Add relative key
     * @param $arr
     * @return mixed
     */
    public function uploadDir( $arr )
    {
        if( self::$upload_dir )
            return self::$upload_dir;

        $home_url = get_home_url();

        $arr['path'] = $this->realpath($arr['path']);
        $arr['basedir'] = $this->realpath($arr['basedir']);

        $arr['url'] =  $this->realpath($arr['url'], '/');
        $arr['baseurl'] =  $this->realpath($arr['baseurl'], '/');

        $arr['relative'] = str_replace($home_url, '', $arr['baseurl']);

        if( $this->config->get('multisite.shared_media', false) && is_multisite() && !is_main_site() ){

            $blog_url = get_home_url(get_main_site_id());
            $ms_dir = '/sites/' . get_current_blog_id();

            $arr['path'] = str_replace($ms_dir, '', $arr['path']);
            $arr['basedir'] = str_replace($ms_dir, '', $arr['basedir']);

            $arr['url'] =  str_replace($ms_dir, '', $arr['url']);
            $arr['url'] =  str_replace($home_url, $blog_url, $arr['url']);

            $arr['baseurl'] =  str_replace($ms_dir, '', $arr['baseurl']);
            $arr['baseurl'] =  str_replace($home_url, $blog_url, $arr['baseurl']);

            $arr['relative'] = str_replace($ms_dir, '', $arr['relative']);
        }

        self::$upload_dir = $arr;

        return $arr;
    }


    /**
     * Resize image on upload to ensure max size
     * @param $image_data
     * @return mixed
     */
    public function uploadResize( $image_data )
    {
        if( isset($_POST['name']) ){

            $info = pathinfo($_POST['name']);
            $info = explode('_', str_replace('-', '_', ($info['filename']??'')));

            if(count($info) && in_array($info[count($info)-1], ['hd','cmjk','cmjn']) )
                return $image_data;
        }

        $valid_types = array('image/png','image/jpeg','image/jpg');

        if(in_array($image_data['type'], $valid_types) && $this->config->get('image.resize', false) ){

            $src = $image_data['file'];

            $image_editor = wp_get_image_editor($src);

            if( is_wp_error($image_editor) )
                return $image_data;

            $sizes = $image_editor->get_size();
            $max_h = $this->config->get('image.resize.max_height', 2160);
            $max_w = $this->config->get('image.resize.max_width', 1920);

            if( ($sizes['width']??0) > $max_w || ($sizes['height']??0) > $max_h )
                $image_editor->resize($max_w, $max_h);

            $image_editor->set_quality(99);
            $image_editor->save($src);

        }

        return $image_data;
    }

    /**
     * Unset thumbnail image
     * @param $sizes
     * @return mixed
     */
    public function intermediateImageSizesAdvanced($sizes)
    {
        unset($sizes['medium'], $sizes['medium_large'], $sizes['large']);
        return $sizes;
    }


    /**
     * @param $actions
     * @param WP_Post $post
     * @param $detached
     * @return void
     */
    public function mediaRowActions($actions, $post, $detached ){

        if($post->post_mime_type === 'image/png'){

            $actions['convert'] = sprintf(
                '<a href="%s" class="submitconvert aria-button-if-js"%s aria-label="%s">%s</a>',
                wp_nonce_url( "post.php?action=convert&amp;post=$post->ID", 'convert-post_' . $post->ID ),
                '',
                /* translators: %s: Attachment title. */
                esc_attr( sprintf( __( 'Convert &#8220;%s&#8221;', 'wp-steroids' ), $post->post_title ) ),
                __( 'Convert to jpg', 'wp-steroids' )
            );
        }

        $metadata = wp_get_attachment_metadata($post->ID);

        if( empty($metadata) ){

            $actions['fix_metadata'] = sprintf(
                '<a href="%s" class="submitregenerate aria-button-if-js"%s aria-label="%s">%s</a>',
                wp_nonce_url( "post.php?action=regenerate_metadata&amp;post=$post->ID", 'fix_metadata-post_' . $post->ID ),
                '',
                /* translators: %s: Attachment title. */
                esc_attr( sprintf( __( 'Regenerate metadata &#8220;%s&#8221;', 'wp-steroids' ), $post->post_title ) ),
                __( 'Regenerate metadata', 'wp-steroids' )
            );
        }

        return $actions;
    }

    /**
     * @param $html
     * @param WP_Post $post
     * @return string
     */
    function mediaMeta($html, $post ){

        if($post->post_mime_type === 'image/png'){

            $html .= sprintf(
                '<a href="%s" class="submitconvert aria-button-if-js"%s aria-label="%s">%s</a>',
                wp_nonce_url( "post.php?action=convert&amp;post=$post->ID", 'convert-post_' . $post->ID ),
                '',
                /* translators: %s: Attachment title. */
                esc_attr( sprintf( __( 'Convert &#8220;%s&#8221;', 'wp-steroids' ), $post->post_title ) ),
                __( 'Convert to jpg', 'wp-steroids' )
            );
        }

        return $html;
    }

    /**
     * @param $path
     * @return false|string
     */
    public static function convertToJpg($path){

        if( !file_exists($path) || mime_content_type($path) !== 'image/png')
            return false;

        $img = imagecreatefrompng( $path );
        $bg = imagecreatetruecolor( imagesx( $img ), imagesy( $img ) );

        imagefill( $bg, 0, 0, imagecolorallocate( $bg, 255, 255, 255 ) );
        imagealphablending( $bg, 1 );
        imagecopy( $bg, $img, 0, 0, 0, 0, imagesx( $img ), imagesy( $img ) );

        $i = 1;

        $newPath = substr( $path, 0, -4 ) . '.jpg';

        while( file_exists( $newPath ) ){

            $newPath = substr( $path, 0, -4 ) . '-' . $i . '.jpg';
            ++$i;
        }

        if( imagejpeg( $bg, $newPath, 99 ) )
            return $newPath;

        return false;
    }


    /**
     * @param $post_id
     * @return void
     */
    public function postActionRegenerateMetadata($post_id){

        if( !$sendback = wp_get_referer() )
            $sendback = admin_url( 'upload.php' );

        $file = get_attached_file($post_id);
        $post = get_post($post_id);

        if (!empty($file)) {

            if( $post->post_mime_type == 'image/svg' || $post->post_mime_type == 'image/svg+xml' )
            {
                $image_meta = [
                    'filesize' => wp_filesize($file)
                ];
            }
            else{

                $imagesize = wp_getimagesize( $file );

                $image_meta = [
                    'width' => $imagesize[0],
                    'height' => $imagesize[1],
                    'filesize' => wp_filesize($file),
                    'file' => _wp_relative_upload_path($file),
                    'sizes' => []
                ];

                $exif_meta = wp_read_image_metadata( $file );

                if ( $exif_meta )
                    $image_meta['image_meta'] = $exif_meta;

                $pathinfo = pathinfo($file);
                $thumbnail = str_replace('.'.$pathinfo['extension'], '-150x150.'.$pathinfo['extension'], $file);

                if( is_readable($thumbnail) ){

                    $image_meta['sizes']['thumbnail'] = [
                        'file' => $pathinfo['basename'],
                        'width' => 150,
                        'height' => 150,
                        'mime-type' => $imagesize['mime'],
                        'filesize' => wp_filesize($thumbnail)
                    ];
                }
            }

            wp_update_attachment_metadata($post_id, $image_meta);
        }

        wp_redirect( $sendback );
        exit;
    }


    /**
     * @param $post_id
     * @return void
     */
    public function postActionConvert($post_id){

        if( !$sendback = wp_get_referer() )
            $sendback = admin_url( 'upload.php' );

        $post = get_post($post_id);

        $path = get_attached_file( $post_id );
        $url = wp_get_attachment_url( $post_id );

        $stats_before = filesize( $path );

        if( $new_path = self::convertToJpg($path) ){

            $old_name = basename( $path );
            $new_name = basename( $new_path );

            $new_url = str_replace( $old_name, $new_name, $url );

            if( $stats_before - filesize( $new_path ) > 0 ){

                $old_name_clean = substr( $old_name, 0, -4 );
                $new_name_clean = substr( $new_name, 0, -4 );

                $replaces = [$old_name => $new_name];

                unlink( $path );

                $thumbs = wp_get_attachment_metadata( $post_id );

                foreach( $thumbs['sizes'] as $img ){

                    $thumb = dirname( $path ) . '/' . $img['file'];

                    if( file_exists( $thumb ) ){

                        $new_thumb = substr( $img['file'], 0, -4 ) . '.jpg';

                        if( $old_name_clean !== $new_name_clean )
                            $new_thumb = str_replace( $old_name_clean, $new_name_clean, $new_thumb );

                        $replaces[ $img['file'] ] = $new_thumb;

                        unlink( $thumb );
                    }
                }

                wp_update_post(['ID' => $post_id, 'post_mime_type' => 'image/jpeg']);

                global $wpdb;

                $wpdb->update( $wpdb->posts, ['guid' => $new_url ], ['ID' => $post_id ], ['%s'], ['%d']);

                $meta = get_post_meta( $post_id, '_wp_attached_file', 1 );

                $meta = str_replace( $old_name, $new_name, $meta );
                update_post_meta( $post_id, '_wp_attached_file', $meta );

                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                $attach_data = wp_generate_attachment_metadata( $post_id, $new_path );
                update_post_meta( $post_id, '_wp_attachment_metadata', $attach_data );

                foreach( $replaces as $old => $new ){

                    $wpdb->query("UPDATE {$wpdb->posts} SET post_content = REPLACE( post_content, '/{$old}', '/{$new}') WHERE post_content LIKE '%/{$old}%'");
                    $wpdb->query("UPDATE {$wpdb->postmeta} SET meta_value = REPLACE( meta_value, '/{$old}', '/{$new}') WHERE meta_value LIKE '%/{$old}%'");
                    $wpdb->query("UPDATE {$wpdb->options} SET option_value = REPLACE( option_value, '/{$old}', '/{$new}') WHERE option_value LIKE '%/{$old}%'");
                }

                do_action('media_replace_value', $post_id, $replaces);
                do_action('media_convert', $post_id, $path, $new_path);
            }
            else{

                unlink($new_path);
            }
        }

        wp_redirect( $sendback );
        exit;
    }

    /**
     * @param $form_fields
     * @param $post
     * @return mixed
     */
    function addCropField($form_fields, $post ) {

        $field_value = get_post_meta( $post->ID, 'crop', true );

        $select = '<select name="attachments['.$post->ID.'][crop]">';

        foreach (['default', 'center', 'top', 'bottom', 'left', 'right'] as $option)
            $select .= '<option value="'.$option.'" '.($field_value==$option?'selected':'').'>'.__t(ucfirst($option)).'</option>';

        $select .= '</select>';

        $form_fields['crop'] = array(
            'value' => $field_value ? $field_value : '',
            'label' => __t( 'Crop' ),
            'input'  => 'select',
            'select'  => $select
        );

        return $form_fields;
    }

    /**
     * @param $attachment_id
     * @return void
     */
    function SaveAttachment($attachment_id ) {

        if ( isset( $_REQUEST['attachments'][ $attachment_id ]['crop'] ) ) {

            $custom_media_style = $_REQUEST['attachments'][ $attachment_id ]['crop'];
            update_post_meta( $attachment_id, 'crop', $custom_media_style );
        }
    }


    /**
     * Constructor
     */
    public function __construct()
    {
        global $_config;

        $this->config = $_config;

        add_filter('upload_dir', [$this, 'uploadDir'], 10, 2);
        add_filter('wp_calculate_image_srcset_meta', '__return_null');

        if( is_admin() )
        {
            add_action('admin_init', [$this, 'adminInit'] );
            add_action('wpmu_options', [$this, 'wpmuOptions'] );
            add_action('wp_handle_upload', [$this, 'uploadResize']);
            add_filter('intermediate_image_sizes_advanced', [$this, 'intermediateImageSizesAdvanced'] );
            add_filter('media_meta', [$this,'mediaMeta'], 10, 2);
            add_filter('media_row_actions', [$this,'mediaRowActions'], 10, 3);
            add_action('post_action_convert', [$this,'postActionConvert']);
            add_action('post_action_regenerate_metadata', [$this,'postActionRegenerateMetadata']);

            if( !class_exists('WP_Smart_Crop') ){

                add_filter('attachment_fields_to_edit', [$this, 'addCropField'], null, 2 );
                add_action('edit_attachment', [$this, 'SaveAttachment'] );
            }

            add_filter('manage_upload_columns', function( $columns ) {
                $columns['filesize'] = 'File Size';
                return $columns;
            });

            add_action('manage_media_custom_column', function ( $column_name, $media_item ){

                if ( 'filesize' != $column_name || !wp_attachment_is_image( $media_item ) )
                    return;

                $filesize = wp_filesize( get_attached_file( $media_item ) );
                $filesize = size_format($filesize, 2);

                echo $filesize;

            }, 10, 2 );

            // Replicate media on network
            if( $this->config->get('multisite.shared_media', false) && is_multisite() )
            {
                add_action('media_convert', [$this, 'mediaConvert'], 10, 3);
                add_action('add_attachment', [$this, 'addAttachment']);
                add_action('delete_attachment', [$this, 'deleteAttachment']);
                add_filter('wp_update_attachment_metadata', [$this, 'updateAttachment'], 10, 2);
                add_filter('wpmu_delete_blog_upload_dir', '__return_false' );
                add_action('edit_attachment', [$this, 'editAttachment'], 10 ,2);
            }
        }
    }
}
