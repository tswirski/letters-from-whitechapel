<?php

defined('SYSPATH') or die('No direct script access.');

class Valid extends Kohana_Valid {

    /**
     * Negatywny Regexp. Spełniony gdy igły nie ma w stogu.
     * @param string $needle (RegExp)
     * @param string $haystack (Value)
     * @return bool
     */
    public static function neg_regex($needle, $haystack) {
        $needle = "/$needle/";
        return preg_match($needle, (string) $haystack) ? FALSE : TRUE;
    }

    /**
     * Format MySQL Date
     * @param string $date
     * @return bool
     */
    public static function mysql_date($date) {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    }

    /**
     * Sprawdza czy podany string jest prawidłowym UTF-8
     * @param string $string
     * @return bool
     */
    public static function utf8($string) {
        return (bool) preg_match('/^[\s\p{L}]+$/u', $string);
    }

    /**
     * Sprawdza czy podany string jest prawidłową nazwą pliku.
     * Zakładamy, że nazwa pliku może zawierać Litery, Cygry, znaki '.', '_', '-' oraz spację ' ';
     * @param string $string
     * @return bool
     */
    public static function file_name($string) {
        return (bool) preg_match('/^[\d\p{L}\_\-\. ]+$/u', $string);
    }

    /**
     * File-name no whitespaces.
     * Nazwa pliku bez znaków białych
     */
    public static function filename_nows($string) {
        return (bool) preg_match('/^[\d\p{L}\_\-\.]+$/u', $string);
    }

    /**
     * Zwraca TRUE jeśli podany ciąg nie jest pusty (zawiera coś więcej niż tylko spacje i taby ;)
     * @param string $string
     * @return bool
     */
    public static function not_blank($string) {
        return !preg_match('/^\s+$/', $string);
    }

    /**
     * Zwraca TRUE jeśli podany ciąg jest liczbą całkowitą
     * @param string $int
     * @return bool
     */
    public static function integer($int) {
        return !preg_match('/^\d+$/', $int);
    }

    public static function nickname($str){
        $str = (string) $str;
        return (bool) preg_match('/^[\pL\-_0-9]++$/uD', $str);
    }

    /**
     * Checks whether a string consists of alphabetical characters only.
     * + separators 'space', and '-'
     */
    public static function alpha_name($str) {
        $str = (string) $str;

        return (bool) preg_match('/^[\pL\- ]++$/uD', $str);
    }

    /**
     * Checks if a field is not ZERO.
     *
     * @return  boolean
     */
    public static function not_zero($value) {
        if (is_object($value) AND $value instanceof ArrayObject) {
            // Get the array from the ArrayObject
            $value = $value->getArrayCopy();
        }

        // Value cannot be NULL, FALSE, '', or an empty array
        return !in_array($value, array(0, '0'), TRUE);
    }

}
