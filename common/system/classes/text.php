<?php

class Text{
    
    public static function limit($string, $limit, $suffix = '...'){
          return (strlen($string) > $limit) ? substr($string,0,$limit).$suffix : $string;
    }
    
}