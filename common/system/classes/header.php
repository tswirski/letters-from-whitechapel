<?php

class Header
{
    /**  As of PHP 5.3.0  */
    public static function __callStatic($name, $arguments)
    {
        $error_id = substr($name, 1);
        if( ! array_key_exists($error_id, Response::$messages))
        {
            die('Nieprawidłowy Kod Błędu');
        }
        
        $error_code = Response::$messages[$error_id];
        
        header("HTTP/1.1 $error_id $error_code");
        exit;
    }
    
    public static function _303($redirect_to)
    {
        header('HTTP/1.1 303 See Other');
        header ("Location: $redirect_to", true, 303); 
        /* Kończymy skrypt */ 
        exit;
    }   
}

