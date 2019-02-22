<?php

defined('SYSPATH') or die('No direct script access.');

function str_to_null(&$str) {
    //if(empty($str) && $str !== 0 && $str !== FALSE)
    if ($str === '')
        $str = NULL;
}

class Arr extends Kohana_Arr {

    /**
     * Usuwa klucz z tablicy Array jeśli podany klucz istnieje w tablicy.
     * @param type $array
     * @param type $key
     */
    public static function remove(&$array, $key) {
        if (array_key_exists($key, $array)) {
            unset($array[$key]);
            return true;
        } return false;
    }

    /**
     * Usuwa klucz tablicy odpowiadający pierwszemu znalezionemu elementowi którego wartość
     * zgodna jest z podaną.
     * @param {array} tablica w której szukamy
     * @param {mixed}
     */
    public static function removeValue(&$array, $value) {
        if (($key = array_search($value, $array)) !== false) {
            unset($array[$key]);
            return true;
        } return false;
    }

    public static function prepend_null(&$array) {
        self::prepend($array);
    }

    /* Dokleja dodatkowy element jako pierwszy element tablicy. Pomocne przy SELECT'ach htmlowych */

    public static function prepend(array &$array, $default = NULL) {
        $array = array($default) + $array;
    }

    public static function equal(array $a, array $b) {
        return array_diff($a, $b) === array_diff($b, $a);
    }

    public static function equal_assoc(array $a, array $b) {
        return array_diff_assoc($a, $b) === array_diff_assoc($b, $a);
    }

    public static function equal_keys(array $a, array $b) {
        return array_diff_key($a, $b) === array_diff_key($b, $a);
    }

    public static function object_to_array($obj) {
        if (is_object($obj))
            $obj = (array) $obj;
        if (is_array($obj)) {
            $new = array();
            foreach ($obj as $key => $val) {
                $new[$key] = Arr::object_to_array($val);
            }
        } else
            $new = $obj;
        return $new;
    }

    /**
     * Przetwarza tablicę tekstów tak, że pierwsza litera tekstu jest WIELKĄ literą, a pozostałe są małymi literami.
     * Funkcja może pracować w trybie referencji (na podanej w referencji tablicy) lub w trybie kopii.
     * @param array $array
     * @return array
     */
    public static function ucfirst_lcrest(array &$array, $replace = TRUE) {
        $lower = array_map('strtolower', $array);
        $upper = array_map('ucfirst', $array);

        if ($replace)
            $array = $upper;

        return $upper;
    }

    /**
     * Podmienia stringi o zerowej długości na NULLe w podanej tablicy.
     */
    public static function nullarize(array &$array) {
        array_walk($array, "str_to_null");
    }

    /**
     * Funkcja używa tablicy asocjacyjnej aby ustawić dostępne PUBLICZNE wartości obiektu
     * @param object $object
     * @param array $array
     */
    public static function object_setup($object, $array) {
        $arr = array_intersect_key($array, get_class_vars(get_class($object)));
        foreach ($arr as $key => $value) {
            $object->$key = $value;
        }
    }

    /**
     * Szuka pierwszego obiektu który posiada atrybut o podanej nazwie i wartości
     * @param string $name
     * @param mixed $value
     * @param bool $strict Wyszukiwanie Z lub BEZ weryfikacji typu danych.
     * @return object or null
     */
    public static function find_object(array $array, $property_name, $property_value, $strict = FALSE) {
        foreach ($array as &$element) {
            //  if(is_object($element))
            try {
                if (($element->$property_name === $property_value) AND $strict)
                    return $element;
                elseif (($element->$property_name == $property_value) AND ! $strict)
                    return $element;
            } catch (Exception $ex) {
                continue;
            }
        }
        return NULL;
    }

    /**
     * Szuka wszystkich obiektów które posiadają atrybut o podanej nazwie i wartości
     * @param string $name
     * @param mixed $value
     * @param bool $strict Wyszukiwanie Z lub BEZ weryfikacji typu danych.
     * @return array;
     */
    public static function find_objects(array $array, $property_name, $property_value, $strict = FALSE) {
        $return = array();
        foreach ($array as &$element) {

            try {
                if (($element->$property_name === $property_value) AND $strict)
                    $return[] = $element;
                elseif (($element->$property_name == $property_value) AND ! $strict)
                    $return[] = $element;
            } catch (Exception $ex) {
                continue;
            }
        }
        return $return;
    }

    /**
     * Tworzy tabelę indeksowaną przy pomocy klucza (tabeli) lub atrybutu (obiektu)
     * @param array $array
     * @param string $key
     * @return array (pusta na niepowodzeniu)
     */
    public static function reindex(array $array, $key, $unset_column = FALSE) {
        $return = array();
        foreach ($array as &$row) {
            if (is_object($row)) {
                $return[$row->$key] = $row;
                if ($unset_column) {
                    unset($return[$row->$key]->$key);
                }
            } elseif (is_array($row)) {
                $return[$row[$key]] = $row;
                if ($unset_column) {
                    unset($return[$row[$key]][$key]);
                }
            }
        }
        return $return;
    }

    /**
     * Zamienia tablicę tablic lub tablicę obiektów na tablicę klucz->wartość przy użyciu wartości dwóch podanych kolumn/atrybutów
     * @param array $array
     * @param string $key_column
     * @param string $value_column
     * @return array  (pusta na niepowodzeniu)
     */
    public static function key_value(array $array, $key_column, $value_column) {
        $return = array();
        foreach ($array as &$row) {
            if (is_object($row)) {
                $return[$row->$key_column] = $row->$value_column;
            } elseif (is_array($row)) {
                $return[$row[$key_column]] = $row[$value_column];
            }
        }
        return $return;
    }

    /**
     * Case InSensitive in_array
     * @param string $needle
     * @param array $haystack
     * @return boolean
     */
    public static function in_arrayi($needle, $haystack) {
        return in_array(strtolower($needle), array_map('strtolower', $haystack));
    }

    /**
     * Tworzy listę obiektów instancji ($instanceof) które znajdują się w wielowymiarowej tablicy ($array)
     * @param string $instanceof
     * @param array $array
     * @return array
     */
    public static function instances($instanceof, $array) {
        /* Lista instancji */
        $instances = array();

        foreach ($array as $element) {
            if ($element instanceof $instanceof) {
                $instances[] = $element;
            } elseif (is_array($element)) {
                $instances = array_merge($instances, self::instances($instanceof, $element));
            }
        }
        return $instances;
    }

    /**
     * Zwraca TRUE jeśli tablica posiada wszystkie z podanych kluczy.
     * @param array $keys Tablica kluczy
     * @param array $array Tablica.
     * @return bool
     */
    public static function has_keys(array $keys, array $array) {
        return !array_diff($keys, array_keys($array));
    }

    /**
     * Tworzy string na podstawie tablicy assocjacyjnej.
     * @param array $assoc
     * @param string $inglue
     * @param string $outglue
     * @param bool $root Zmienna pomocnicza. Marker punktu wejścia.
     * @return string
     */
    public static function implode_assoc(array $assoc, $inglue = '=', $outglue = ',', $root = TRUE) {
        $return = '';

        foreach ($assoc as $key => $value) {
            if (is_array($value)) {
                $return .= self::implode_assoc($value, $inglue, $outglue, FALSE);
            } else {
                $return .= $outglue . $key . $inglue . $value;
            }
        }
        return $root ? substr($return, strlen($outglue)) : $return;
    }

    /* Sprawdza czy w tablicy jest element który spełnia warunek LIKE */

    function in_array_like($like, $array) {
        foreach ($array as $value) {
            if (strstr($like, $value)) {
                return true;
            }
        }
        return false;
    }

    /* Sprawdza czy w tablicy jest element który zgadza się z danym regexpem */

    public static function in_array_regex($regex, $array) {
        if (!is_array($array) or strlen($regex) == 0) {
            throw new Exception('Zły zestaw parametrów');
        }

        foreach ($array as $value) {
            if (preg_match($regex, $value)) {
                return true;
            }
        }
        return false;
    }

    /* Sprawdza czy wartość zgadza się z jednym z podanych (w postaci tablicy) regexpów */

    public static function in_array_match($value, $regexps) {
        foreach ($regexps as $regex) {
            if (preg_match($regex, $value)) {
                return true;
            }
        }
        return false;
    }

    /* Wypełnia tablicę wartościami od Start do Max (Rosnąco lub Malejąco) */

    public static function fill($from, $to, $step = 1) {
        $return = array();

        if ($step < 1)
            return $return;

        if ($from <= $to) {
            for ($i = $from; $i <= $to; $i = $i + $step) {
                $return[$i] = $i;
            }
        } else {
            for ($i = $from; $i >= $to; $i = $i - $step) {
                $return[$i] = $i;
            }
        }

        return $return;
    }

    public static function remap(array & $input, array $remap) {
        foreach ($remap as $key => $value) {
            if (array_key_exists($key, $input)) {
                $input[$value] = $input[$key];
                unset($input[$key]);
            }
        }
        return $input;
    }

}
