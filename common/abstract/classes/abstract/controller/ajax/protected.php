<?php defined('SYSPATH') or die('No direct script access.');

class  Abstract_Controller_Ajax_Protected extends Controller
{
    public function __construct(Request $request, Response $response) {
        /* Kontrolery Ajaxowe nie są cacheowane przez przeglądarki */
        header("Cache-Control: no-store");
        parent::__construct($request, $response);
    }
    
    /* Zwraca zawartość tablicy POST w postaci niesparsowanej. */
    protected function get_input()
    {
        return file_get_contents('php://input');
    }
    
    protected function redirect($url)
    {
        Ajax::redirect($url);
    }
    
    /**
     * Funkcja powinna sprawdzać czy do danego kontrolera użytkownik ma dostęp przy 
     * zastosowaniu obecnego układu parametrów. (Dostęp użytkownika na podstawie typu konta
     * jest weryfikowany gdzie indziej).
     * @return boolean 
     */
    protected function access_check()
    {
        return TRUE;
    }

    /** 
     * Nadpisanie abstrakcji. Najpierw weryfikujemy czy kontroler może być obsłużony, a potem dopiero go obsługujemy. 
     */
    public function before() 
    {
        if( ! $this->access_check())
        {
            throw new HTTP_Exception_403(__('403'));
        }
        parent::before();
    }
    
    
    public function after() {
        parent::after();
        exit; // Zabijamy proces gdyż nie chcemy żadnych dodatkowych outputów ;)
    }
}