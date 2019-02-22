<?php

class Time {

    const SECONDS_IN_DAY = 86400;

    public static function getCurrentTimestamp() {
        return (new DateTime)->getTimestamp();
    }

    public static function getCurrentUSTime(){
        return (new DateTime)->format('h:i A');
    }

}
