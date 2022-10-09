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

                $directory = new \RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS);
                $filtered = new WPS_Dir_Filter($directory, $exclude);
                $iterator = new \RecursiveIteratorIterator($filtered, \RecursiveIteratorIterator::SELF_FIRST);

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
     * @return bool|\WP_Error
     */
    private function dumpDatabase($path)
    {
        try {
            $localname = 'db.sql';
            $file = $path.'/'.$localname;

            if( file_exists($file) )
                unlink($file);

            $dump = new IMysqldump\Mysqldump('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD, ['add-drop-table' => true]);
            $dump->start($file);

            if( file_exists($file) ){

                $this->zip->addFile($file, $localname);
                $this->zip->setCompressionName($localname, \ZipArchive::CM_DEFAULT);
            }

            return true;
        }
        catch (\Exception $e)
        {
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

        if ( !$this->zip->open( $destination, \ZipArchive::CREATE ) )
            return new \WP_Error('archive', 'Can\'t create archive file');

        return $this->zip;
    }


    /**
     * Bundle SQL and Uploads
     * @param $global
     * @param $type
     * @param $filename
     * @return bool|string
     */
    private function bundle($global, $type, $filename)
    {
        $backup = false;

        if ( current_user_can('administrator') && (!$global || is_super_admin()) )
        {
            $folder = wp_upload_dir();
            $rootPath = $folder['basedir'];

            $backup = $rootPath.'/'.$filename;

            $this->init($backup);

            if( is_wp_error($this->zip) )
                wp_die( $this->zip->get_error_message() );

            if( file_exists($backup) )
                return $backup;

            if( $type == 'all' || $type == 'sql'){

                $db = $this->dumpDatabase($rootPath);

                if( is_wp_error($db) )
                    wp_die( $db->get_error_message() );
            }

            if( $type == 'all' || $type == 'uploads')
                $this->dumpFolder($rootPath, ['wpallimport', 'cache', 'wpcf7_uploads', 'acf-thumbnails', 'wp-personal-data-exports'], '/(?!.*150x150).*-[0-9]+x[0-9]+(-c-default|-c-center)?\.[a-z]{3,4}$/');

            $this->close();

            if( $type == 'all' || $type == 'sql')
                unlink($rootPath.'/db.sql');

            if( file_exists($backup) )
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
        if( WPS_Stream::send($file) )
            unlink($file);
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
				  <a class="button button-primary" href="'.get_admin_url().'?download_mu_backup&type=all">'.__('Download All', 'wp-steroids').'</a>
				  <a class="button button-primary" href="'.get_admin_url().'?download_mu_backup&type=sql">'.__('Download SQL', 'wp-steroids').'</a>
				  <a class="button button-primary" href="'.get_admin_url().'?download_mu_backup&type=uploads">'.__('Download Uploads', 'wp-steroids').'</a>
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

        if( isset($_GET['download_backup']) )
            $this->download(false, $_GET['type'] ?? 'all');

        if( isset($_GET['download_mu_backup']) )
            $this->download(true, $_GET['type'] ?? 'all');

        add_settings_field('download_backup', __('Database', 'wp-steroids'), function(){

            echo '<a class="button button-primary" href="'.get_admin_url().'?download_backup&type=sql">'.__('Download backup', 'wp-steroids').'</a> ';

        }, 'general');

        add_settings_field('download_backup', __('Uploads', 'wp-steroids'), function(){

            echo '<a class="button button-primary" href="'.get_admin_url().'?download_backup&type=uploads">'.__('Download backup', 'wp-steroids').'</a>';

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

