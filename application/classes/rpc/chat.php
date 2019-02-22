<?php defined('SYSPATH') or die('No direct script access.');

class Rpc_Chat {

//    const INTERFACE_SERVICE_CHAT = 'Interface_Service_Chat';
//    const INTERFACE_INSTANCE_CHAT = 'Interface_Service_Instance_Chat';

    const ROOM_GENERAL = 'general';
    const ROOM_BOARD = 'board';
    const ROOM_GAME = 'game';

    protected static $map = [
        self::ROOM_GAME => Service_Dashboard_Game::class,
        self::ROOM_BOARD => Service_Board::class,
        self::ROOM_GENERAL => Service_Dashboard_General::class
    ];

    /**
     * Return Service Class Name by key
     * @param string $key
     * @return string
     */
    protected static function getServiceClassForKey($key){

        if( ! isset(self::$map[$key])){
            throw new Exception("Invalid Service Key");
        }

        $serviceClass = self::$map[$key];

//        if( ! in_array(self::INTERFACE_SERVICE_CHAT, class_implements($serviceClass))){
//            throw new Exception("{$serviceClass} is not implementation of " . self::INTERFACE_SERVICE_CHAT);
//        }

        return $serviceClass;
    }

    /**
     * Send $message to Service given by $key.
     * Message will be send if User is registerd with Service or Services Instance.
     * @param string $message
     * @param string $room
     */
    public static function message($message, $room = self::ROOM_GENERAL){
        if($room === self::ROOM_GENERAL) {
            return self::messageGeneralChat($message);
        }

        return self::messageServiceInstance($room, $message);
    }

    /**
     * Message a General ChatRoom
     * @param {string} $message
     * @return boolean
     */
    protected static function messageGeneralChat($message){
        if (!Service_Dashboard_General::isRegisteredSocketId(Server::getSocketId())) {
            throw new Exception("Not registered to Service [". self::ROOM_GENERAL ."]");
        }

        self::publish(Service_Dashboard_General::getSocketIDs(), self::ROOM_GENERAL, $message);
        return true;
    }

    /**
     * Service have to implement Interface_Service_Chat
     * @param {string} $room
     * @param {string} $message
     * @return boolean
     */
    protected static function messageServiceInstance($room, $message){
        $class = self::getServiceClassForKey($room);

        if(!call_user_func([$class, 'isRegisteredSocketId'], Server::getSocketId())){
            throw new Exception("Not registered to Service [$room]");
        }

        $instance = call_user_func([$class, 'getInstanceBySocketId'], Server::getSocketId());

//        if( ! in_array(self::INTERFACE_INSTANCE_CHAT, class_implements($instance))){
//            throw new Exception("$class instance is not implementation of " . self::INTERFACE_INSTANCE_CHAT);
//        }

        self::publish($instance->getSocketIDs(), $room, $message);
        return true;
    }



    /**
     * Rozsyła wiadomość na wszystkie sockety z listy.
     * @param {array} $sockets
     * @param {string} $room
     * @param {string} $message
     * @return null
     */
    protected static function publish(array $sockets, $room, $message){
        foreach ($sockets as $receiverSocketId) {

            $method = ($receiverSocketId !== Server::getSocketId())
            ? $room . '.chat.message'
            : $room . '.chat.message.self';

            JsonRpc::client()->notify($receiverSocketId, $method, [
                'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
                'avatarUrl' => Server::getUser()->getAvatarPath(),
                'nickname' => Server::getUser()->get(Model_User::COLUMN_NICKNAME),
                'userId' => Server::getUserId(),
            ]);
        }
    }
}
