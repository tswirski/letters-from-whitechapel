<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_HTTP_Exception_204 extends HTTP_Exception {

	/**
	 * @var   integer    HTTP 204 No Content
	 */
	protected $_code = 204;

}