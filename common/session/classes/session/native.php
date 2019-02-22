<?php defined('SYSPATH') or die('No direct script access.');

    class Session_Native extends Kohana_Session_Native 
    {
        public function & share($key)
        {
            return $this->_data[$key];
        }
        
        public function session_unset()
        {
            session_unset();
        }
        
        public function has_key($key)
        {
            return array_key_exists($key, $this->_data);
        }

        
        
        protected function _read($id = NULL)
	{
            if( ! $id)
            {
                if( Arr::get($_SERVER, 'HTTP_USER_AGENT') === 'Shockwave Flash') {
                    $id = Arr::get($_POST, 'session_id');
                }
            }
            
            $return = parent::_read($id);
        }
    }