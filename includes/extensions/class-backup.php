<?php

use Ifsnop\Mysqldump as IMysqldump;

/**
 * Class
 */
class WPS_Backup {

    private $zip;

    /**
     * Export folder, recursive
     * @param $source
     * @param array $exclude
     * @param bool $exclude_pattern
     * @return bool
     */
    public function dumpFolder($source, $exclude = [], $exclude_pattern=false)
    {
        if ( is_string( $source ) )
            $source_arr = [$source];
        else
            $source_arr = $source;

        foreach ( $source_arr as $source ) {

            $source = str_replace( '\\', '/', realpath( $source ) );
            $folder = "";

            if ( count( $source_arr ) > 1 ) {

                $folder = substr( $source, strrpos( $source, '/' ) + 1 ) . '/';
                $this->zip->addEmptyDir( $folder );
            }

            if ( is_dir( $source ) === true ) {

                $filter = function ($file, $key, $iterator) use ($exclude) {

                    if ($iterator->hasChildren() && !in_array($file->getFilename(), $exclude))
                        return true;

                    return $file->isFile();
                };

                $innerIterator = new \RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS);
                $iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator($innerIterator, $filter));

                foreach ( $iterator as $file ) {

                    $file = str_replace( '\\', '/', $file );

                    if( $exclude_pattern && preg_match($exclude_pattern, $file))
                        continue;

                    $file = realpath( $file );

                    if ( is_dir( $file ) === true ) {

                        $this->zip->addEmptyDir( $folder . str_replace( $source . '/', '', $file . '/' ) );
                    }
                    else {

                        if ( is_file( $file ) === true ) {

                            $localname = $folder . str_replace( $source . '/', '', $file );
                            $this->zip->addFile($file, $localname);
                            $this->zip->setCompressionName($localname, \ZipArchive::CM_STORE);
                        }
                    }
                }
            }
            else {

                if ( is_file( $source ) === true ) {

                    $localname = $folder . basename( $source );
                    $this->zip->addFile($source, $localname);
                    $this->zip->setCompressionName($localname, \ZipArchive::CM_STORE);
                }
            }
        }

        return true;
    }


    /**
     * Export database
     * @param $path
     * @param bool $global
     * @return string|WP_Error
     */
    private function dumpDatabase($path, $global = false)
    {
        $localname = 'db.sql';
        $file = $path.'/'.$localname;

        try {

            global $wpdb;

            if( is_file($file) )
                unlink($file);

            $settings = ['add-drop-table' => true];

            if (is_multisite() && !$global)
                $settings['include-tables'] = array_values($wpdb->tables('blog'));

            $dump = new IMysqldump\Mysqldump('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD, $settings);
            $dump->start($file);

            if( is_file($file) ){

                $this->zip->addFile($file, $localname);
                $this->zip->setCompressionName($localname, \ZipArchive::CM_DEFAULT);

                return $file;
            }

            return new \WP_Error('mysqldump-error', 'Could not create database file');
        }
        catch (\Exception $e)
        {
            if( is_file($file) )
                unlink($file);

            return new \WP_Error('mysqldump-error', $e->getMessage());
        }
    }


    /**
     * Create zip file
     * @param $destination
     * @return \WP_Error|\ZipArchive
     */
    public function init($destination){

        if ( !extension_loaded( 'zip' ) )
            return new \WP_Error('zip_extension', 'Zip Extension is not loaded');

        $this->zip = new \ZipArchive();

        if ( $this->zip->open( $destination, \ZipArchive::CREATE ) !== true )
            return new \WP_Error('archive', 'Can\'t create archive file');

        return $this->zip;
    }


    /**
     * Bundle SQL and Uploads
     * @param $global
     * @param $type
     * @param $filename
     * @return bool|string
     * @throws \Random\RandomException
     */
    private function bundle($global, $type, $filename)
    {
        $backup = false;

        if ( current_user_can('administrator') && (!$global || is_super_admin()) )
        {
            $db_file = false;
            $folder = wp_upload_dir();
            $rootPath = $folder['basedir'];
            $backup_dir = sys_get_temp_dir().'/wps-backup-'.bin2hex(random_bytes(16));

            if (!mkdir($backup_dir, 0700))
                wp_die('Unable to create backup directory');

            $backup = $backup_dir.'/'.$filename;

           $zip = $this->init($backup);

            if( is_wp_error($zip) )
                wp_die( $zip->get_error_message() );

            if( is_file($backup) )
                return $backup;

            if( in_array($type, ['all', 'sql']) ){

                $db_file = $this->dumpDatabase($backup_dir, $global);

                if( is_wp_error($db_file) )
                    wp_die( $db_file->get_error_message() );
            }

            if( $type == 'all' || $type == 'uploads')
                $this->dumpFolder($rootPath, ['wpallimport', 'cache', 'wpcf7_uploads', 'acf-thumbnails', 'wp-personal-data-exports'], '/(?!.*150x150).*-[0-9]+x[0-9]+(-c-default|-c-center)?\.[a-z]{3,4}$/');

            $status = $this->close();

            if( in_array($type, ['all', 'sql']) && is_file($db_file) )
                unlink($db_file);

            if( $status && is_file($backup) )
                return $backup;
            else
                wp_die('Can\'t generate archive file');
        }

        return $backup;
    }


    /**
     * Generate and download zip file
     * @param bool $global
     * @param string $type
     * @throws \Random\RandomException
     */
    private function download($global=false, $type='all')
    {
        @ini_set('max_execution_time', 60);

        $filename = 'backup-'.$type.'-'.date('Ymd').'.zip';

        if ( current_user_can('administrator') && (!$global || is_super_admin()) )
        {
            if( $backup = $this->bundle($global, $type, $filename) )
            {
                $this->stream($backup);
                exit(0);
            }
        }

        wp_redirect( get_admin_url(null, $global?'network/settings.php':'options-'.($type=='uploads'?'media':'general').'.php') );
        exit;
    }


    /**
     * Close zip to write file
     */
    public function close()
    {
        return $this->zip->close();
    }


    /**
     * Stream file to browser
     * @param $file
     */
    private function stream($file)
    {
        try {

            WPS_Stream::send($file);

        } finally {

            if (is_file($file))
                unlink($file);

            $directory = dirname($file);

            if (is_dir($directory))
                rmdir($directory);
        }
    }


    /**
     * add network parameters
     */
    public function wpmuOptions()
    {
        if(!current_user_can('administrator'))
            return;

        echo '<table id="backup" class="form-table">
			<tbody><tr>
				<th scope="row"><h2>'.__('Backup', 'wp-steroids').'</h2></th>
				<td>
				  <a class="button button-primary" href="'.get_admin_url().'?download_mu_backup&type=all&_wpnonce='.wp_create_nonce('download_mu_backup').'">'.__('Download All', 'wp-steroids').'</a>
				  <a class="button button-primary" href="'.get_admin_url().'?download_mu_backup&type=sql&_wpnonce='.wp_create_nonce('download_mu_backup').'">'.__('Download SQL', 'wp-steroids').'</a>
				  <a class="button button-primary" href="'.get_admin_url().'?download_mu_backup&type=uploads&_wpnonce='.wp_create_nonce('download_mu_backup').'">'.__('Download Uploads', 'wp-steroids').'</a>
				</td>
			</tr>
		</tbody></table>';
    }


    /**
     * add admin parameters
     */
    public function adminInit()
    {
        if( !current_user_can('administrator') )
            return;

        if( isset($_GET['download_backup']) && check_admin_referer('download_backup') )
            $this->download(false, $_GET['type'] ?? 'all');

        if( isset($_GET['download_mu_backup']) && check_admin_referer('download_mu_backup') )
            $this->download(true, $_GET['type'] ?? 'all');

        add_settings_field('download_backup', __('Database', 'wp-steroids'), function(){

            echo '<a class="button button-primary" href="'.get_admin_url().'?download_backup&type=sql&_wpnonce='.wp_create_nonce('download_backup').'">'.__('Download backup', 'wp-steroids').'</a> ';

        }, 'general');

        add_settings_field('download_backup', __('Uploads', 'wp-steroids'), function(){

            echo '<a class="button button-primary" href="'.get_admin_url().'?download_backup&type=uploads&_wpnonce='.wp_create_nonce('download_backup').'">'.__('Download backup', 'wp-steroids').'</a>';

        }, 'media');
    }


    /**
     * Constructor
     */
    public function __construct()
    {
        if( !is_admin() || !WP_DEBUG )
            return;

        add_action( 'admin_init', [$this, 'adminInit'] );
        add_action( 'wpmu_options', [$this, 'wpmuOptions'] );
    }
}

