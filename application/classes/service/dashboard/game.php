<?php

class Service_Dashboard_Game implements Abstract_Service {

    /** @var array Mapa Obiektów Gier, kluczem jest id gry, a wartością obiekt gry */
    protected static $gameIdToGameObject = [];

    /** @var array Mapa przypisująca id gry do id socketu gracza*/
    protected static $socketIdToGameId = [];

    /**
     * DEBUG
     * @return array
     */
    public static function debug()
    {
        return [
            'sockets' => array_keys(self::$socketIdToGameId),
            'games' => array_keys(self::$gameIdToGameObject)
        ];
    }

    /**
     * Zwraca TRUE jeśli gra o podanym ID jest zarejestrowana.
     * @param {string} $gameId
     * @return bool
     */
    public static function isRegisteredGameId($gameId){
        return isset(self::$gameIdToGameObject[$gameId]);
    }

    /**
     * Zwraca TRUE jeśli socket jest zarejestrowany w dowolnej grze.
     * FALSE w przeciwnym wypadku.
     * @param {int} $socketId
     * @return {boolean}
     */
    public static function isRegisteredSocketId($socketId){
        return isset(self::$socketIdToGameId[$socketId]);
    }


    /**
     * Zwraca ID gry przypisanej do podanego $socketId
     * @param {int} $socketId
     * @returns {int}
     */
    protected static function getGameIdBySocketId($socketId){
        return self::$socketIdToGameId[$socketId];
    }

    /**
     * Zwraca obiekt gry przypisany do danego ID
     * @param {int} $gameId
     * @return {object}
     */
    public static function getGameById($gameId){
        return self::$gameIdToGameObject[$gameId];
    }

    /**
     * Zwraca obiekt gry przypisany do danego socketId
     * @param {int} $socketId
     * @returns {object}
     */
    public static function getGameBySocketId($socketId){
        return self::getGameById(self::getGameIdBySocketId($socketId));
    }

    /** ALIAS */
    public static function getInstanceBySocketId($socketId){
        return self::getGameBySocketId($socketId);
    }

    /**
     * @return string
     */
    protected static function getUniqueGameHashId() {
        $uniqueId = null;
        do {
            $uniqueId = Token::random(7);
        } while (self::isRegisteredGameId($uniqueId) || Service_Board::isRegisteredBoardId($uniqueId));
        return $uniqueId;
    }

    /**
     * Zwraca listę promowanych ID gier które powinny zostać zasugerowane
     * dołączającemu użytkownikowi.
     */
    public static function getRecentGameIDs(){
        return array_keys(self::$gameIdToGameObject);
    }

    /**
     * Tworzy obiekt nowej gry. Przypisuje gracza tworzącego grę jako Operatora (Admina),
     * ładuje widok gry dla użytkownika. Rejestruje id socketu gracza.
     * @param {string} $privacy poziom prywatności gry
     * @param (string) $password opcjonalne hasło gry
     * @param boolean $randomJackHideout
     * @return boolean
     */
    public static function createNewGame($usingPassword, $password, $randomJackHideout = null) {
        if(self::isRegisteredSocketId(Server::getSocketId())){
            return false;
        }
        Service_Dashboard_General::leave();

        $usingPassword = $usingPassword === 'yes' ? true : false;
        $randomJackHideout = $randomJackHideout === 'yes' ? true : false;
        $uniqueId = self::getUniqueGameHashId();

        self::$gameIdToGameObject[$uniqueId] = (new Service_Dashboard_Game_Object($uniqueId, $usingPassword, $password, $randomJackHideout));
        self::$socketIdToGameId[Server::getSocketId()] = $uniqueId;
        return true;
    }

    /**
     * Weryfikuje czy gracz może dołączyć do gry o podanym $gameId
     * @param $gameId
     * @return bool
     */
    protected static function canJoinGame($gameId){
        if(self::isRegisteredSocketId(Server::getSocketId())){
            return false;
        }

        /** Brak gry o podanym ID */
        if( ! self::isRegisteredGameId($gameId)){
            return false;
        }

        /** BANNED USER */
        if(self::getGameById($gameId)->isBannedUserId(Server::getUserId())){
            JsonRpc::client()->notify(Server::getSocketId(), 'popup.alert', [
                'payload' => 'You are banned from this game'
            ]);
            return false;
        }

        return true;
    }

    /**
     * Dołączenie do gry publicznej
     * @param {string} $gameId
     * @return {boolean}
     */
    public static function joinGame($gameId){
        if(!self::canJoinGame($gameId)){
            return false;
        }

        if(self::getGameById($gameId)->isUsingPassword()){
            JsonRpc::client()->notify(Server::getSocketId(), 'popup-game-password.init', [
               'gameId' => $gameId
            ]);
            return false;
        }

        /** Opuść Service_Dashboard_General */
        Service_Dashboard_General::leave();

        /** Dołącz do wybranej gry */
        self::getGameById($gameId)->joinGame(Server::getSocketId());
        self::$socketIdToGameId[Server::getSocketId()] = $gameId;

        /** Uaktualnienie stanu gier */
        Service_Dashboard_General::updateGame($gameId, self::getGameById($gameId)->getPlayerCount());
        return true;
    }

    /**
     * Dołączenie do zahasłowanej gry
     * @param {string} $gameId
     * @param {string} $password
     * @return {boolean}
     */
    public static function joinPasswordProtectedGame($gameId, $password){
        if(!self::canJoinGame($gameId)){
            return false;
        }

        if( ! self::getGameById($gameId)->isPasswordCorrect($password)) {
            JsonRpc::client()->notify(Server::getSocketId(), 'popup.alert', [
                'payload' => 'Invalid password'
            ]);

            return false;
        }

        /** Opuść Service_Dashboard_General */
        Service_Dashboard_General::leave();

        /** Dołącz do wybranej gry */
        self::getGameById($gameId)->joinGame(Server::getSocketId());
        self::$socketIdToGameId[Server::getSocketId()] = $gameId;
        return true;
    }

    /**
     * Użytkownik żąda przyłączenia do slotu postaci
     * @param string klucz postaci
     * @return boolean
     */
    public static function claimToggleSlot($role){
        if(!self::isRegisteredSocketId(Server::getSocketId())){
            return false;
        }
        self::getGameBySocketId(Server::getSocketId())->claimToggleSlot($role);
        return true;
    }

    /**
     * Opcja Administracyjna, zwalnianie slotu
     * @param string klucz postaci
     * @return boolean
     */
    public static function openSlot($role){
        if(!self::isRegisteredSocketId(Server::getSocketId())){
            return false;
        }

        $game = self::getGameBySocketId(Server::getSocketId());
        return $game->openSlot($role);
    }

    /**
     * Tworzy planszę dla podanej gry, wylogowuje nieprzypisanych graczy
     * @param int $gameId
     * @param object $game
     * @return boolean
     */
    protected static function _evaluateGameToBoard($game){
        foreach($game->getUnassignedSocketIDs() as $socketId){
            Arr::remove(self::$socketIdToGameId, $socketId);

            JsonRpc::client()->notify($socketId, 'popup.alert', [
                'payload' => "Game has started without you"
            ]);

            Service_Dashboard_General::joinRemote($socketId);
        }

        foreach($game->getAssignedSocketIDs() as $socketId){
            Arr::remove(self::$socketIdToGameId, $socketId);
        }

        Service_Board::newBoard($game->getAssignedSocketIDs(), $game->isRandomJackHideout());
    }


    /**
     * Odpala grę jeśli w grze jest komplet przypisanych graczy.
     * Graczy nie będących przypisanymi wyrzuca do ekranu gównego,
     * startuje grę (tworzy planszę, przypisuje graczy)
     * @return boolean
     */
    public static function startGame()
    {
        if ( ! self::isRegisteredSocketId(Server::getSocketId())) {
            return false;
        }

        $gameId = self::getGameIdBySocketId(Server::getSocketId());
        $game = self::getGameById($gameId);
        if (!$game->isHostSocketId(Server::getSocketId())) {
            return false;
        }

        if (!$game->isGameReadyToStart()) {
            JsonRpc::client()->notify(Server::getSocketId(), 'popup.alert', [
                'payload' => "You can't start the game until all slots are taken"
            ]);
            return false;
        }

        Arr::remove(self::$gameIdToGameObject, $gameId);
        Service_Dashboard_General::removeGame($gameId);
        return self::_evaluateGameToBoard($game);
    }

    /**
     * Kicknięcie użytkownika
     * @param {int} $userId
     * @return {boolean}
     */
    public static function kickUser($userId) {
        if ( ! self::isRegisteredSocketId(Server::getSocketId())) {
            return false;
        }

        $gameId = self::getGameIdBySocketId(Server::getSocketId());
        $game = self::getGameById($gameId);
        $kickedSocketId = Server::getSocketIdByUserId($userId);

        if( ! $game->isRegisteredSocketId($kickedSocketId) || $game->isHostSocketId($kickedSocketId)){
            return false;
        }

        $game->removePlayer($kickedSocketId);
        Arr::remove(self::$socketIdToGameId, $kickedSocketId);

        Service_Dashboard_General::updateGame($gameId, $game->getPlayerCount());
        Service_Dashboard_General::joinRemote($kickedSocketId);
        JsonRpc::client()->notify($kickedSocketId, 'popup.alert', [
            'payload' => "You have been kicked from game"
        ]);

        return true;
    }

    /**
     * Banowanie użytkownika
     * @param {int} $userId
     * @return {boolean}
     */
    public static function banUser($userId){
        if ( ! self::isRegisteredSocketId(Server::getSocketId())) {
             return false;
         }

         $gameId = self::getGameIdBySocketId(Server::getSocketId());
         $game = self::getGameById($gameId);
         $kickedSocketId = Server::getSocketIdByUserId($userId);

         if( ! $game->isRegisteredSocketId($kickedSocketId) || $game->isHostSocketId($kickedSocketId)){
             return false;
         }

         $game->removePlayer($kickedSocketId);
         $game->setBannedUserId($userId);
         Arr::remove(self::$socketIdToGameId, $kickedSocketId);

         Service_Dashboard_General::updateGame($gameId, $game->getPlayerCount());
         Service_Dashboard_General::joinRemote($kickedSocketId);
         JsonRpc::client()->notify($kickedSocketId, 'popup.alert', [
             'payload' => "You have been banned from game"
         ]);

         return true;
     }


    /**
     * Wyjdź z gry, powrót do swrwisu głównego
     */
    public static function quitGame(){
        if(self::leave()){
            Service_Dashboard_General::join();
            return true;
        } return false;
    }

    /**
     * Rozłączenie gracza będącego hostem gry
     * @return bool
     */
    protected static function _leaveHost($game, $gameId){
        /** Usuniecie hosta z gry */
        Arr::remove(self::$socketIdToGameId, Server::getSocketId());

        /** Usuń graczy z gry */
        foreach($game->getSocketIDs() as $playerSocketId){
            if(Server::getSocketId() === $playerSocketId){
                continue;
            }

            Arr::remove(self::$socketIdToGameId, $playerSocketId);
            JsonRpc::client()->notify($playerSocketId, 'popup.alert', ["payload" =>
                'Host has left the game'
            ]);
            Service_Dashboard_General::joinRemote($playerSocketId);
        }
        Service_Dashboard_General::removeGame($gameId);

        /** Usuń obiekt gry z mapy */
        Arr::remove(self::$gameIdToGameObject, $gameId);
    }

    /**
     * Odłączenie aktualnego gracza od gry,
     * @return {boolean} TRUE na powodzeniu, FALSE w przeciwnym wypadku
     */
    public static function leave(){
        /** Gracz nie uczestniczy w grze */
        if( ! self::isRegisteredSocketId(Server::getSocketId())){
            return false;
        }

        $gameId = self::getGameIdBySocketId(Server::getSocketId());
        $game = self::getGameById($gameId);

        /**
         * Gracz jest HOSTem
         */
        if($game->isHostSocketId(Server::getSocketId())){
            self::_leaveHost($game, $gameId);
        }

        /**
         * Gracz nie jest HOSTem
         */
        else {
            $game->removePlayer(Server::getSocketId());

            /** Uaktualnienie stanu gier */
            Service_Dashboard_General::updateGame($gameId, self::getGameById($gameId)->getPlayerCount());
            Arr::remove(self::$socketIdToGameId, Server::getSocketId());
        }
        return true;
    }
}