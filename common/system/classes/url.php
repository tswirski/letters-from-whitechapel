<?php defined('SYSPATH') or die('No direct script access.');

class URL extends Kohana_URL
{
    
    public static function protocol()
    {
        if ( 
               ( ! empty($_SERVER['HTTPS']) &&  $_SERVER['HTTPS'] !== 'off')
               || $_SERVER['SERVER_PORT'] == 443
               || (
                       !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) 
                    && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' 
                    || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) 
                    && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
        ) return 'https';
        return 'http';
    }
   
    
    public static function current($protocol = NULL){
        
        if($protocol === TRUE){
            $protocol = self::protocol();
        }
        
        return URL::site($_SERVER['REQUEST_URI'], $protocol, FALSE);
    }
    
    public static function base_extend($extension, $host = TRUE)
    {
        return self::base($host).$extension;
    }

    
    public static function base($protocol = NULL, $index = FALSE)
    {
            // Start with the configured base URL
            $base_url = Kohana::$base_url;

            if ($protocol === TRUE and is_object(Request::current()))
            {
                    // Use the initial request to get the protocol
                    $protocol = Request::current()->secure() ? 'https' : 'http';
            }

            if ($protocol instanceof Request)
            {
                    // Use the current protocol
                    list($protocol) = explode('/', strtolower($protocol->protocol()));
            }

            if ( ! $protocol)
            {
                    // Use the configured default protocol
                    $protocol = parse_url($base_url, PHP_URL_SCHEME);
            }

            if ($index === TRUE AND ! empty(Kohana::$index_file))
            {
                    // Add the index file to the URL
                    $base_url .= Kohana::$index_file.'/';
            }

            if (is_string($protocol))
            {
                    if ($port = parse_url($base_url, PHP_URL_PORT))
                    {
                            // Found a port, make it usable for the URL
                            $port = ':'.$port;
                    }

                    if ($domain = parse_url($base_url, PHP_URL_HOST))
                    {
                            // Remove everything but the path from the URL
                            $base_url = parse_url($base_url, PHP_URL_PATH);
                    }
                    else
                    {
                            // Attempt to use HTTP_HOST and fallback to SERVER_NAME
                            $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
                    }

                    // Add the protocol and domain to the base URL
                    $base_url = $protocol.'://'.$domain.$port.$base_url;
            }

            return $base_url;
    }
}