<?php defined('SYSPATH') or die('No direct access allowed.');

return array(
	'cookie' => array(
		'encrypted' => TRUE,
	),
        'lifetime' => 28800,    /* 8 Godzin */
        'session_type' => Session::$default,  /* Typ sesji. Domyślnie Native */
        'salt' => 'Fs$2sDg21gVZ95VkzalwoigBIeqp@@#*Vnfsoliww92',
);
