<?php defined('SYSPATH') or die('No direct script access.');

/* Kontroler AJAX w którym PHP decyduje o tym co jest celem zwracanej odpowiedzi, a JS rozpatruje określony przez PHP cel i podejmuje określoną przez PHP akcję. */
class Ajax
{

    const ERROR = 405;
    
    protected static $error_code = 0;
    /* URL to adres pod który przekierowany zostanie użytkownik */
    protected static $url;
    protected static $objects = array();
    protected static $eval_before, $eval_after, $eval_beforeNext;

    public static function clear(){
        self::$error_code = 0;
        self::$url = null;
        self::$objects = array();
        self::$eval_before = null;
        self::$eval_beforeNext = null;
        self::$eval_after = null;
    }
    
    public static function redirect($url)
    {
        self::$url = $url;
        self::go();
    }

    public static function eval_before($code){
        self::$eval_before = $code;
    }

    public static function eval_after($code){
        self::$eval_after = $code;
    }

    public static function eval_beforeNext($code){
        self::$eval_beforeNext = $code;
    }

    public static function go()
    {
        if( ! empty(self::$msg_frame)) {
            $msg_frame = self::$msg_frame;
            self::$eval_after .= "$('#{$msg_frame}').scrollTo(100);";
            
            self::$eval_before .= "$('#{$msg_frame}').html('');";
        }
        
        $objects = array();
        foreach (self::$objects as $object)
        {
            $objects[] = $object->as_array();
        }

        if(self::$persist_messages === FALSE) {
            // tymczasowo wyłączony, działający ficzer :)
//            self::$eval_before .= ( self::$message_count > 0 )
//                                ? 'removeAjaxResistantMessages();' 
//                                : 'removeNonAjaxResistantMessages();'
//                                ;
        }
        
        $response = array(
            'error_code' => self::$error_code,
            'objects' => $objects,
            'url' => self::$url,
            'eval_before' => self::$eval_before,
            'eval_after' => self::$eval_after,
            'eval_beforeNext' => self::$eval_beforeNext,
        );

        header('Content-type: application/json');
        echo json_encode($response);
        exit;
    }
           
    public static function add(){
        $object = new Ajax_Object();
        self::$objects[] = $object;
        return $object;
    }
    
    /* Wersja 'na skróty' dodawania elementów do stosu.
     * Umożliwia dodanie kodu html/tesktu wielu elementów równocześnie
     * ale blokuje możliwość ustawienia indywidualnej ewaluacji skryptów 
     * tych elementów
     */
    public static function add_arr(array $arr)
    {
        foreach($arr as $target => $content){
            self::add()->target($target)->content($content);
        }
    }
    
    /**
     * @param type $message
     * @param type $vanish
     */
    public static function error($message, $vanish = FALSE, $error_code = 405)
    {   
        self::error_code($error_code);
        
        if(is_string($message)){
            $message = Message::error()->by_key($message);
        } else if(! $message instanceof Message_Error){
            die('error error');
        }
       
        if($vanish){
           $message->skin('vanish');
        }
       
        /* Jeśli wystąpi błąd to nie renderujemy niczego innego */
        self::$objects = array();
        self::message($message, 'page-message-container');
        self::go();
    }
    
    public static function error_code($code){
        self::$error_code = $code;
    }
    
    protected static $msg_frame = false;
    
    /**
     * Domyślnie wywołanie Ajax::go() spowoduje usunięcie wybranych komunikatów wyświetlanych w bloku
     * komunikatów. Jest to domyślna akcja gdyż w większości przypadków jest porządana. Jeśli wymagane
     * jest wykonanie żądania Ajax-Paw które nie spowoduje usunięcia komunikatów należy ustawić flagę
     * 'persist_messages' na TRUE (za pomocą poniższej funkcji)
     */
    protected static $persist_messages = FALSE;
    public static function persist_messages(){
        self::$persist_messages = TRUE;
    }
    
    protected static $message_count = 0;
    
    /* Dodanie komunikatu do stosu Ajax */
    public static function message(Message_Abstract_Message $message, $target  = 'page-message-container')
    {
        if(empty(self::$msg_frame)){
            self::$msg_frame = $target;
        }
        
        self::$message_count++ ;
        
        return 
            self::add()
                ->target($target)
                ->content($message->render());
    }
}