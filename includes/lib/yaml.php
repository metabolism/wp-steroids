<?php


class WPS_Yaml {

    public static function load($name, $file){

        if( !file_exists($file) || !is_readable($file) )
            wp_die( 'File '.basename($file).'does not exists');

        $filemtime = filemtime($file);

        $option = get_option($name);

        if( $option && ($option['version']??false) == WPS_VERSION && ($option['filemtime']??0) == $filemtime && isset($option['config'])){

            $config = $option['config'];
        }
        else{

            try{

                $config = Spyc::YAMLLoad($file);
                update_option($name, ['version'=>WPS_VERSION, 'config'=>$config, 'filemtime'=>$filemtime], true);
            }
            catch (Exception $e){

                wp_die(basename($file).' loading error: '.$e->getMessage());
            }
        }

        return $config;
    }
}