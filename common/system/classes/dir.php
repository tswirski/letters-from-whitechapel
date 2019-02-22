<?php defined('SYSPATH') or die('No direct access allowed.');

class Dir
{

/**
 * Funkcja standardyzuje ścieżkę do katalogu.
 * Ustawiany jest format linuxowski oraz dodawany jest końcowy slash    C:\test  ->  C:/test/
 * @param string $directory
 * @return string
 */
public static function clarify($directory)
{
    if(empty($directory)){
        return '';
    }
    $directory = str_replace('\\','/', $directory);
    return substr($directory, -1) === '/' ? $directory : $directory.'/'; 
}

/**
 * Funkcja gwarantuje, że katalog zostanie opróżniony (oraz skasowany jeśli ustawiono $self_delete = TRUE).
 * @param string $directory Ścieżka do katalogu (nie wymaga clarify_dir gdyż ten jest robiony wewnątrz funkcji).
 * @param bool $self_delete Skasować tez katalog?
 * @return bool
 */
public static function ensure_empty($directory, $self_delete = FALSE) 
{
    $directory = self::clarify($directory);
    if(Arr::in_array_match($directory, 
        array(	
            '/^\.{0,2}\/$/',   // Katalog  '(..)/' 
            '/^\w{1}\:\/{1}$/', // Katalog '?:/'
            '/^'.addcslashes(DOCROOT, '\/').'$/', // Główny katalog programu
    ))) {
        // Tutaj skrypt logujący błąd!
        die('Awaryjne zatrzymanie skryptu. Przepraszamy.');
    }

    if ( ! $directory_handler = @opendir($directory))
    {	
        return;
    }
    while (false !== ($file = readdir($directory_handler))) 
    {
        if($file=='.' || $file=='..') 
        {
            continue;
        }
        if ( ! @unlink($directory.$file)) 
        {
            self::ensure_empty_dir($directory.$file, true);
        }
    }
    closedir($directory_handler);
    if ($self_delete)
    {
        @rmdir($directory);
    }
}
    
    /**
     * Sprawdza czy podana ścieżka wskazuje na katalog. 
     * @param string $dir_path ścieżka 
     * @return bool
     */
    public static function validate($dir_path)
    {
        return  is_dir($dir_path) AND ! is_file($dir_path);
    }
    
    /* Alias dla 'validate'; */
    public static function exists($dir_path) { return self::validate($dir_path); }
    
    /**
     * Gwarantuje, że podana ścieżka wskazuje na katalog. (Jeśli nie ma takiego katalogu to go tworzy) 
     * 
     * @param string $dir_path ścieżka
     * @return mixed (string/FALSE) Zwraca frazę 'CREATED' (gdy utworzono katalog), 'EXISTS' (gdy istniał) lub FALSE (gdy błąd).
     */
    public static function ensure_directory($dir_path, $chmod = 0777)
    {
        if( ! self::validate($dir_path))
        {
            return @mkdir($dir_path, $chmod, TRUE) ? 'CREATED' : FALSE;
        }
        else
        {
            return 'EXISTS';
        }
    }
}
