<?php defined('SYSPATH') or die('No direct script access.');

/** 
* !! UWAGA UWAGA UWAGA !!
* Ponieważ stencile są wiązane z sesją na etapie tworzenia instancji stencila to w momencie wyzerowania
* sesji (przez przypisanie null'a, pustej tablicy lub komendą session_unset()) sesja traci integralność,
* a elementy stencila przestają wskazywać na klucze w tablicy sesji. Do wyzerowania konkretnego stencila 
* należy używać funkcję Stencil->zero_memory(). Do wyzerowania całej sesji należy użyć funkcji statycznej
* Stencil::zero_all()
* 
*/

/* Abstrakcja dla szablonu elementu sesji */
abstract class Abstract_Session_Stencil extends Abstract_Singleton
{   
    /* Lista uchwytów do instancji aktualnie aktywnych stencili */
    protected static $_stencils = array();
    /**
     * Wywołanie funkcji powoduje przywrócenie wartości domyślnych (zresetowanie) wszystkich
     * aktualnie zapamiętanych w sesji Stencil'i. 
     */
    public function reset_all()
    {
        /* Dla każdego zapamiętanego Stencil'a przeprowadzamy zerowanie (przywracanie wart. domyslnych) */
        foreach (array_keys(self::$_stencils) as $stencil)
        {
            $stencil::instance()->reset();
        }
   }

   /**
    * Wywołanie funkcji cleanup() na każdym z stencili (o ile funkcja istnieje) po czym wywołanie reset().
    * Ta metoda jest wywoływana zawsze przy wylogowywaniu. W metodach cleanup() powinny znajdować się dyrektywy
    * zwalniające inne zajęte zasoby. (Np. gdy w sesji trzymane są informacje o plikach tymczasowo zapisanych
    * na dysku to przed usunięciem informacji o plikach z sesji należy usunąć pliki z dysku).
    */
    public static function cleanup_all()
    {
        /* Dla każdego zapamiętanego Stencil'a przeprowadzamy zerowanie (przywracanie wart. domyslnych) */
        foreach (array_keys(self::$_stencils) as $stencil)
        {
            if(method_exists($stencil::instance(), 'cleanup'))
            {
                $stencil::instance()->cleanup();
            }
            $stencil::instance()->reset();
        }
    }
     
    /* Uchwyt sesji z której korzysta dany Stencil */
    private $_session;
    
    /* Zwraca uchwyt do obiektu sesji danego Stencil'a */
    final public function & handler() 
    { 
        return $this->_session;     
    }
    
    /* @return string Typ sesji danego Stencil'a */
    public function get_type()
    {
        return 'native';
    }
    
    /* @return string Klucz z którego korzysta dany Stencil */
    public function get_key()
    {
        return str_replace('Session_Stencil_', NULL, get_class($this));
    }
    
    /**
	* Zwraca nazwy zmiennych (klasy dziedziczącej) Stencila
	* @param bool @attach_defaults jeżeli TRUE to dodany zostanie nadmiarowo parametr przechowujący
	* tablicę z wartościami domyślnymi danego Stencil'a.
	*/
    public function get_properties($attach_defaults = TRUE) {
        $return = array_diff_key(get_object_vars($this), get_class_vars(get_class()));
		if($attach_defaults)
			$return['_defaults'] = $this->_defaults;
		return $return;
    }
    
    /* Zwraca informację czy STENCIL (potomny) posiada dany ATRYBUT */
    public function property_exists($property) {
        return array_key_exists($property, $this->get_properties(FALSE));
    }
    
    /* Zwraca nazwy zmiennych (klasy dziedziczącej) Stencila */
    final protected function get_var_names()
    {
        return array_keys($this->get_properties());
    }
    
    /* Ustawia wartości wszystkich zmiennych Stencila na domyślne */
    final public function reset()
    {
        foreach ($this->get_var_names() as $var_name)
        {
            /* Podczas zerowania omijamy klucz zmiennej zawierającej wartości domyślne Stencil'a */
            if($var_name === '_defaults') 
            {
                continue;   
            }
            
            $this->$var_name = isset($this->_defaults[$var_name]) ? $this->_defaults[$var_name] : NULL;
        }
    }
    
    /**
     * Ustawia wartości Stencil'a zgodnie z podaną tablicą asocjacyjną.
     * @param array $array Tablica asocjacyjna. 
     *  (Tablica może nie zawierać wartości dla wszystkich parametrów Stencil'a, ale nie może
     *   zawierać kluczy nadmiarowych które nie mają swoich odpowiedników w Stencil'u.  Ponadto
     *  w tablicy nie może istnieć pole o kluczu '_defaults' gdyż ten klucz jest zastrzeżony).
     * @throws Kohana_Exception Jeśli tablica zawiera klucz który nie ma odpowiednika w Stencilu.
     */
    final public function merge(array $array)
    {
        if(array_key_exists('_defaults', $array))
        {
            throw new Kohana_Exception("SESSION STENCIL : Nie można zmienić wartości domyślnych");
        }
        elseif(array_diff_key($array, $this->get_properties()))
        {
            throw new Kohana_Exception("SESSION STENCIL : Błąd importu tablicy do szablonu");
        }
        else
        {
            foreach ($array as $key => $value)
            {
                    $this->$key = $value;
            }
        }
    }
    
    
    
    /* Funkcja wywoływana przez abstrakcję jednokrotnie, po utworzeniu instancji */   
    protected function after_init() {
        parent::after_init();
  
        /* Inicjalizacja obiektu Session */
        $this->_session = Session::instance($this->get_type());
        /* Łączenie obiektu Session ze Stencil'em */
        $this->connect($this->_session);
        
        $session_native = Session::instance('native');
        
        if(empty(self::$_stencils) AND $session_native->has_key('STENCILS'))
        {
            self::$_stencils = & $session_native->share('STENCILS');
        }
        elseif(empty(self::$_stencils))
        {
            $session_native->bind('STENCILS', self::$_stencils);
        }
        
        /* Dokładamy stworzoną instancję stencil'a na stosik stencili ;) */
        self::$_stencils[get_class($this)] = TRUE;
    }
    
    /**
     * Ustaw wartość domyślną podanego atrybutu lub atrybutów
     * @param mixed (string/array) $attr
     * @return boolean
     */
    public function restore_defaults($attr)
    {
        if(is_array($attr))
        {
            foreach($attr as $a)
            {
                $this->reset($a);
            }
        }
        
        if(array_key_exists($attr, $this->_defaults)){
            $this->attr = $this->_defaults[$attr];
        }
        return TRUE;
    }
    
    
    /* @var array Zestawienie wartości domyslnych danego STENCIL'a */
    protected $_defaults = array();
       
    /* Funkcja łączy elementy Stencil'a z danym kluczem w sesji */
    final protected function connect(Session $session)
    {
        /* Lista nazw atrybutów obiektu */
        $keys = $this->get_var_names();
        
        /* Z powyższej listy usuwamy klucz _defaults aby uniknąć zapętlenia ;) */
        unset($keys[array_search('_defaults', $keys)]); 
  
        /* Flaga informująca czy to pierwsza inicjalizacja danego Stencil'a */
        $is_first_run = ! $session->has_key($this->get_key());
        
        /* Pobieramy referencję dla elementu sesji który będzie używany przez dany Stencil */
        $session_piece = & $session->share($this->get_key());
        
        
        /* Jeśli jest to pierwsza inicjalizacja danego Stencil'a */
        if($is_first_run)
        {
            foreach($keys as $key)
            {
                /* Zapamiętujemy wartość domyślną o ile nie jest NULLem */
                if($this->$key !== NULL)
                {
                    $this->_defaults[$key] = $this->$key;
                }
                /* Sprzęgamy zmienną w obiekcie z polem w sesji */
                $this->$key = &$session_piece[$key];
                /* Zapisujemy wartość domyślną do sesji */
                $this->$key = isset($this->_defaults[$key]) ? $this->_defaults[$key] : NULL;
            }
            /* Zapisujemy do sesji zbiór wartości domyślnych */
            $session_piece['_defaults'] = $this->_defaults;
            /* Sprzęgamy zmienną 'defaults' z sesją */
            $this->_defaults = &$session_piece['_defaults'];
        }
        else
        {
            /* Linkowanie poszczególnych atrybutów obiektu z sesją */
            foreach($keys as $key)
            {   
                /* Linkujemy zmienne z sesją */
                $this->$key = &$session_piece[$key];
            }
            $this->_defaults = &$session_piece['_defaults'];
        }
    }
}