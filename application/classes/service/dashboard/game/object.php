<?php
class Service_Dashboard_Game_Object {

    /** @var string | null suma kontrola hasła gry lub null */
    protected $passwordHash = null;
    /** @var bool TRUE gdy gra zahasłowana */
    protected $usingPassword = false;
    /** @var int ID gry */
    protected $gameId = null;
    /** @var int socketId hosta */
    protected $hostSocketId = null;
    /** @var array (assoc/map) int socketId => int timestamp  */
    protected $socketIdToTimestamp = [];
    /**  @var array (list) UserId graczy zbanowanych. */
    protected $bannedUserIDs = [];
    /** @var boolean - is Jack hideout randomized? */
    protected $randomJackHideout = false;


    /** @var array (assoc/map) string role => int socketId | null */
    protected $roleToSocketId = [
        Game_Role::JACK => null,
        Game_Role::BLUE_POLICE_OFFICER => null,
        Game_Role::RED_POLICE_OFFICER => null,
        Game_Role::GREEN_POLICE_OFFICER => null,
        Game_Role::YELLOW_POLICE_OFFICER => null,
        Game_Role::BROWN_POLICE_OFFICER => null
    ];

    /**
     * Zwraca sumę kontrolną hasła lub null
     * @param boolean $usingPassword
     * @param string $password
     * @return int|null
     */
    public static function hashPasssword($usingPassword, $password){
        return $usingPassword
            ? crc32(strtolower($password))
            : null;
    }

    /**
     * Ustawia hash hasła używanego w grze
     * @param string $passwordHash
     * @return $this
     */
    protected function setPasswordHash($passwordHash){
        $this->passwordHash = $passwordHash;
        return $this;
    }

    /**
     * Zwraca hash hasła używanego w grze lub null (gdy gra publiczna)
     * @return null|string
     */
    protected function getPasswordHash(){
        return $this->passwordHash;
    }

    /**
     * Sprawdza czy podane hasło zgadza siś z hasłem użytym przy tworzeniu gry.
     * @param string $password
     * @return boolean
     */
    public function isPasswordCorrect($password){
        return $this->getPasswordHash() === self::hashPasssword(true, $password);
    }

    /**
     * Ustawia flagę informującą o tym czy w grze uąywane jest hasło.
     * @param boolean $boolean
     * @return $this
     */
    protected function setUsingPassword($boolean){
        $this->usingPassword = $boolean;
        return $this;
    }

    /**
     * Zwraca TRUE jeśli gra używa hasła, FALSE w przeciwnym wypadku
     * @return boolean
     */
    public function isUsingPassword(){
        return $this->usingPassword;
    }

    /**
     * Ustawia ID gry
     * @param string $gameId
     * @return $this
     */
    protected function setGameId($gameId){
        $this->gameId = $gameId;
        return $this;
    }

    /**
     * Zwraca ID gry
     * @return string
     */
    public function getGameId(){
        return $this->gameId;
    }

    /**
     * Ustawia ID socketu hosta
     * @param int $socketId
     * @return $this
     */
    protected function setHostSocketId($socketId){
        $this->hostSocketId = $socketId;
        return $this;
    }

    /**
     * Zwraca ID socketu hosta
     * @return int
     */
    public function getHostSocketId(){
        return $this->hostSocketId;
    }

    /**
     * Zwraca TRUE jeśli podany socketId wskazuje na socket hosta,
     * FALSE w przeciwnym wypadku
     * @param int $socketId
     * @return bool
     */
    public function isHostSocketId($socketId){
        return ($socketId === $this->getHostSocketId());
    }

    /**
     * Zwraca mapę int socketId => int timestamp
     * @return array
     */
    protected function getSocketIdToTimestamp(){
        return $this->socketIdToTimestamp;
    }

    /**
     * Zwraca listę przybindowanych socketów.
     * @return array
     */
    public function getSocketIDs(){
        return array_keys($this->getSocketIdToTimestamp());
    }

    /**
     * Zwraca ilość graczy znajdujących się w grze
     * @return int
     */
    public function getPlayerCount(){
        return count($this->getSocketIdToTimestamp());
    }

    /**
     * Rejestruje socket w grze
     * @param int $socketId
     * @return $this
     */
    protected function registerSocketId($socketId){
        $this->socketIdToTimestamp[$socketId] = Time::getCurrentTimestamp();
        return $this;
    }

    /**
     * Wyrejestrowuje socket z gry
     * @param int $socketId
     * @return $this
     */
    protected function unRegisterSocketId($socketId){
        Arr::remove($this->socketIdToTimestamp, $socketId);
        return $this;
    }

    /**
     * Zwraca TRUE jeśli podany socketId jest zarejestrowany w grze
     * @param int $socketId
     * @return bool
     */
    public function isRegisteredSocketId($socketId){
        return isset($this->getSocketIdToTimestamp()[$socketId]);
    }

    /**
     * Dodaje użytkownika do listy zbanowanych
     * @param int $userId
     * @return $this
     */
    public function setBannedUserId($userId){
        $this->bannedUserIDs[] = $userId;
        return $this;
    }

    /**
     * Zwraca TRUE jeśli użytkownik (zadany przez userId) jest zbanowany,
     * FALSE w przeciwnym wypadku
     * @param int $userId
     * @return bool
     */
    public function isBannedUserId($userId){
        return in_array($userId, $this->bannedUserIDs);
    }

    /**
     * Zwraca tablicę {string} role => {int} socketId
     * TYLKO DLA PRZYPISANYCH GRACZY
     * @return array
     */
    public function getAssignedSocketIDs(){
        return array_filter($this->roleToSocketId, function($element){
          return $element !== null;
        });
    }

    /**
     * Zwraca listę id nieprzypisanych socketów.
     * @return array
     */
    public function getUnassignedSocketIDs(){
        return array_diff($this->getSocketIDs(), $this->getAssignedSocketIDs());
    }

    /**
     * Zwraca TRUE jeśli wszystkie sloty posiadają przypisanych graczy,
     * FALSE w przeciwnym wypadku
     * @return boolean
     */
    public function isGameReadyToStart(){
        return count($this->getAssignedSocketIDs()) === count($this->roleToSocketId);
    }

    /**
     * Funkcja sprawdza czy klucz postaci jest poprawny
     * @param string $role
     * @return boolean
     */
    protected function isCorrectRole($role){
        if(array_key_exists($role, $this->roleToSocketId)){
            return true;
        } return false;
    }

    /**
     * Setter pozwalający przypisać idSocketu do postaci.
     * @param string $role
     * @param int | null $socketId
     * @returns $this
     */
    protected function setRoleSocketId($role, $socketId){
        if( ! $this->isCorrectRole($role)){
            return false;
        }
        $this->roleToSocketId[$role] = $socketId;
        return true;
    }

    /**
     * Getter zwracający id socketu przypisanego do postaci lub null (postać nie przynależna)
     * @return int|null|false
     */
    protected function getRoleSocketId($role){
        return Arr::get($this->roleToSocketId, $role, false);
    }

    /**
     * @param $boolean
     */
    protected function setRandomJackHideout($boolean){
        $this->randomJackHideout = $boolean;
    }

    /**
     * @return bool
     */
    public function isRandomJackHideout(){
        return $this->randomJackHideout;
    }

    /**
     * Konstruktor
     * @param string $gameId
     * @param boolean $usingPassword
     * @param string $password
     * @param boolean $randomJackHideout
     */
    public function __construct($gameId, $usingPassword, $password, $randomJackHideout){
        $this->setPasswordHash(self::hashPasssword($usingPassword, $password));
        $this->setUsingPassword($usingPassword);
        $this->setRandomJackHideout($randomJackHideout);
        $this->setGameId($gameId);
        $this->setHostSocketId(Server::getSocketId());

        /** Wyślij informację o nowej grze */
        Service_Dashboard_General::addGame(
            $gameId,
            $usingPassword,
            Server::getUser()->get(Model_User::COLUMN_NICKNAME),
            Server::getUser()->getAvatarPath(),
            $randomJackHideout
        );

        /** Host dołącza do gry */
        $this->joinGame();
        return true;
    }


    /**
     * Dołącza użytkownika do gry.
     * @return bool
     */
    public function joinGame(){
        if($this->isRegisteredSocketId(Server::getSocketId())){
            return false;
        }

        /**
         * Gracz ładuje stronę managera gry
         */
        JsonRpc::client()->notify(Server::getSocketId(), 'page-dashboard-game.init', [
            'templateData' => [
                'admin' => Server::isSameSocketAs($this->getHostSocketId()) ? 'true' : 'false',
                'randomJackHideout' => $this->isRandomJackHideout()
            ]
        ]);

        /**
         * Gracz otrzymuje pełną listę graczy w grze wraz z informacją o przydziałach
         */
        $batch = JsonRpc::client()->batch();
        foreach(self::getSocketIdToTimestamp() as $playerSocketId => $playerTimestamp){
            $batch->notify('page-dashboard-game.addPlayer', [
                'nickname' => Server::getUserBySocketId($playerSocketId)->get(Model_User::COLUMN_NICKNAME),
                'avatarUrl' => Server::getUserBySocketId($playerSocketId)->getAvatarPath(),
                'userId' => Server::getUserIdBySocketId($playerSocketId),
                'timestamp' => $playerTimestamp,
                'isMyAccount' => false,
                'isMyFriend' => Rpc_Player::isFriendOfMine(Server::getUserIdBySocketId($playerSocketId))
            ]);
        }

        foreach($this->roleToSocketId as $role => $playerSocketId){
            if($playerSocketId === null){
                continue;
            }

            $batch->notify('page-dashboard-game.addPlayerToRoleSlot', [
                'role' => $role,
                'userId' => Server::getUserIdBySocketId($playerSocketId)
            ]);
        }

        $batch->send(Server::getSocketId());

        /** Player is joining the game */
        $this->registerSocketId(Server::getSocketId());

        /**
         * Players are notified about new player
         */
        foreach($this->getSocketIDs() as $remoteSocketId){
            JsonRpc::client()->notify($remoteSocketId, 'page-dashboard-game.addPlayer', [
                'nickname' => Server::getUser()->get(Model_User::COLUMN_NICKNAME),
                'avatarUrl' => Server::getUser()->getAvatarPath(),
                'userId' => Server::getUserId(),
                'isMyAccount' => Server::getSocketId() === $remoteSocketId,
                'isMyFriend' => Rpc_Player::areFriends(Server::getUserIdBySocketId($remoteSocketId), Server::getUserId())
            ]);
        }
        return true;
    }

    /**
     * Opuszczenie gry przez gracza o podanym socket ID
     * @param int $leavingSocketId
     * @return bool
     */
    public function removePlayer($leavingSocketId){
        if(!$this->isRegisteredSocketId($leavingSocketId)){
            return false;
        }

        foreach($this->roleToSocketId as $role => $roleSocketId){
            if($leavingSocketId === $roleSocketId){
                $this->setRoleSocketId($role, null);
            }
        }

        $this->unRegisterSocketId($leavingSocketId);
      
        foreach($this->getSocketIDs() as $remoteSocketId) {
            JsonRpc::client()->notify($remoteSocketId, 'page-dashboard-game.removePlayer', [
                'userId' => Server::getUserIdBySocketId($leavingSocketId)
            ]);
        }
        return true;
    }

    /**
     * Zwralnia slot przypisany do postaci
     * @param {string} $role klucz postaci
     * @return null
     */
    protected function _openSlot($role){
        $socketId = $this->getRoleSocketId($role);
        if(!$socketId){
            return;
        }

        /** zwalnia slot */
        $this->setRoleSocketId($role, null);

        foreach($this->getSocketIDs() as $remoteSocketId) {
            JsonRpc::client()->notify($remoteSocketId, 'page-dashboard-game.removePlayerFromRoleSlot', [
                'role' => $role,
                'userId' => Server::getUserIdBySocketId($socketId)
            ]);
        }
    }

    /**
     * Zwalnia slot dla danej postaci.
     * @param {string} $role
     * @return bool
     */
    public function openSlot($role){
        $playerSocketId = $this->getRoleSocketId($role);

        /** nieprawidłowy klucz postaci */
        if( ! $playerSocketId){
            return false;
        }

        /** postać nie należy do gracza, a gracz nie jest hostem */
        if(!in_array(Server::getSocketId(), [$playerSocketId, $this->getHostSocketId()])){
            return false;
        }

        /** Użytkownik usunięty ze slotu przez admina */
        if($playerSocketId !== Server::getSocketId()){
            JsonRpc::client()->notify($playerSocketId, 'popup.alert', [
               'payload' => 'You ware removed from slot by admin'
            ]);
        }

        $this->_openSlot($role);
        return true;
    }

    /**
     * Przypisuje bądź zwalnia slot
     * @param string $role
     * @return boolean
     */
    public function claimToggleSlot($role){
        if(!$this->isCorrectRole($role)){
            return false;
        }

        if($role === Game_Role::JACK){
            return $this->_claimToggleJackSlot();
        }

        return $this->_claimTogglePoliceOfficerSlot($role);
    }


    /**
     * Użytkownik żąda przyłączenia do slotu jack'a
     */
    protected function _claimToggleJackSlot(){
        /**
         * Zwalnianie slotu
         */
        if($this->getRoleSocketId(Game_Role::JACK) === Server::getSocketId()){
            $this->_openSlot(Game_Role::JACK);
            return true;
        }

        /**
         * Przejmowanie slotu
         */
        $playerIsPoliceOfficer = in_array(Server::getSocketId(), [
            $this->getRoleSocketId(Game_Role::BLUE_POLICE_OFFICER),
            $this->getRoleSocketId(Game_Role::RED_POLICE_OFFICER),
            $this->getRoleSocketId(Game_Role::YELLOW_POLICE_OFFICER),
            $this->getRoleSocketId(Game_Role::GREEN_POLICE_OFFICER),
            $this->getRoleSocketId(Game_Role::BROWN_POLICE_OFFICER),
        ]);

        $jackSlotIsTaken = (bool) $this->getRoleSocketId(Game_Role::JACK);

        if($playerIsPoliceOfficer || $jackSlotIsTaken){
            JsonRpc::client()->notify(Server::getSocketId(), 'page-dashboard-game.animateRoleError', [
                'role' => Game_Role::JACK
            ]);
            return false;
        }

        $this->setRoleSocketId(Game_Role::JACK, Server::getSocketId());
        foreach($this->getSocketIDs() as $remoteSocketId) {
            JsonRpc::client()->notify($remoteSocketId, 'page-dashboard-game.addPlayerToRoleSlot', [
                'role' => Game_Role::JACK,
                'userId' => Server::getUserId()
            ]);
        }
        return true;
    }


    /**
     * Użytkownik żąda przyłączenia do slotu oficera policji
     * @param string $role nazwa postaci
     * @return boolean
     */
    protected function _claimTogglePoliceOfficerSlot($role){
        /**
         * Zwalnianie slotu
         */
        if($this->getRoleSocketId($role) === Server::getSocketId()) {
            $this->_openSlot($role);
            return true;
        }

        /** Przejmowanie slotu */
        $playerIsJack = $this->getRoleSocketId(Game_Role::JACK) === Server::getSocketId();
        $policeOfficerSlotIsTaken = (bool) $this->getRoleSocketId($role);

        if($playerIsJack || $policeOfficerSlotIsTaken){
            JsonRpc::client()->notify(Server::getSocketId(), 'page-dashboard-game.animateRoleError', [
                'role' => $role
            ]);
            return false;
        }

        $this->setRoleSocketId($role, Server::getSocketId());

        foreach($this->getSocketIDs() as $remoteSocketId) {
            JsonRpc::client()->notify($remoteSocketId, 'page-dashboard-game.addPlayerToRoleSlot', [
                'role' => $role,
                'userId' => Server::getUserId()
            ]);
        }
        return true;
    }
}