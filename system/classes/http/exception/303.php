<?php defined('SYSPATH') or die('No direct script access.');

class HTTP_Exception_303 extends HTTP_Exception {

	/**
	 * @var   integer  HTTP 303  See Other
	 */
	protected $_code = 303;

        public function uri(){
            return $this->message;
        }
}