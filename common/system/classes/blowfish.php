<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * Moduł Blowfish używa funkcji password_hash, password_verify
 * Moduł samoczynnie określa koszt algorytmu.
 */
class Blowfish {

    public static function getCost() {
        $timeTarget = 0.05;
        $cost = 8;
        do {
            $cost++;
            $start = microtime(true);
            password_hash("test", PASSWORD_BCRYPT, ["cost" => $cost]);
            $end = microtime(true);
        } while (($end - $start) < $timeTarget);
        return $cost;
    }

    public static function hash($password) {
        return password_hash($password, PASSWORD_BCRYPT, ["cost" => self::getCost()]);
    }

    public static function verify($password, $hash) {
        return password_verify($password, $hash);
    }

}
