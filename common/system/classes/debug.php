<?php

defined('SYSPATH') or die('No direct script access.');

class Debug extends Kohana_Debug {

    /**
     * Uniwersalny log błędu.
     * @param Exception $e
     * @return boolean
     */
    static public function log_exception(Exception $e) {

        $message = "\n" . '-----------------------------------------------------------------------' . "\n" .
                'Wyjątek numer: ' . $e->getCode() . "\n" .
                'Komunikat: ' . $e->getMessage() . "\n" .
                'Plik: ' . $e->getFile() . ', linia: ' . $e->getLine() . "\n" .
                'Historia wywołania: ' . $e->getTraceAsString() . "\n" .
                '-----------------------------------------------------------------------' . "\n";

        echo $message;

        Kohana::$log->add(Log::ERROR, $message);
        return TRUE;
    }

    static function log($message, $module = "UNKNOWN") {
        echo "[$module] $message";
    }

}
