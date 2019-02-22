<?php

class Token extends Abstract_Singleton
{
    /* @var Session_Stencil_Token Obiekt szablonu sesji modułu Token */
    protected $session;
    
    protected function after_init()
    {
        /* Tworzenie obiektu szablonu sesji */
        $this->session = Session_Stencil_Token::instance();        
    }
    
    /* @return string losowa nazwa dla obrazka 'tokenImage' + losowy numer */
    public function get_random_name()
    {  
        return 'tokenImage'.rand(122,463);
    }
    
    public function get_url()
    {
        return Url::base(TRUE, TRUE).$this->get_random_name();
    }
    
    /**
     * 
     * @param type $length
     */
    public static function random($code_length)
    {
        $alphanum = "123456789ABCDEFGHIJKLMN123456789PQRSTUVWXYZ123456789abcdefghijkmnoprstuwxyz";
        $rand = substr(str_shuffle($alphanum), 0, $code_length);
        return $rand;
    }
    
    /**
     * @param int $code_length Długość generowanego kodu
     * @param bool $return_image TRUE - zwracany jest obrazek, FALSE - zwracany jest text 
     * @return stream Zwraca obrazek (nagłówek jpg i zawartość) 
     */
    public function create($code_length=6, $return_image = TRUE)
    {
        /* Ciąg znaków z których losowane bedą znaki pokazane w obrazku */
        $alphanum = "123456789ABCDabcdefEFGghijkmnoprstHIJKLMNuwxyz1234ab567cd89efPQghRSijTUkmnopVWrstuwxyXYZabcd12efg34hijk56mnopr789rst";
        $rand = substr(str_shuffle($alphanum), 0, $code_length);
        $this->session->token = md5($rand);
        
        if($return_image)
        {
            $image = imagecreate(54, 14);
            $imgBgColor = imagecolorallocate ($image, 220, 220, 220); 
            $textColor = imagecolorallocate ($image, 55, 55, 55); 
            imagestring ($image, 5, 0, -1, $rand, $textColor); 
            /* HEADER powinien być ustawiony w kontrolerze wywołującym tą metodę poprzez
            * wywołanie Response->headers();
            * header('Content-type: image/jpeg');
            */
            imagejpeg($image);
            imagedestroy($image);
        }
        else return $rand;
    }
    /**
     * Funkcja ustawia losowy ciąg znaków jako kod tokena. Wywołanie tej funkcj po wykonaniu akcji
     * która była zablokowana przez weryfikację token'a ma zapobiec wielokrotnemu wykożystaniu tego
     * samego kodu. 
     */
    public function reset()
    {
        $this->session->token = strrev(md5(microtime(true)));
    }
    
    public function check($text)
    {
        return md5($text) === $this->session->token;
    }
    
    public function get()
    {
        return $this->session->token;
    }
}

