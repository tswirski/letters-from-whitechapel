<?php defined('SYSPATH') or die('No direct script access.');

/**
 *  Ten kontroler to taka cegiełka z której tworzy się strony. Pojedynczy element widoku który jest elementem kolejnego widoku
 * musi mieć swój kontroler i to jest właśnie taki singleton ;) Singletonowe kontrolery nie obsługują akcji, nie pobierają informacji odnośnie
 * $request i $response (Jeśli jest konieczność dostania się do requestu należy zrobić Request::current());
 * Kontroler 'cegiełka' sam powinien decydować co ma wyświetlić w momencie w którym został 'odpytany'.
 */

abstract class Abstract_Controller_Singleton_Template 
    extends Abstract_Singleton
{
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
    protected function get_template_data()
    {
        if( ! $this->template_data)
        {
            $this->set_template_data(
                    $this->prepare_template_data()
            );
        }
        return $this->template_data;
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
    protected function set_template(View $template)
    {
        $this->template = $template;
    }
    
    /**
     * Zwraca obiekt widoku.
     * Jeśli obiekt nie istnieje to tworzy go na podstawie ścieżki zwracanej przez 
     * get_template_path() oraz danych zwracanych przez prepare_template_data();
     * @return object View
     * @throws Http_Exception_405
     */
    public function get_template()
    {
        /* Jeśli widok został już w przeszłości stworzony to go zwracamy */
        if( is_object($this->template))
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
            $template->set($this->get_template_data());
            
            /* Zapamiętujemy go na przyszłość */
            $this->set_template($template);
            
           /* Zwracamy nowo powstały widok */
            return $template;
        }
    }
    
    /** 
     * Funkcja zwraca wyrenderowany widok w postaci tekstu (html).
     * @return string
     */
    public function render()
    {
        return 
            $this->get_template() 
            ->render();
    }
}