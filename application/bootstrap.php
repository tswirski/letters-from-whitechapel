<?php

defined('SYSPATH') or die('No direct script access.');

// Load the core Kohana class
require SYSPATH . 'classes/kohana/core' . EXT;

if (is_file(APPPATH . 'classes/kohana' . EXT)) {
    // Application extends the core
    require APPPATH . 'classes/kohana' . EXT;
} else {
    // Load empty core extension
    require SYSPATH . 'classes/kohana' . EXT;
}

/**
 * Set the default time zone.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/timezones
 */
date_default_timezone_set('Europe/Warsaw');

/**
 * Set the default locale.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/setlocale
 */
setlocale(LC_ALL, 'pl_PL.utf-8');

/**
 * Enable the Kohana auto-loader.
 *
 * @see  http://kohanaframework.org/guide/using.autoloading
 * @see  http://php.net/spl_autoload_register
 */
spl_autoload_register(array('Kohana', 'auto_load'));

/**
 * Enable the Kohana auto-loader for unserialization.
 *
 * @see  http://php.net/spl_autoload_call
 * @see  http://php.net/manual/var.configuration.php#unserialize-callback-func
 */
ini_set('unserialize_callback_func', 'spl_autoload_call');

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules(array(
    'database' => MODPATH . 'database', // Database access
    'dao' => MODPATH . 'dao', // Data Access Objects
    'swiftmailer' => MODPATH . 'swiftmailer', // SwiftMailer.
    'abstract' => COMMONPATH . 'abstract', // Klasy Abstrakcyjne
    'jsonrpc' => MODPATH . 'jsonrpc',
    'system' => COMMONPATH . 'system', // Dodatkowe funkcje i helpery dla PHP/Kohana.
    'token' => MODPATH . 'token', // Graficzna autoryzacja
    'image' => MODPATH . 'image', // Image manipulation
));

/**
 * Initialize Kohana, setting the default options.
 * The following options are available:
 *
 * - string   base_url    path, and optionally domain, of your application   NULL
 * - string   index_file  name of your index file, usually "index.php"       index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - string   cache_dir   set the internal cache directory                   APPPATH/cache
 * - boolean  errors      enable or disable error handling                   TRUE
 * - boolean  profile     enable or disable internal profiling               TRUE
 * - boolean  caching     enable or disable internal caching                 FALSE
 */

define('BASEDIR', '/');

Kohana::init(array(
    'base_url' => BASEDIR,
    'errors' => true,
));

/**
 * Attach the file write to logging. Multiple writers are supported.
 */
Kohana::$log->attach(new Log_File(APPPATH . 'logs'));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Kohana::$config->attach(new Config_File);

/**
 * Konfiguracja i inicjalizacja plików cookie
 */
//Cookie::init();

/**
 * Ustawia język strony.
 */
I18n::lang('en');

/**
 * Set the routes. Each route must have a minimum of a name, a URI and a set of
 * defaults for the URI.
 */
Route::set('ca', '(<controller>(/<action>(/<id>)))', array(
            'controller' => '[\w\-_]+',
            'action' => '[\w\-_]+',
            'id' => '[\w\d:.,-_;]+',
        ))
        ->defaults(array(
            'controller' => 'production',
            'action' => 'index'
        ));


//Route::set('ca', function($url) {
//    var_dump($url);
//});
