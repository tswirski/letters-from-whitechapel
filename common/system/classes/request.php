<?php defined('SYSPATH') or die('No direct script access.');

class Request extends Kohana_Request
{
    protected $parameters = array();
    
    
   /* Obsługa podkatalogów oraz parametrów umieszczonych jako ID w formacie klucz->wartość */  
    public function execute()
    { 
        $id = $this->param('id');
        
        /* Parametry przekazywane w parametrze 'id' */
        $param_arr = explode(',', $id);
        foreach($param_arr as $param_str) {
            $param = explode(':', $param_str);
            if(count($param) == 2){
                $this->parameters[$param[0]] = $param[1];
            }
        }
        
        /* Obsługa podkatalogów. Katalogi rozdzielone '-' w nazwie kontrollera. */
        $path = explode('-', $this->controller());
        $this->controller(array_pop($path));
        $this->directory(implode('_', $path));
        
        
        /* Obsługa akcji z nazwami rozdzielonymi '-' */
        $this->action(str_replace('-', '_', $this->action()));
        
        return parent::execute();
    }
    
    
    
    
    
    public function parameter($key, $default = NULL){
        return Arr::get($this->parameters, $key, $default);
    }
    
    public function parameters(){
        return $this->parameters;
    }
    
    /**
    * Zwraca nazwę aktualnie użytego routingu.
    * @return mixed (string/NULL) 
    */
    public function route_name()
    {
        return is_object($this->route()) ? Route::name($this->route()) : NULL;
    }


    /**
     * Zwraca nazwę katalogu pierwszego poziomu   ./KatalogPierwszegoPoziomu/Katalog_2/Kontroler/Akcja
     * chyba dość jasne ;) 
     */
     public function directory_root()
     {
         return strtok($this->path(),'/');
     }
     
     /**
      * Zwraca TRUE jeśli request jest typu POST.  False w przeciwnym wypadku.
      * @return bool
      */
     public function is_post()
     {
         return $_SERVER['REQUEST_METHOD'] === 'POST';
     }
     
}       