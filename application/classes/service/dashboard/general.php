<?php

class Service_Dashboard_General implements Abstract_Service{

    /**
     * Map socketId to timestamp
     * @var {array}
     */
    protected static $socketIdToTimestamp = [];

    /**
     * Get 'socketId to timestamp' map
     * @return {array}
     */
    public static function getSocketIdToTimestamp(){
        return self::$socketIdToTimestamp;
    }

    /**
     * Checks if user is registered with this service
     * @param {int} $socketId
     * @return {boolean}
     */
    public static function isRegisteredSocketId($socketId){
        return isset(self::getSocketIdToTimestamp()[$socketId]);
    }

    /**
     * Get list of all registered sockets
     * @return array
     */
    public static function getSocketIDs(){
        return array_keys(self::getSocketIdToTimestamp());
    }

    /**
     * User given with $socketId is joining the service
     * @param {int} $socketId
     * @return {boolean}
     */
    public static function joinRemote($socketId) {
        if(self::isRegisteredSocketId($socketId)){
            return false;
        }

        /** Joining user view has to be updated with all currently registered users */
        $batch = JsonRpc::client()->batch();

        $batch->notify('page-dashboard-general.init');
        foreach(self::getSocketIdToTimestamp() as $playerSocketId => $playerTimestamp){
            $batch->notify('page-dashboard-general.addPlayer', [
                'nickname' => Server::getUserBySocketId($playerSocketId)->get(Model_User::COLUMN_NICKNAME),
                'avatarUrl' => Server::getUserBySocketId($playerSocketId)->getAvatarPath(),
                'userId' => Server::getUserIdBySocketId($playerSocketId),
                'timestamp' => $playerTimestamp,
                'isMyAccount' => false,
                'isMyFriend' => Rpc_Player::areFriends(
                    Server::getUserIdBySocketId($socketId),
                    Server::getUserIdBySocketId($playerSocketId))
            ]);
        }

        foreach(Service_Dashboard_Game::getRecentGameIDs() as $gameId){
            $game = Service_Dashboard_Game::getGameById($gameId);
            $hostUserModel = Server::getUserBySocketId($game->getHostSocketId());

            $batch->notify('page-dashboard-general.addGame', [
                'gameId' => $gameId,
                'usingPassword' => $game->isUsingPassword() ? "yes" : 'no',
                'playerCount' => $game->getPlayerCount(),
                'nickname' => $hostUserModel->get(Model_User::COLUMN_NICKNAME),
                'avatarUrl' => $hostUserModel->getAvatarPath(),
                'randomJackHideout' => $game->isRandomJackHideout() //? 'true' : 'false'
            ]);
        }
        $batch->send($socketId);

        /** Lets prepare joining player's data */
        $timestamp = Time::getCurrentTimestamp();
        $nickname = Server::getUserBySocketId($socketId)->get(Model_User::COLUMN_NICKNAME);
        $avatarUrl = Server::getUserBySocketId($socketId)->getAvatarPath();
        $userId = Server::getUserIdBySocketId($socketId);

        /** And notify everybody about newly joined player */
        JsonRpc::client()->notify(self::getSocketIDs(), 'general.chat.notification', [
            'nickname' => $nickname,
            'avatarUrl' => $avatarUrl,
            'userId' => $userId,
            'message' => __('Player has joined')
        ]);

        /** Add him to the service registered socket list */
        self::$socketIdToTimestamp[$socketId] = $timestamp;

        /** And to user-list of all users */
        foreach (self::getSocketIdToTimestamp() as $remoteSocketId => $dummyTimestamp) {
            JsonRpc::client()
            ->notify($remoteSocketId, 'page-dashboard-general.addPlayer', [
                'nickname' => $nickname,
                'avatarUrl' => $avatarUrl,
                'userId' => $userId,
                'timestamp' => $timestamp,
                'isMyAccount' => ($remoteSocketId === $socketId),
                'isMyFriend' => Rpc_Player::areFriends(Server::getUserIdBySocketId($remoteSocketId), Server::getUserIdBySocketId($socketId))
            ]);
        }
    }

    /**
     * User is joining the service
     * @return {boolean}
     */
    public static function join() {
        return self::joinRemote(Server::getSocketId());
    }

    /** DISCONNECT sequence */
    public static function disconnect(){
        return self::leave();
    }

    /**
     * Unregister user and remove his representation for all subscribed users.
     * @return boolean
     */
    public static function leave() {
        $socketId = Server::getSocketId();

        if (!self::isRegisteredSocketId($socketId)) {
            return false;
        }

        /** leaving player's data */
        $nickname = Server::getUserBySocketId($socketId)->get(Model_User::COLUMN_NICKNAME);
        $avatarUrl = Server::getUserBySocketId($socketId)->getAvatarPath();
        $userId = Server::getUserIdBySocketId($socketId);

        foreach(self::getSocketIDs() as $remoteSocketId){
            if($remoteSocketId === Server::getSocketId()){
                continue;
            }

            /** And notify everybody about leaving player */
            JsonRpc::client()
                ->batch()
                ->notify('page-dashboard-general.removePlayer', [
                    'userId' => Server::getUserIdBySocketId($socketId)
                ])
                -> notify('general.chat.notification', [
                    'nickname' => $nickname,
                    'avatarUrl' => $avatarUrl,
                    'userId' => $userId,
                    'message' => __('Player has left')
                ])
                ->send($remoteSocketId);
        }
        
        unset(self::$socketIdToTimestamp[$socketId]);
        return true;
    }

    /**
     * Notify all registered users about new game
     * @param string $gameId
     * @param boolean $usingPassword
     * @param string $nickname of host player
     * @param string $avatarUrl
     * @param boolean $randomJackHideout
     * @param int $playerCount - default 1
     */
    public static function addGame($gameId, $usingPassword, $nickname, $avatarUrl, $randomJackHideout, $playerCount = 1){
        /** Gracze informowani są o powstaniu nowej gry */
        foreach(self::getSocketIdToTimestamp() as $remoteSocketId => $timestamp){
            JsonRpc::client()->notify($remoteSocketId, 'page-dashboard-general.addGame', [
                'gameId' => $gameId,
                'usingPassword' => $usingPassword ? "yes" : 'no',
                'nickname' => $nickname,
                'avatarUrl' => $avatarUrl,
                'playerCount' => $playerCount,
                'randomJackHideout' => $randomJackHideout //? 'true' : 'false'
            ]);
        }
    }

    /**
     * Rozsyła informację o zmianie ilości graczy w danej grze
     * @param {string} $gameId
     * @param {boolean} $usingPassword
     */
    public static function updateGame($gameId, $playerCount){
        foreach(self::getSocketIdToTimestamp() as $remoteSocketId => $timestamp){
            JsonRpc::client()->notify($remoteSocketId, 'page-dashboard-general.updateGame', [
                'gameId' => $gameId,
                'playerCount' => $playerCount
            ]);
        }
    }

    /**
     * Remove game for all registered users
     * @param {string} $gameId
     */
    public static function removeGame($gameId){
        foreach(self::getSocketIdToTimestamp() as $remoteSocketId => $timestamp){
            JsonRpc::client()->notify($remoteSocketId, 'page-dashboard-general.removeGame', [
                'gameId' => $gameId
            ]);
        }
    }
}