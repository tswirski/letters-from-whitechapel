<?php

class Session_Stencil{
    
    public static function instance($name){
        $name ='Session_Stencil_'.$name;
        return $name::instance();
    }
    
}