<?php defined('SYSPATH') or die('No direct script access.');

class Date extends Kohana_Date
{
    public static function daysDiff($timestamp)
    {
        $datediff = $timestamp-time();
        return floor($datediff/(60*60*24));
    }
    
    public static function format(&$date, $format = 'd.m.Y')
    {
        if(is_object($date))
        {
            $date = date_format($date, $format);
        }
        elseif(is_numeric($date))
        {
            $date = date($format, $date);
        }
        else
        { 
            $date = date($format, strtotime($date));
        }
        return $date;
    }
    
     public static function convert($date, $format = 'd.m.Y')
    {
        if(is_object($date))
        {
            $date = date_format($date, $format);
        }
        elseif(is_numeric($date))
        {
            $date = date($format, $date);
        }
        else
        { 
            $date = date($format, strtotime($date));
        }
        return $date;
    }
    
    public static function timestamp($date)
    {
        if(is_object($date))
        {
            $date = $date->getTimestamp();
        }
        else
        { 
            $date = strtotime($date);
        }
        return $date;
    }
}