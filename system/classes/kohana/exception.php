<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Exception extends Kohana_Kohana_Exception {
    
    public function get_message()
    {
        return $this->message;
    }
    
}
