<?php

class Service_Board_Object {
    protected
        /** @var Game_Machine game state machine */
        $machine,
        /** @var array */
        $roleToSocketId = [];

    public function __construct(array $roleToSocketId){
        $this->roleToSocketId = $roleToSocketId;
        $this->machine = new Game_Machine($this);
    }

    /**
     * @return Game_Machine
     */
    public function getGameStateMachine(){
        return $this->machine;
    }

    /**
     * Returns map of assigned assigned sockets as {string} role => {int} $socketId
     * @return array
     */
    public function getRolesToSocketIDs(){
        return $this->roleToSocketId;
    }

    /**
     * Return socketId for given role
     * @param string $role
     * @return int
     */
    public function getSocketIdByRole($role){
        return $this->getRolesToSocketIDs()[$role];
    }

    /**
     * Returns roles assigned to socket (as array)
     * @param int $socketId
     * @return array
     */
    public function getRolesForSocketId($socketId){
        $output = [];
        foreach($this->getRolesToSocketIDs() as $role => $rolesSocketId){
            if($socketId == $rolesSocketId){
                $output[] = $role;
            }
        }
        return $output;
    }

    /**
     * Get socketIDs for allo ther player
     */
    public function getOtherSocketIDs(){
        return array_diff($this->getSocketIDs(), [Server::getSocketId()]);
    }

    /**
     * Return list of all socket id except for one for given role
     * @param string $role
     * @return array
     */
    public function getSocketIDsWithoutRole($role){
        return array_diff($this->getSocketIDs(), [$this->getSocketIdByRole($role)]);
    }

    /**
     * Return user nickname by role
     * @param string $role
     * @return string
     */
    public function getNicknameByRole($role){
        return  Server::getUserBySocketId($this->getSocketIdByRole($role))
            ->get(Model_User::COLUMN_NICKNAME);
    }

    /**
     * Return list of all socketIDs
     * @returns array
     */
    public function getSocketIDs(){
        return array_values(array_unique($this->getRolesToSocketIDs()));
    }

    /**
     * Return TRUE if given socket belongs to Jack, FALSE otherwise
     * @param {int} $socketId
     * @return bool
     */
    public function isJackSocketId($socketId){
        return $this->getRolesToSocketIDs()[Game_Role::JACK] === $socketId;
    }

    /**
     * Returns player cont.
     * @return int
     */
    public function getPlayerCount(){
        return count($this->getSocketIDs());
    }

    /** @var  int Randomized Jack Hideout Id */
    protected $randomJackHideoutId;

    /**
     * This function is used to set Randomized Jack Hideout
     * @param int $hideoutId
     * @return $this
     */
    public function setRandomJackHideoutId($hideoutId){
        $this->randomJackHideoutId = $hideoutId;
        return $this;
    }

    /**
     * Get Jack Randomized Hideout Id
     * @return mixed
     */
    public function getRandomJackHideoutId(){
        return $this->randomJackHideoutId;
    }

    /**
     * Check if Randomized Hideout ID is available
     * @return boolean
     */
    public function isRandomJackHideout(){
        return $this->randomJackHideoutId !== null;
    }
    
    /**
     * Get Character data used to initialize board roles-widget
     * @param int base socket id - the socket representing user the data is prepared for.
     * @return array
     */
    protected function getCharacters($baseSocketId){
        $characters = [];
        foreach($this->getRolesToSocketIDs() as $character => $socketId){
            $characters[$character] = [
                'userId' => Server::getUserIdBySocketId($socketId),
                'nickname' => Server::getUserBySocketId($socketId)->get(Model_User::COLUMN_NICKNAME),
                'avatarUrl' => Server::getUserBySocketId($socketId)->getAvatarPath(),
                'isMyAccount' => $socketId == $baseSocketId,
                'isMyFriend' => Rpc_Player::areFriends($baseSocketId, $socketId)
            ];
        }
        return $characters;
    }

    /**
     * Call this function to begin game!
     */
    public function init(){

        foreach($this->getSocketIDs() as $socketId){
            JsonRpc::client()->batch()
                ->notify('page-board.init', [
                    'players' => $this->getCharacters($socketId)
                ])
                ->notify('page-board.addLogMessage', [
                    'message' => 'The game has started'
                ])
                ->send($socketId);
        }

        $this->getGameStateMachine()->run();
    }

    /**
     * Return socketId for PoliceOfficer player with less roles to play,
     * excluding given socketId
     * @param int socketId
     * @return int
     */
    protected function getSocketIdForLessOccupiedPoliceOfficerPlayer($excludeSocketId)
    {
        $policeOfficerSocketToRoles = [];
        foreach ($this->getRolesToSocketIDs() as $role => $socketId) {
            if ($role === Game_Role::JACK) {
                continue;
            }

            if ($excludeSocketId === $socketId) {
                continue;
            }

            $policeOfficerSocketToRoles[$socketId][] = $role;
        }

        uasort($policeOfficerSocketToRoles, function($a, $b){
            return count($a) - count($b);
        });

        reset($policeOfficerSocketToRoles);
        return key($policeOfficerSocketToRoles);
    }

    /**
     * If player is about to leave then we attempt to reassign his role to other player.
     * this will fail if Jack leaves or if no other Police Officers are playing in this game.
     * Returns TRUE on success, FALSE otherwise.
     * @param int $leavingSocketId
     * @returns boolean
     */
    public function reassignSocket($leavingSocketId){
        if($this->isJackSocketId($leavingSocketId) || $this->getPlayerCount() <= 2){
            return false;
        }

        /** @var $activeRoleSocketId */
        $activeRoleSocketId = $this->getSocketIdByRole($this->getGameStateMachine()->getGraphRole());

        foreach($this->getRolesToSocketIDs() as $role => $socketId){
            if($role === Game_Role::JACK){
                continue;
            }

            if($socketId === $leavingSocketId){
                $replacementSocketId = $this->getSocketIdForLessOccupiedPoliceOfficerPlayer($leavingSocketId);
                $this->roleToSocketId[$role] = $replacementSocketId;
            }
        }

        // update board roles-widget
        foreach($this->getSocketIDs() as $socketId){
            JsonRpc::client()->notify($socketId, 'page-board.setRoles', [
                'players' => $this->getCharacters($socketId)
            ]);
        }

        if($activeRoleSocketId === $leavingSocketId) {
            // update game if leaving player had active turn.
            $this->getGameStateMachine()->run();
        }

        return true;
    }


    /**
     * Magic method used to access game state machine
     * @param string $method
     * @param array $params
     */
    public function __call($method, $params){
        return $this->getGameStateMachine()
            ->call($method,$params);
    }
}