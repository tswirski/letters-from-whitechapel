<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Ten kontroler to taka cegiełka z której tworzy się strony. Pojedynczy element widoku który jest elementem kolejnego widoku
 * musi mieć swój kontroler i to jest właśnie taki kontroler ;) Jako, że jest to kontroler-cegiełka to nie posiada on informacji
 * o requescie i responsie, nie powinien mieć akcji i nie powinien być wywoływany bezpośrednio. Służy tylko jako podstawka do tworzenia
 * powtarzających się elementów składowych innych widoków.  Generalny koncept jest taki. Abstract_Controller_Template jest najmniejszą
 * cząstką składową. Może on należeć do innycy elementów tego samego typu lub do kontrolera nadrzędnego jakim jest Abstract_Controller_Singleton_Template.
 * Docelowo najwyżej jest Abstract_Controller_Ajax_Template oraz Layout_Template.
 * 
 * Controler-Cegiełka sam decyduje o tym co ma wyświetlić. Pracuje tylko na jednym widoku.
 */
abstract class Abstract_Controller_Template
{
    protected $templalte = null;
    
    /**
     * Funkcja powinna zwracać ścieżkę do widoku.
     * @return string
     */
    abstract protected function get_template_path();
    
    
    
    /* Dane Widoku */
    protected $template_data;

    /**
     * Ustawia dane widoku.
     * @param mixed $data 
     */
    protected function set_template_data($data)
    {
        $this->template_data = $data;
    }
    /**
     * Zwraca aktualne dane widoku.
     * @return mixed 
     */
    protected function get_template_data($use_cache = TRUE)
    { 
        if($use_cache)
        {
            if( ! $this->template_data)
            {
                    $this->set_template_data(
                            $this->prepare_template_data()
                    );
            }
            return $this->template_data;
        }
        else
        {
            $this->set_template_data(NULL);
            return $this->prepare_template_data();
        }
    }
    
    
    /**
     * Funkcja powinna zwracać tablicę asocjacyjną którą używa się jako dane
     * widoku. 
     * @return array (associative) 
     */
    protected abstract function prepare_template_data();
    
    
    /* @var object Obiekt klasy widoku */
    protected $template = null;
    
    /**
     * Ustawienie obiektu widoku. Funkcja używana przez get_template() do ustawiania widoku gdy
     * ten nie istnieje. Funkcja do użytku wewnętrznego.
     * @param View $template.
     */
    protected function set_template(View $template = NULL)
    {
        $this->template = $template;
    }
    
    /**
     * Zwraca obiekt widoku.
     * Jeśli obiekt nie istnieje to tworzy go na podstawie ścieżki zwracanej przez 
     * get_template_path() oraz danych zwracanych przez prepare_template_data();
     * 
     * @param bool $use_cache TRUE = model będzie pamiętany pomiędzy wywołaniami w ramach jednego żądania
     * @return object View
     * @throws Http_Exception_405
     */
    public function get_template($use_cache = TRUE)
    {
        /* Jeśli widok został już w przeszłości stworzony to go zwracamy */
        if(is_object($this->template) and $use_cache)
        {
            return $this->template;
        }
        /* W przeciwnym wypadku tworzymy widok i uzupełniamy go danymi */
        else
        {
            /* Upewniamy się, że ścieżka została podana */
            if( ! $this->get_template_path())
            {
                throw new Exception('Nieprawidłowa ścieżka widoku. Rysowanie zakończone niepowodzeniem.');
            }
            /* Tworzymy widok */
            $template = View::factory($this->get_template_path());
            
            /* Wstawiamy dane do template'a */
            $template->set($this->get_template_data($use_cache));
            
            if($use_cache)
            {
                /* Zapamiętujemy go na przyszłość */
                $this->set_template($template);
            }
            else 
            {
                $this->set_template();
            }
            
            /* Zwracamy nowo powstały widok */
            return $template;
        }
    }
    
    /** 
     * Funkcja zwraca wyrenderowany widok w postaci tekstu (html).
     * @param bool $use_cache TRUE = model będzie pamiętany pomiędzy wywołaniami w ramach jednego żądania
     * @return string
     */
    public function render($use_cache = TRUE)
    {
        return 
            $this->get_template($use_cache) 
            ->render();
    }
}