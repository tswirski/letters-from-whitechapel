<?php

/**
 * ENVIRONMENT SETUP
 */
        const ENVIRONMENT_DEVELOPMENT = 'DEVELOPMENT';
        const ENVIRONMENT_PRODUCTION = 'PRODUCTION';

define('ENVIRONMENT', ENVIRONMENT_DEVELOPMENT);

/**
 * PHP EXTENSION
 * (acceptable php extension)
 */
define('EXT', '.php');

/**
 * DIRECTORY ROOT
 */
define('DOCROOT', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

/**
 * SUB DIRECTORIES
 */
$directories = [
    'APPPATH' => 'application',
    'MODPATH' => 'modules',
    'COMMONPATH' => 'common',
    'SYSPATH' => 'system'
];

foreach ($directories as $const => &$directory) {
    if (!is_dir($directory) && is_dir(DOCROOT . $directory)) {
        $directory = DOCROOT . $directory;
    }

    define($const, realpath($directory) . DIRECTORY_SEPARATOR);
}
unset($directories);


/**
 * DEBUG MEASUREMENT START POINT
 */
if (!defined('KOHANA_START_TIME')) {
    define('KOHANA_START_TIME', microtime(TRUE));
}

if (!defined('KOHANA_START_MEMORY')) {
    define('KOHANA_START_MEMORY', memory_get_usage());
}

/* KOHANA BOOTSTRAP - fo */
require APPPATH . 'bootstrap' . EXT;


/* OBIEKT ŻĄDANIA */
$Request = Request::factory();

/**
 * DEBUG MEASUREMENT STOP POINT
 */
if (!defined('KOHANA_START_TIME')) {
    define('KOHANA_START_TIME', microtime(TRUE));
}

if (!defined('KOHANA_START_MEMORY')) {
    define('KOHANA_START_MEMORY', memory_get_usage());
}

try {
    /* WERYFIKUJEMY CZY AKTUALNE ŻĄDANIE WPISUJE SIĘ W RUTING */
    if ($Request->route_name() === NULL) {
        throw new HTTP_Exception_404();
    }
    /* WYKONUJEMY ŻĄDANIE */
    echo $Request
            ->execute()
            ->send_headers()
            ->body();
}

//	/** OBSŁUGA WYJĄTKÓW */
catch (Exception $e) {
    var_dump($e);
}
?>