<?php

/**
 *
 * Class Service_Board
 */
class Service_Board{
    /** @var array registered boards  */
    protected static $boardIdToBoardObject = [];

    /** @var array map int socketId => int boardId */
    protected static $socketIdToBoardId = [];

    /**
     * Return TRUE if boardId registered.
     * @param {string} $boardId
     * @return bool
     */
    public static function isRegisteredBoardId($boardId){
        return isset(self::$boardIdToBoardObject[$boardId]);
    }

    /**
     * Return TRUE if socketId registered.
     * @param $socketId
     * @return bool
     */
    public static function isRegisteredSocketId($socketId){
        return isset(self::$socketIdToBoardId[$socketId]);
    }

    /**
     * @param {int} $socketId
     * @returns {int}
     */
    protected static function getBoardIdBySocketId($socketId){
        return self::$socketIdToBoardId[$socketId];
    }

    /**
     * Returns Board object by socketId
     * @param {int} $socketId
     * @returns {object}
     */
    public static function getBoardBySocketId($socketId){
        return self::getBoardById(self::getBoardIdBySocketId($socketId));
    }

    /** ALIAS */
    public static function getInstanceBySocketId($socketId){
        return self::getBoardBySocketId($socketId);
    }

    /**
     * Return board object by boardId
     * @param {int} $boardId
     * @return {object}
     */
    public static function getBoardById($boardId){
        return self::$boardIdToBoardObject[$boardId];
    }


    /**
     * Return first available boardId.
     * @return int
     */
    protected static function getUniqueBoardId(){
        for($id = 0;; $id++){
            if( ! isset(self::$boardIdToBoardObject[$id])){
                return $id;
            }
        }
    }

    /**
     * Create new board, assign players
     * @param array $assignedPlayers
     * @param boolean $randomJackHideout
     * @return boolean
     */
    public static function newBoard($assignedSocketIDs, $randomJackHideout){
        /** Can't start the game if socket already registered with another board */
        foreach($assignedSocketIDs as $socketId){
            if(self::isRegisteredSocketId($socketId)){
                return false;
            }
        }

        $boardId = self::getUniqueBoardId();
        $board =  new Service_Board_Object($assignedSocketIDs);
        self::$boardIdToBoardObject[$boardId] = $board;

        foreach($board->getSocketIDs() as $socketId){
            self::$socketIdToBoardId[$socketId] = $boardId;
        }

        /** RANDOMIZE and set Random Jack Hideout */
        if($randomJackHideout) {
            $jackStartPoints = Game_Pathfinder::getJackAvailableStartPoints();
            $randomJackHideout = $jackStartPoints[array_rand($jackStartPoints)];
            $board->setRandomJackHideoutId($randomJackHideout);
        }

        $board->init();
        return true;
    }

    /**
     * Leave board, return to Dashboard
     */
    public static function quitBoard(){
        if(self::leave()){
            Service_Dashboard_General::join();
            return true;
        } return false;
    }

    /**
     * End Game
     * @return bool
     */
    public static function endGame(){
        $boardId = self::getBoardIdBySocketId(Server::getSocketId());
        $board = self::getBoardById($boardId);

        foreach($board->getSocketIDs() as $socketId){
            Arr::remove(self::$socketIdToBoardId, $socketId);
            Service_Dashboard_General::joinRemote($socketId);
        }

        Arr::remove(self::$boardIdToBoardObject, $boardId);
        return true;
    }

    /**
     * Leave the game
     * @return bool
     */
    public static function leave(){
        if(! self::isRegisteredSocketId(Server::getSocketId())){
            return false;
        }

        $boardId = self::getBoardIdBySocketId(Server::getSocketId());
        $board = self::getBoardById($boardId);
        $boardSocketIDs = $board->getSocketIDs();
        $nickname = Server::getUser()->get(Model_User::COLUMN_NICKNAME);

        // socket reassing fail
        if( ! $board->reassignSocket(Server::getSocketId())){
            foreach($boardSocketIDs as $socketId){
                Arr::remove(self::$socketIdToBoardId, $socketId);

                if(Server::getSocketId() !== $socketId){
                    Service_Dashboard_General::joinRemote($socketId);

                    JsonRpc::client()->notify($socketId, 'popup.alert', [
                        'payload' => "Player [{$nickname}] has left the game and we ware unable to reassign his character(s) to other players"
                    ]);
                }
            }
            Arr::remove(self::$boardIdToBoardObject, $boardId);
            return true;
        }

        // socket reassing succeed
        Arr::remove(self::$socketIdToBoardId, Server::getSocketId());

        JsonRpc::client()->notify($board->getSocketIDs(), 'popup.alert', [
            'payload' => "Player {$nickname} has left the game. Police Officer character(s) has been reassigned."
        ]);

        return true;
    }

    /**
     * Return list of IDs of registered boards
     * @return array
     */
    public static function debug(){

        return [
            'BoardIDs' => array_keys(self::$boardIdToBoardObject),
            'SocketIDtoBoardID' => self::$socketIdToBoardId,
            ];
    }

    /**
     * STATE MACHINE
     */

    /** Set Jack Hideout
     * @param int $hideoutId
     */
    public static function setJackHideout($hideoutId){
        return self::getBoardBySocketId(Server::getSocketId())
            ->actionSetJackHideout($hideoutId);
    }


    /** Confirm Police Readiness
     * @param int $hideoutId
     */
    public static function confirmPoliceReadiness(){
        return self::getBoardBySocketId(Server::getSocketId())
            ->actionConfirmPoliceReadiness();
    }

    /** Set Wretched Token
     * @param int $hideoutId
     * @param string $tokenType
     */
    public static function setWretchedToken($hideoutId, $tokenType){
        return self::getBoardBySocketId(Server::getSocketId())
            ->actionSetWretchedToken($hideoutId, $tokenType);
    }

    /**
     * Get Available Wretched Tokens
     */
    public static function getAvailableWretchedTokens(){
        return self::getBoardBySocketId(Server::getSocketId())
            ->getGameStateMachine()
            ->getWretchedAvailableTokens();
    }

    /**
     * Get Available Wretched Tokens Data
     */
    public static function getWretchedPutTokenMenuData($hideoutId){
        return self::getBoardBySocketId(Server::getSocketId())
            ->getGameStateMachine()
            ->getWretchedPutTokenMenuData($hideoutId);
    }

    /**
     * Get Available Police Pawn menu Data
     */
    public static function getPolicePutPawnMenuData($junctionId){
        return self::getBoardBySocketId(Server::getSocketId())
            ->getGameStateMachine()
            ->getPolicePutPawnMenuData($junctionId);
    }

    /**
     * Get Woman Available moves menu data
     * @param int $hideoutId
     */
    public static function getWomanMovesMenuData($hideoutId){
        return self::getBoardBySocketId(Server::getSocketId())
            ->getGameStateMachine()
            ->getWomanMovesMenuData($hideoutId);
    }

    /**
     * Get kill wretched menu data
     * @param int $hideoutId
     * @return array as method => description
     */
    public static function getKillWretchedMenuData($hideoutId){
        return self::getBoardBySocketId(Server::getSocketId())
            ->getGameStateMachine()
            ->getKillWretchedMenuData($hideoutId);
    }


    /**
     * Set Police Pawn
     * @param int $junctionId
     * @param string $pawnType
     */
    public static function setPolicePawn($junctionId, $pawnType){
        return self::getBoardBySocketId(Server::getSocketId())
            ->actionSetPolicePawn($junctionId, $pawnType);
    }

    /**
     * Jack decides to kill or wait
     * @param string $action
     */
    public static function killOrWait($action){
        return self::getBoardBySocketId(Server::getSocketId())
            ->actionKillOrWait($action);
    }

    /**
     * Jack kill the woman
     * @param int $hideoutId
     * @param string $method
     */
    public static function kill($hideoutId, $method){
        return self::getBoardBySocketId(Server::getSocketId())
            ->actionKill($hideoutId, $method);
    }

    /**
     * Reveal Police Pawn (jack waits)
     * @param int $junctionId
     */
    public static function revealPolicePawn($junctionId){
        return self::getBoardBySocketId(Server::getSocketId())
            ->actionRevealPolicePawn($junctionId);
    }

    /**
     * Chief of Investigation moves Woman token
     * @param int $fromHideoutId
     * @param int $toHideoutId
     */
    public static function moveWoman($fromHideoutId, $toHideoutId){
        return self::getBoardBySocketId(Server::getSocketId())
            ->actionMoveWoman($fromHideoutId, $toHideoutId);
    }

    /**
     * Move Jack from current location to given location using method.
     * If method is 'carriage' movement
     * @param int | array $hideoutId
     * @param string $method
     * @return mixed
     */
    public static function moveJack($hideoutId, $method){
        return self::getBoardBySocketId(Server::getSocketId())
            ->actionEscapeInTheNight($hideoutId, $method);
    }

    /**
     * Jack decides to enter hideout or not (he has to be in hideout for this function to take effekt).
     * @param string $action enter | pass
     */
    public static function enterHideout($action){
        return self::getBoardBySocketId(Server::getSocketId())
            ->actionEnterHideout($action);
    }

    /**
     * Move Police Officer from location to location
     * @param int $junctionId where to move current police officer
     * @return mixed
     */
    public static function movePoliceOfficer($junctionId){
        return self::getBoardBySocketId(Server::getSocketId())
            ->actionHuntingTheMonster($junctionId);
    }

    /**
     * Police Officer Action on Hideout
     * @param int $hideoutId
     * @param string $action arrest | search
     * @return mixed
     */
    public static function policeOfficerAction($hideoutId, $action){
        return self::getBoardBySocketId(Server::getSocketId())
            ->actionCluesAndSuspicion($hideoutId, $action);
    }
    

    /**
     * DEBUG
     */
    public static function debugSetDay($day){
        return self::getBoardBySocketId(Server::getSocketId())
            ->getGameStateMachine()
            ->debugSetDay($day);
    }

    public static function debugWoman(){
        return self::getBoardBySocketId(Server::getSocketId())
            ->getGameStateMachine()
            ->debugWoman();
    }


    public static function debugPolice(){
        return self::getBoardBySocketId(Server::getSocketId())
            ->getGameStateMachine()
            ->debugPolice();
    }


    public static function debugJack(){
        return self::getBoardBySocketId(Server::getSocketId())
            ->getGameStateMachine()
            ->debugJack();
    }

    public static function debugStorage(){
        return self::getBoardBySocketId(Server::getSocketId())
            ->getGameStateMachine()
            ->debugStorage();
    }

    public static function debugSet($data){
        return self::getBoardBySocketId(Server::getSocketId())
            ->getGameStateMachine()
            ->debugSet($data);
    }

    public static function debugSkipPoliceTurn(){
        return self::getBoardBySocketId(Server::getSocketId())
            ->getGameStateMachine()
            ->debugSkipPoliceTurn();
    }


}