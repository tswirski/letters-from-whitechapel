<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * Abstrakcja umożliwiająca konfigurację cookie za pomocą konfiguracji zawartej w
 * config:cookie/default  oraz  preinicjalizację ciastek zdefiniowanych w config:cookie/cookies
 */
class Cookie extends Kohana_Cookie {

    /**
     * Dekodowanie daty wygaśnięcia ciastka podanej w pliku config do postaci
     * przekazywanej do funkcji setcookie();
     * @param mixed $expiration
     * @return int timestamp
     */
    private static function decode_expiration($expiration) {
        if (is_string($expiration)) {
            return strtotime($expiration);
        }

        return $expiration;
    }

    protected static $default_config;

    /**
     * Inicjalizacja konfiguracji modułu Cookie oraz pre-inicjalizacja ciastek
     * określonych w configu.
     */
    public static function init() {
        /* Inicjalizacja konfiguracji modułu Cookie */
        self::$default_config = Kohana::$config->load('cookie.default');

        foreach (self::$default_config as $cfg_key => $cfg_value) {
            /* Dekodowanie daty wygaśnięcia ciastka */
            if ($cfg_key === 'expiration') {
                $cfg_value = self::decode_expiration($cfg_value);
            }
            self::${$cfg_key} = $cfg_value;
        } unset($cfg_key, $cfg_value);

        /* Preinicjalizacja ciastek */
        $cookies = Kohana::$config->load('cookie.cookies');
        foreach ($cookies as $cookie_name => $cookie_config) {
            /* 1: Pomijamy ustawienie domyslnych wartości dla ciastek które już istnieją
             *    (zapobiegamy nadpisaniu ustawionej wartości wartością domyślną)
             * 2: Pomijamy ciastka bez klucza 'value' lub z wartością NULL dla klucza 'value'
             */

            if (
                    array_key_exists($cookie_name, $_COOKIE) || !array_key_exists('value', $cookie_config) || $cookie_config['value'] === NULL
            ) {
                continue;
            }

            $value = $cookie_config['value'];
            unset($cookie_config['value']);

            if (array_key_exists('expiration', $cookie_config)) {
                $cookie_config['expiration'] = self::decode_expiration($cookie_config['expiration']);
            }

            /* Ustawienia domyślne */
            $defaults = array();

            foreach ($cookie_config as $cfg_key => $cfg_value) {
                /* Zapisujemy starą, domyślną wartość */
                $defaults[$cfg_key] = self::${$cfg_key};
                /* Zastępujemy ją nową */
                self::${$cfg_key} = $cfg_value;
            } unset($cfg_key, $cfg_value);

            self::set($cookie_name, $value);

            /* Przywracamy ustawienia domyślne */
            foreach ($defaults as $cfg_key => $cfg_value) {
                self::${$cfg_key} = $cfg_value;
            } unset($cfg_key, $cfg_value);
        } unset($cookie_name, $cookie_config);
    }

    /**
     * Update cookie value, properties or both.
     * @param string $name
     * @param string or array $c, string for value update, array for property update.
     * @param array $d if updating both, here goes properties
     */
    public static function update($name, $c, $d = NULL) {
        /* Wartość ciastka */
        $value = !is_array($c) ? (string) $c : Cookie::get($name);

        /* Konfiguracja ciastka */
        $config_override = is_array($c) ? $c : (
                is_array($d) ? $d : array()
                );

        $cookie_config = Kohana::$config->load('cookie.cookies.' . $name);
        if ($cookie_config === NULL) {
            $cookie_config = array();
        }

        $config = array_merge(self::$default_config, $cookie_config, $config_override);

        if (array_key_exists('value', $config)) {
            unset($config['value']);
        }
        if (array_key_exists('expiration', $config)) {
            $config['expiration'] = self::decode_expiration($config['expiration']);
        }

        /* Ustawienia domyślne */
        $defaults = array();

        foreach ($config as $cfg_key => $cfg_value) {
            /* Zapisujemy starą, domyślną wartość */
            $defaults[$cfg_key] = self::${$cfg_key};
            /* Zastępujemy ją nową */
            self::${$cfg_key} = $cfg_value;
        } unset($cfg_key, $cfg_value);

        self::set($name, $value);

        /* Przywracamy ustawienia domyślne */
        foreach ($defaults as $cfg_key => $cfg_value) {
            self::${$cfg_key} = $cfg_value;
        } unset($cfg_key, $cfg_value);
    }

    /**
     *  SUPPORT DLA CIASTEK KTÓRE NIE SĄ ZABEZPIECZONE PRZED MODYFIKACJĄ
     *  POPRZEZ HASH'owy PREFIX.
     */

    /**
     * Ustawia zaszyfrowane ciastko z domyślnymi parametrami (expiration).
     */
    public static function set_secure($name, $value) {
        return self::set($name, Blowfish::encrypt($value));
    }

    /**
     * Zwraca zdekodowaną wartość ciastka lub Null gdy błąd (np. brak ciastka)
     */
    public static function get_secure($name) {
        $value = self::get($name);
        return $value ? Blowfish::decrypt($value) : NULL;
    }

    /* Bez zabezpieczenia danych przed modyfikacją */

    public static function set_raw($name, $value, $expiration = NULL) {
        if ($expiration === NULL) {
            // Use the default expiration
            $expiration = Cookie::$expiration;
        }
        if ($expiration !== 0) {
            // The expiration is expected to be a UNIX timestamp
            $expiration += time();
        }
        return setcookie($name, $value, $expiration, Cookie::$path, Cookie::$domain, Cookie::$secure, Cookie::$httponly);
    }

    /**
     * Update cookie value, properties or both.
     * @param string $name
     * @param string or array $c, string for value update, array for property update.
     * @param array $d if updating both, here goes properties
     */
    public static function update_raw($name, $c, $d = NULL) {
        /* Wartość ciastka */
        $value = !is_array($c) ? (string) $c : Arr::get($_COOKIE, $name);

        /* Konfiguracja ciastka */
        $config_override = is_array($c) ? $c : (
                is_array($d) ? $d : array()
                );

        $cookie_config = Kohana::$config->load('cookie.cookies.' . $name);
        if ($cookie_config === NULL) {
            $cookie_config = array();
        }

        $config = array_merge(self::$default_config, $cookie_config, $config_override);

        if (array_key_exists('value', $config)) {
            unset($config['value']);
        }
        if (array_key_exists('expiration', $config)) {
            $config['expiration'] = self::decode_expiration($config['expiration']);
        }

        /* Ustawienia domyślne */
        $defaults = array();

        foreach ($config as $cfg_key => $cfg_value) {
            /* Zapisujemy starą, domyślną wartość */
            $defaults[$cfg_key] = self::${$cfg_key};
            /* Zastępujemy ją nową */
            self::${$cfg_key} = $cfg_value;
        } unset($cfg_key, $cfg_value);

        self::set_raw($name, $value);

        /* Przywracamy ustawienia domyślne */
        foreach ($defaults as $cfg_key => $cfg_value) {
            self::${$cfg_key} = $cfg_value;
        } unset($cfg_key, $cfg_value);
    }

}
