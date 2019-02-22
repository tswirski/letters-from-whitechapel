<?php

class HTML extends Kohana_HTML
{
    public static function script_rel($file) 
    {
        $attributes['src'] = $file;

        // Set the script type
        $attributes['type'] = 'text/javascript';

        return '<script'.HTML::attributes($attributes).'></script>';
    }
    
    public static function style_rel($file)
    {
        // Set the stylesheet link
        $attributes['href'] = $file;

        // Set the stylesheet rel
        $attributes['rel'] = 'stylesheet';

        // Set the stylesheet type
        $attributes['type'] = 'text/css';

        return '<link'.HTML::attributes($attributes).' />';
    }
}