<?php

defined('SYSPATH') or die('No direct script access.');

class Websocket {

    protected static $_server;

    public static function set(Websocket_Server $server) {
        self::$_server = $server;
    }

    public static function server() {
        return self::$_server;
    }

    public static function consoleLogOutput($socketID, $userID, $json) {
        $data = json_decode($json);
        $data = print_r($data, true);
        echo "\n OUTPUT [socket: {$socketID}] [user: $userID] : $data \n";
    }

    public static function consoleLogInput($socketID, $userID, $json) {
        $data = json_decode($json);
        $data = print_r($data, true);
        echo "\n INPUT [socket: {$socketID}] [user: $userID] : $data \n";
    }

    public static function consoleLogError($userID, $socketID, $message){
        echo "\n ERROR [socket: {$socketID}] [user: $userID] : $message \n";
    }
}
