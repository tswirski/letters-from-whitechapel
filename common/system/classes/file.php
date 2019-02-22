<?php defined('SYSPATH') or die('No direct access allowed.');

class File extends Kohana_File
{

    public static function exists($file_path)
    {
        clearstatcache();
        return file_exists($file_path);
    }
    
/**
 * Kasuje plik lub pliki. 
 * Można podać :
 *  1: Pełną ścieżkę do pliku (jako $path) oraz nazwę pliku NULL,
 *  2: Ścieżkę do katalogu oraz nazwę pliku osobno,
 *  3: Ścieżkę do katalogu oraz nazwy plików w postaci tablicy.
 * 
 * UWAGA! Przy usuwaniu wielu wpisów zamiast znacznika powodzenia otrzymamy liczbę usuniętych elementów.
 * 
 * @param string $path
 * @param mixed (string/array/NULL) $file_name 
 * @return mixed (bool/int) (TRUE/FALSE/ liczba usuniętych elementów)
 */
    public static function delete($path, $file_name = NULL)
    {
        /* Zakładamy tutaj, że $path wskazuje bezpośrednio na plik */
        if($file_name === NULL)
        {
            return @unlink($path);
        }
        /* Zakładamy, że path wskazuje na katalog */
        else
        {
            $directory = Dir::clarify($path);

            if(is_string($file_name))
            {
                return @unlink($directory.$file_name);
            }
            elseif(is_array($file_name))
            {
                $delete_count = 0;
                foreach($file_name as $_file_name)
                {
                    if(@unlink($directory.$_file_name))
                    {
                        $delete_count++;
                    }
                }
                return $delete_count;
            }
        }
        /* No niestety.. nic nie zostało usunięte */
        return FALSE;
    }

    
    /**
     * Zwraca rozszerzenie pliku
     * @param string $file_path
     * @return string
     */
    public static function get_extension($file_path)
    {
        return pathinfo($file_path,PATHINFO_EXTENSION);
    }
    
    public static function get_filename($file_path)
    {
        return pathinfo($file_path,PATHINFO_FILENAME);
    }
}