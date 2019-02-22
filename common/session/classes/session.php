<?php defined('SYSPATH') or die('No direct script access.');

abstract class Session extends Kohana_Session {
    
    /**
     * Generuje unikalne ID które może być użyte jako ID sesji.
     * @return string
     */
    
    protected function unique_id()
    {
                
        $config = Kohana::$config->load('session');
        $base = $config['salt']+$_SERVER['REMOTE_ADDR']+time();
        
        $id = sha1($base).rand(1,1000000).crc32(microtime(TRUE)).time();
        /* Kolejne losowanie */
        while($this->session_exists($id)){
           $id = sha1($base).rand(1,1000000).crc32(microtime(TRUE)).time();
        }    
        
        return $id;
    }
    
    /**
     * Sprawdza czy sesja o podanym ID istnieje.
     * @param int $id
     * @return bool
     */
    abstract public function session_exists($id);

    
    
    public static function check_id($id)
    {
        return $id === session_id();
    }
    
    public static function stencil($stencil_name)
    {
        $stencil = 'Session_Stencil_'.ucfirst(strtolower($stencil_name));
        return $stencil::instance();
    }
}
