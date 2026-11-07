<?php


class WPS_Stream {

    public static function send($file)
    {
        if( !is_file($file) || !is_readable($file) || headers_sent() )
            return false;

        $input = fopen($file, 'rb');

        if( $input === false )
            return false;

        $size = fstat($input)['size'];

        // A buffered response would otherwise retain the whole download in memory.
        while( ob_get_level() > 0 ){

            if( !@ob_end_clean() ){
                fclose($input);
                return false;
            }
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.str_replace(['"', "\r", "\n"], '', basename($file)).'"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: private, no-store, no-transform');
        header('Pragma: no-cache');
        header('Content-Length: ' . $size);

        ignore_user_abort(true);

        $sent = 0;

        try {
            while( !feof($input) ){

                $chunk = fread($input, 1024 * 1024);

                if( $chunk === false || ($chunk === '' && !feof($input)) )
                    return false;

                $sent += strlen($chunk);
                echo $chunk;
                flush();
            }

            return $sent === $size;
        }
        finally {
            fclose($input);
        }
    }
}


function wp_stream($file){
    return WPS_Stream::send($file);
}
