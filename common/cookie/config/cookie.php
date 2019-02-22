<?php defined('SYSPATH') or die('No direct script access.');

/* Jeśli podano wartość 'value' oraz wartość value jest różna niż NULL 
 * to ciastko zostanie zainicjalizowane przed wywołaniem kontrolera. 
 * Jeśli wartość 'value' wynosi NULL lub brakuje klucza 'value' w tablicy 
 * konfiguracyjnej to ciastko zostanie storzone wraz z wywołaniem 
 * Cookie::update( , ) dla danego ciastka; Dla pozostałych atrybutów
 * jeśli klucz konfiguracji danego atrybutu dla ciastka istnieje to zostanie
 * użyta określona wartość (nawet NULL). Jeśli klucz konfiguracji nie istnieje
 * to zostanie użyta wartość domyślna (określona w 'default' lub wewnątrz klasy Cookie).
 */

return array(
    'cookies' => array(
        'execution_time' => array(
            'value' => null,
        ),
        'http_referer' => array(
            'value' => ENVIRONMENT_HOST
        ),
    ),
    
    // Cała reszta ciastek
    'default' => array(
        'expiration'    => Date::WEEK,  // 0 = sesja, int = sekund od teraz,
       // 'path'      => '/',
        'domain'    => ENVIRONMENT_HOST,
        'secure'    => false,
        'httponly'  => false,
        'salt'      => '!0f#9vWM3Mc93@Rc9sCM3$5caaz$'
    ),
);