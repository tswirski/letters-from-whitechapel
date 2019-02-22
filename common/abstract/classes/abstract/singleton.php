<?php defined('SYSPATH') or die('No direct script access.');

abstract class Abstract_Singleton
{
    /* Nie pozwalamy na tworzenie obiektów tej klasy 
     */
    protected function __construct() {}
    
    /* Nie pozwalamy na klonowanie tego obiektów tej klasy oraz 
     * blokujemy możliwość nadpisania metody w klasach pochodnych 
     */
    final private function __clone(){}

    /* Funkcja wywoływana raz, zaraz po inicjalizacji, gdy istnieje już obiekt */
    protected function after_init(){}
    
    /* tworzenie instancji / zwracanie uchwytu */
    static public function instance()
    {
        /* instancje dziedziczące po singletonie */
        static $instance = array();
    
        /* get_called_class zwraca nazwę klasy która odziedziczy tą metoę po abstractcie */
        $called_class = get_called_class();
        if( ! isset($instance[$called_class]))
        {
            $instance[$called_class] = new $called_class();
            $instance[$called_class]->after_init();
        }
        return $instance[$called_class];
    }
}