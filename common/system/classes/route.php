<?php

defined('SYSPATH') or die('No direct script access.');

class Route extends Kohana_Route {

    /**
     * Tworzy link na podstawie obiektu kontrollera lub (opcjonalnie) jego nazwy
     * @param mixed (object/string) $object  OBIEKT lub NAZWA KLASY!
     * @param string action
     * @param array attributes (attrybuty)
     * @param mixed protokół ('http' / 'https');
     * @return string link
     */
    public static function link($controller, $action = 'index', $attributes = NULL, $protocol = TRUE) {
        // KONTROLER przekazany jako obiekt lub nazwa klasy
        $controller = is_object($controller) ? get_class($controller) : $controller;

        // Nazwa_Kontrolera -> nazwa_kontrolera
        $controller = strtolower($controller);

        $controller = explode('_', $controller);

        if ($controller[0] === 'controller') {
            /* Uwuwamy przedrostek 'controller' */
            array_shift($controller);
        }

        // KONTROLER
        $controller = implode('-', $controller);

        // ID
        $id = '';

        if (is_array($attributes)) {
            // atrybuty dodane w polu 'id'
            foreach ($attributes as $attr_key => $attr_value) {
                $id .= "$attr_key:$attr_value,";
            }
            // usuwamy ';' z ostatniego parametru
            $id = substr_replace($id, "", -1);
        } else {
            $id = (string) $attributes;
        }

        $credentials = array(
            'controller' => $controller,
            'action' => $action,
            'id' => $id
        );

        return Route::url('ca', $credentials, $protocol);
    }

//    public static function base_url($class)
//    {
//        $url = self::link($class);
//        $result = preg_match('/^(.*:\w+\/)/', $url, $matches);
//        if($result) return $matches[1];
//        else throw new Http_Exception_400('Nie udało się pozyskać podstawy adresu URL dla podanego adresu URL');
//    }
//
//     /**
//     * Tworzy tablicę linków dla różnych akcji jednego kontrolera
//     * @param mixed (object/string) $object  OBIEKT lub NAZWA KLASY!
//     * @param mixed array Tablica Nazw Akcji
//     * @param mixed null/array $params Tablica zawierająca zestaw wspólnych parametrów (np. Id, Page..ect)
//     * @return string link ;)
//     */
//    public static function links($object, array $actions, array $params = NULL, $protocol = TRUE)
//    {
//        /* Pobieramy podstawę */
//        $url_base_arr = self::url_base_arr($object);
//
//        /* Nazwa route'a który zostanie użyty do wygenerowania linku. Route powinno mieć nazwę d(s)a. */
//        $route_name = self::guess_route_name($object, $url_base_arr);
//
//        /* Zwracaną wartością jest tablica */
//        $output = array();
//
//        /* Dla każdej akcji tworzymy link */
//        foreach($actions as $action)
//        {
//            $output[$action] = Route::url(
//                    $route_name,
//                    array_merge(
//                        $url_base_arr,
//                        (array) $params,
//                        array('action'=>$action)
//                    ),
//                    $protocol);
//        }
//        return $output;
//    }
}
