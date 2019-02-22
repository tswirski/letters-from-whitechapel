<?php defined('SYSPATH') or die('No direct script access.');

class Rpc_Access {

    public static function logout(){
        self::resetUserPage(Server::getSocketId());
        JsonRpc::client()->notify(Server::getSocketId(), 'server.canDisconnect');
        Websocket::server()->wsClose(Server::getSocketId());
    }

    public static function resetUserPage($socketId = null) {
        JsonRpc::client()
            ->batch()
            ->notify('menu.guest')
            ->notify('page-welcome.init')
            ->send($socketId);
    }

}