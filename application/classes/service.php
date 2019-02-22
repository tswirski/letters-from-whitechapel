<?php

class Service{
    public static function getServiceClassById($serviceId){
        return Kohana::$config->load('services')[$serviceId];
    }

    public static function getServiceIdByClass($class){
        return array_search($class, (array) Kohana::$config->load('services'));
    }

    public static function unsubscribe(){
        foreach(Kohana::$config->load('services') as $service){
            $service::leave();
        }
    }
}