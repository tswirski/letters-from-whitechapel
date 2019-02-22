<?php
/**
 * Created by PhpStorm.
 * User: lama
 * Date: 2016-05-03
 * Time: 18:33
 *
 * State Machine for Letters from Whitechapel.
 * ACTION methods are supposed to set valid machine-state and retunr TRUE on success.
 * (false on failure). By returning TRUE action notifying state-machine to run PRE-action-callable
 * for new state.
 *
 *
 * UWAGA w pliku board.js podczas inicjalizacji menu jest błąd w koncepcji. Polega on na tym, że menu
 * inicjalizowane jest dla wszystkich elementów hideout lub junction, a w metodzie sprawdzane jest
 * czy nadana została klasa seletable - oznacza to, ze gdy zdeinicjalizujemy wszystkie elementy mające klasę selectable
 * to wciąż pozostają przypięte eventy do elementów które w tej klasy nie miały i gdy tylko ją otrzymają
 * to będą aktywne i będą wykonywać niechciane akcje. Należy przenieść aktywację elementów do metod wywołujących
 * menu  np.  actionMenuForXX(hideoutIDs) zamiast enableHideoutHover(hideoutIDs) i wewnątrz metody actionMenuForXX()
 * sprawdzać nadanie klasy, a inicjalizować menu dla wszystkich elementów !!
 */

class Game_Machine extends Game_Machine_Middleware {
    /**
     * This routine is called each time new day begins.
     * (Along with [x, 1] -pre- function);
     */
    protected function dayBegins(){
        JsonRpc::client()->batch()
            ->notify('page-board.setDay', [
                'day' => $this->getDay()
            ])
            ->notify('page-board.setAvailableMovesTo', [
                'value' => $this->getJackMoveBaseLimit()
            ])
            ->notify('page-board.setCurrentMoveTo', [
                'value' => 1
            ])
            ->notify('page-board.setAvailableSpecialMoves', [
                'moveType' => self::MOVEMENT_ALLEY,
                'value' => $this->getJackSpecialMoveLimit()[self::MOVEMENT_ALLEY]
            ])
            ->notify('page-board.setAvailableSpecialMoves', [
                'moveType' => self::MOVEMENT_CARRIAGE,
                'value' => $this->getJackSpecialMoveLimit()[self::MOVEMENT_CARRIAGE]
            ])
            ->send($this->getBoard()->getSocketIDs());

        $this->resetClues();
        $this->clearWretchedTokenAllocation();

        /** Set Available Special Moves */
        $this->setAvailableJackSpecialMoves($this->getJackSpecialMoveLimit());
        $this->setPoliceOfficerOrderForCurrentDay();
        $this->resetJackTrack();
    }

    protected $readyPlayersSockets = [];
    /**
     * PRE : Readiness
     */
    protected function preConfirmPoliceReadiness(){
        $this->readyPlayersSockets = [];

        JsonRpc::client()->batch()
            ->notify('page-board.showReadinessConfirmBox')
            ->notify('page-board.removeRolesReadinessMarkers')
            ->notify('page-board.setActiveRoles', [
                'roles' => Game_Role::getPoliceRolesArray()
            ])
            ->send($this->getBoard()->getSocketIDsWithoutRole(Game_Role::JACK));

        JsonRpc::client()->batch()
            ->notify('page-board.removeRolesReadinessMarkers')
            ->notify('page-board.setActiveRoles', [
                'roles' => Game_Role::getPoliceRolesArray()
            ])
            ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));
    }

    /**
     * ACTION : Readiness
     */
    protected function actionConfirmPoliceReadiness(){
        if(in_array(Server::getSocketId(), $this->readyPlayersSockets)){
            return false;
        }

        $this->readyPlayersSockets[] = Server::getSocketId();
        // SELF
        JsonRpc::client()->batch()
            ->notify('page-board.setRolesReadinessMarkers', [
                'roles' => $this->getBoard()->getRolesForSocketId(Server::getSocketId())
            ])
            ->notify('popup.note', [
                'message' => __('You are ready')
            ])
            ->notify('page-board.hideReadinessConfirmBox')
            ->send(Server::getSocketId());

        // OTHERS
        JsonRpc::client()->batch()
            ->notify('page-board.setRolesReadinessMarkers', [
                'roles' => $this->getBoard()->getRolesForSocketId(Server::getSocketId())
            ])
            ->notify('popup.note', [
                'message' => Server::getUserBySocketId(Server::getSocketId())->get(Model_User::COLUMN_NICKNAME) . ' '. __('is ready')
            ])
            ->send($this->getBoard()->getOtherSocketIDs());



        if($this->getBoard()->getPlayerCount() - 1 == count($this->readyPlayersSockets)){
            // ALL
            JsonRpc::client()->batch()
                ->notify('page-board.removeRolesReadinessMarkers')
                ->notify('page-board.clearMoveTrack')
                ->notify('page-board.removeClueTokens')
                ->notify('popup.note', [
                    'message' => __('All players are ready. New day has begun.')
                ])
                ->send($this->getBoard()->getSocketIDs());


            $this->readyPlayersSockets = [];
            $this->setActionId(self::ACTION_SET_WRETCHED_TOKENS);
            $this->dayBegins();
            return true;
        }

    }

    /**
     * PRE : Jack set his hideout
     */
    protected function preSetJackHideout(){

        /** !!
         * RANDOM HIDEOUT MODE, skip this phase
         */
        if($this->getBoard()->isRandomJackHideout()){
           return $this->processRandomJackHideout();
        }

        /** Jack */
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => "Jack The Ripper (you): choose your hideout now!"
            ])
            ->notify('page-board.enableHideoutHoverForIDs', [
                'IDs' => Game_Pathfinder::getJackAvailableStartPoints()
            ])
            ->notify('page-board.setActionMenuForHideoutSelection')
            ->notify('page-board.switchActiveRole', [
                'role' => Game_Role::JACK
            ])
            ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));

        /** Everybody else */
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => "Jack The Ripper ({$this->getBoard()->getNicknameByRole(Game_Role::JACK)}): chooses his hideout"
            ])
            ->notify('page-board.switchActiveRole', [
                'role' => Game_Role::JACK
            ])
            ->send($this->getBoard()->getSocketIDsWithoutRole(Game_Role::JACK));
    }

    /**
     * ACTION : Jack set his hideout
     * @param int $hideoutId
     */
    public function actionSetJackHideout($hideoutId){
        $hideoutId = $this->validHideoutId($hideoutId);
        $this->setJackHideout($hideoutId);

        foreach($this->getBoard()->getSocketIDs() as $socketId){
            if($this->getBoard()->isJackSocketId($socketId)){
                // jack only
                JsonRpc::client()->batch()
                    ->notify('page-board.addLogMessage', [
                        'message' => "Jack The Ripper (you): your hideout is [{$this->getJackHideout()}]"
                    ])
                    ->notify('page-board.setJackHideoutDisplay', [
                        'hideoutId' => $this->getJackHideout()
                    ])
                    ->notify('page-board.disableAllHideoutHover')
                    ->send($socketId);
            } else {

                // police only
                JsonRpc::client()->batch()
                    ->notify('page-board.addLogMessage', [
                        'message' => "Jack The Ripper ({$this->getBoard()->getNicknameByRole(Game_Role::JACK)}): hideout is choosen"
                    ])
                    ->send($socketId);
            }
        }

        //NEXT STEP
        $this->setActionId(self::ACTION_SET_WRETCHED_TOKENS);
        return true;
    }

    /** !!
     * Skip Jack Hideout Setup Phase
     */
    protected function processRandomJackHideout(){
        $this->setJackHideout($this->getBoard()->getRandomJackHideoutId());

        foreach($this->getBoard()->getSocketIDs() as $socketId){
            if($this->getBoard()->isJackSocketId($socketId)){
                // jack only
                JsonRpc::client()->batch()
                    ->notify('page-board.addLogMessage', [
                        'message' => "Jack Hideout Randomized, [{$this->getJackHideout()}]"
                    ])
                    ->notify('popup.alert', [
                        'payload' => "Your Random Hideout Location is: {$this->getJackHideout()}"
                    ])
                    ->notify('page-board.setJackHideoutDisplay', [
                        'hideoutId' => $this->getJackHideout()
                    ])
                    ->send($socketId);
            } else {

                // police only
                JsonRpc::client()->batch()
                    ->notify('page-board.addLogMessage', [
                        'message' => "Jack Hideout Randomized"
                    ])
                    ->send($socketId);
            }
        }

        //NEXT STEP
        $this->setActionId(self::ACTION_SET_WRETCHED_TOKENS);
        return true;
    }

    /**
     * PRE : Jack set Wretched Tokens
     */
    public function preSetWretchedTokens(){
        foreach($this->getBoard()->getSocketIDs() as $socketId){

            if($this->getBoard()->isJackSocketId($socketId)){
                // jack only
                JsonRpc::client()->batch()
                    ->notify('page-board.addLogMessage', [
                        'message' => "Jack The Ripper (you): place wretched tokens now!"
                    ])
                    ->notify('page-board.enableHideoutHoverForIDs', [
                        'IDs' => Game_Pathfinder::getWretchedAvailableStartPoints($this->getMurderScenes())
                    ])
                    ->notify('page-board.switchActiveRole', [
                        'role' => Game_Role::JACK
                    ])
                    ->notify('page-board.setActionMenuForWretchedTokens')
                    ->send($socketId);

            } else {
                // police only
                JsonRpc::client()->batch()
                    ->notify('page-board.addLogMessage', [
                        'message' => "Jack The Ripper ({$this->getBoard()->getNicknameByRole(Game_Role::JACK)}): places wretched tokens"
                    ])
                    ->notify('page-board.switchActiveRole', [
                        'role' => Game_Role::JACK
                    ])
                    ->send($socketId);
            }
        }
    }

    /**
     * ACTION : Jack set Wretched Tokens
     * @param $hideoutId
     * @param $tokenType
     * @throws Exception
     */
    public function actionSetWretchedToken($hideoutId, $tokenType)
    {
        $hideoutId = $this->validHideoutId($hideoutId);
        $this->setWretchedToken($hideoutId, $tokenType);

        // jack only
        JsonRpc::client()->batch()
            ->notify('page-board.putWretchedToken', [
                'hideoutId' => $hideoutId,
                'tokenType' => $tokenType
            ])
            ->notify('page-board.disableHideoutHoverById', [
                'hideoutId' => $hideoutId,
            ])
            ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));


        // police only
        JsonRpc::client()->batch()
            ->notify('page-board.putWretchedToken', [
                'hideoutId' => $hideoutId,
                'tokenType' => self::WRETCHED_TOKEN_BLANK
            ])
            ->send($this->getBoard()->getSocketIDsWithoutRole(Game_Role::JACK));

        /**
         * Jack has finished placing Wretched Tokens
         */
        if($this->getWretchedAvailableTokens()[self::WRETCHED_TOKEN_BLANK] == 0
            && $this->getWretchedAvailableTokens()[self::WRETCHED_TOKEN_MARKED] == 0) {

            JsonRpc::client()->batch()
                ->notify('page-board.addLogMessage', [
                    'message' => "Jack The Ripper: The Targets are Identified"
                ])
                ->send($this->getBoard()->getSocketIDs());

            /** Disable Hideout Hover */
            JsonRpc::client()->batch()
                ->notify('page-board.disableAllHideoutHover')
                ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));

            // NEXT STEP
            $this->setActionId(self::ACTION_SET_POLICE_PAWNS);
            return true;
        }
    }


    /**
     * PRE : Chief Inspector allocate Police Pawns
     */
    public function preSetPolicePawns(){
        JsonRpc::client()->batch()
            ->notify('page-board.switchChiefOfInvestigationByRole', [
                'role' => $this->getChiefOfInvestigation()
            ])
            ->notify('page-board.switchActiveRole', [
                'role' => $this->getChiefOfInvestigation()
            ])
            ->notify('page-board.addLogMessage', [
                'message' => "Police Patrolling the Streets"
            ])
            ->send($this->getBoard()->getSocketIDs());


            $batch = JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => "Place 5 Police Pawns and 2 Blank Pawns now!"
            ])
            ->notify('page-board.enableJunctionHoverForIDs', [
                'IDs' => $this->getPolicePawnAllocAbleJunctionIDs()
            ])
                ->notify('page-board.setActionMenuForPolicePawnAllocation');

        if( ! $this->isFirstDay()){
            $batch
                ->notify('page-board.removePolicePawns')
                ->notify('page-board.putPolicePawnPlaceHolders', [
                    'junctionIDs' => $this->getPoliceOfficersJunctionIDs()
                ]);
            }

            $batch->send(
                $this->getBoard()->getSocketIdByRole($this->getChiefOfInvestigation())
            );
    }


    /**
     * ACTION : Chief Inspector allocate Police Pawns
     * @param int $junctionId,
     * @param string $pawnType
     */
    public function actionSetPolicePawn($junctionId, $pawnType){
        $canUseStartPoint = $this->canAllocatePolicePawnStartPoint();
        $junctionId = $this->validJunctionId($junctionId);
        $this->setPolicePawn($junctionId, $pawnType);

        /** Notify chief of investigation */
        $batch = JsonRpc::client()->batch();

        $batch
            ->notify('page-board.putPolicePawn', [
                'junctionId' => $junctionId,
                'pawnType' => $pawnType
            ])
            ->notify('page-board.disableJunctionHover', [
                'junctionId' => $junctionId
            ]);
            
            
        if($canUseStartPoint && !$this->canAllocatePolicePawnStartPoint()){
            $batch
            ->notify('page-board.disableJunctionHoverForIDs', [
                'junctionIDs' => $this->getPoliceUnusedBaseStartPoints()
            ]);
        }

        $batch
            ->send($this->getBoard()->getSocketIdByRole($this->getChiefOfInvestigation()));
        
        /** Notify other players */
        JsonRpc::client()->batch()
            ->notify('page-board.putPolicePawn', [
                'junctionId' => $junctionId,
                'pawnType' => self::POLICE_BLANK_PAWN
            ])->send($this->getBoard()->getSocketIDsWithoutRole($this->getChiefOfInvestigation()));


        /** 
         * No more free pawns left in hand 
         */
        if($this->getPolicePawnAllocAbleJunctionCount() === 0){

            // disable all other junctions
            JsonRpc::client()->batch()
                ->notify('page-board.disableAllJunctionHover')
                ->send($this->getBoard()->getSocketIdByRole($this->getChiefOfInvestigation()));

            // NEXT STEP
                $this->setActionId(self::ACTION_VICTIMS_ARE_CHOSEN);
                return true;
        }
    }

    /**
     * PRE : Reveal Wretched Tokens
     */
    public function preVictimsAreChosen(){
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => "Jack the Ripper: Victims Are Chosen"
            ])
            ->notify('page-board.removeWretchedTokens', [
                'tokenType' => self::WRETCHED_TOKEN_BLANK
            ])
            ->send($this->getBoard()->getSocketIDs());

        /**
         * all wreched tokens are blank for everybody except Jack - therefor we need to place
         * marked tokens rather then removing blanks
         */
        JsonRpc::client()->batch()
            ->notify('page-board.putWomenTokens', [
                'hideoutIDs' => $this->getWomenHideoutIDs()
            ])
            ->send($this->getBoard()->getSocketIDsWithoutRole(Game_Role::JACK));

        // NEXT STEP
        $this->setActionId(self::ACTION_KILL_OR_WAIT);
        return true;
    }

    /**
     * PRE : Jack decides to Kill Or Wait [Blood On the Streets]
     */
    public function preKillOrWait(){
        if($this->getJackMoveLimit() === $this->getJackMoveMaxLimit()){
            $this->storeJackTrackLimit();
            $this->setActionId(self::ACTION_KILL);
            return true;
        }

        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [ 'message' => "Jack the Ripper: Blood on the Streets" ])
            ->notify('page-board.switchActiveRole', [
                'role' => Game_Role::JACK
            ])
            ->send($this->getBoard()->getSocketIDs());

        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [ 'message' => "Would you like to kill or wait? Chose now!" ])
            ->notify('page-board.openKillOrWaitPopup')
            ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));
    }

    /**
     * ACTION : Jack decides to Kill Or Wait [Blood On the Streets]
     * @param $action
     * @return bool
     * @throws Exception
     */
    public function actionKillOrWait($action){
        switch($action){
            case 'kill':

                $this->storeJackTrackLimit();
                $this->setActionId(self::ACTION_KILL);
                return true;
                break;

            case 'wait':

                $this->increaseJackMoveLimit();
                JsonRpc::client()->batch()
                    ->notify('page-board.addLogMessage', [ 'message' => "Jack have decided to wait a little longer" ])
                    ->notify('page-board.setAvailableMovesTo', [
                        'value' => $this->getJackMoveLimit()
                    ])
                    ->send($this->getBoard()->getSocketIDs());

                $this->setActionId(self::ACTION_MOVE_WOMAN_TOKENS);
                return true;
                break;

            default:
                throw new Exception("Invalid action name");
        }
    }


    /**
     * PRE : Chief Of Investigation moves Women [Police Suspense Grows]
     */
    public function preMoveWomen(){
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [ 'message' => "Police Suspense Grows" ])
            ->notify('page-board.switchActiveRole', [
                'role' => $this->getChiefOfInvestigation()
            ])
            ->send($this->getBoard()->getSocketIDs());

        /** Setup Woman Target Locations */
        if( ! $this->hasMovableWomen()){
            $this->setActionId(self::ACTION_REVEAL_POLICE_PAWN);
            return true;
        }

        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [ 'message' => "Move each of Woman Tokens now" ])
            ->notify('page-board.setActionMenuForWomanTokenMove', [
                'hideoutIDs' => $this->getMovableWomen()
            ])
            ->send($this->getBoard()->getSocketIdByRole($this->getChiefOfInvestigation()));
    }

    /**
     * ACTION : Chief Of Investigation moves Women [Police Suspense Grows]
     */
    public function actionMoveWoman($fromHideoutId, $toHideoutId){
        $fromHideoutId = $this->validHideoutId($fromHideoutId);
        $toHideoutId = $this->validHideoutId($toHideoutId);
        
        if( ! $this->isWomanAllowedToMove($fromHideoutId, $toHideoutId)){
            throw new Exception("Can not move this woman token");
        }

        $this->markWomanAsMoved($toHideoutId);
        $this->changeWomanHideoutId($fromHideoutId, $toHideoutId);

        /** Notify everybody */
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [ 'message' => "Woman move from [{$fromHideoutId}] to [{$toHideoutId}]" ])
            ->notify('page-board.moveWomanToken', [
                'fromHideoutId' => $fromHideoutId,
                'toHideoutId' => $toHideoutId
            ])->send($this->getBoard()->getSocketIDs());

        /** Disable click for Chief of Investigation */
        JsonRpc::client()->batch()
            ->notify('page-board.disableHideoutHoverById', [
                'hideoutId' => $fromHideoutId
            ])
            ->send($this->getBoard()->getSocketIdByRole($this->getChiefOfInvestigation()));

        /** has movable women? */
        if( ! $this->hasMovableWomen()){
            $this->clearMovedWoman();
            $this->setActionId(self::ACTION_REVEAL_POLICE_PAWN);

            /** Disable click for Chief of Investigation */
            JsonRpc::client()->batch()
                ->notify('page-board.disableAllHideoutHover')
                ->send($this->getBoard()->getSocketIdByRole($this->getChiefOfInvestigation()));

            return true;
        }
    }

    /**
     * PRE : Reveal Police Pawn [Ready to Kill]
     */
    public function preRevealPolicePawn(){
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [ 'message' => "Jack Ready to Kill" ])
            ->notify('page-board.switchActiveRole', [
                'role' => Game_Role::JACK
            ])
            ->send($this->getBoard()->getSocketIDs());

        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [ 'message' => "Reveal one Police Pawn now" ])
            ->notify('page-board.enableJunctionHoverForIDs', [
                'IDs' => $this->getPolicePawnAllocatedJunctionIDs()
            ])
            ->notify('page-board.setActionMenuForRevealPolicePawn')
            ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));
    }

    /**
     * ACTION : Reveal Police Pawn [Ready to Kill]
     */
    public function actionRevealPolicePawn($junctionId){
        if(! $this->isPolicePawnLocation($junctionId)){
            throw new Exception("No pawn assigned to given junction Id");
        }

        $batch = JsonRpc::client()->batch()
            ->notify('page-board.disableAllJunctionHover')
            ->notify('page-board.removePolicePawn', [
                'junctionId' => $junctionId
            ]);

        // police blank pawn
        if($this->isPoliceBlankPawn($junctionId)) {
            $this->unsetPolicePawnAllocation($junctionId);

            $batch->notify('page-board.addLogMessage', [ 'message' =>
                'Blank Police Officer Token was removed from the board'
            ]);
        }

        // police marked pawn
        else {
            $batch
            ->notify('page-board.putPolicePawn',[
                'junctionId' => $junctionId,
                'pawnType' => $this->getPolicePawnAllocations()[$junctionId]
            ])
                ->notify('page-board.addLogMessage', [ 'message' =>
                    Game_Role::getColorNameForRole($this->getPolicePawnAllocations()[$junctionId])
                    . " found near to locations : "
                    . implode(', ', $this->getPoliceOfficerAvailableActionHideoutIDs($junctionId))
                ]);
        }

        $batch->send($this->getBoard()->getSocketIDs());

        // go to KILL
        $this->setActionId(self::ACTION_KILL_OR_WAIT);
        return true;
    }

    /**
     * Helper function allowing to set active (clickable) hideoits
     */
    protected function setActionMenuForKill(){
        JsonRpc::client()->batch()
            ->notify('page-board.setActionMenuForKill', [
                'hideoutIDs' => array_diff($this->getWomenHideoutIDs(), $this->getMurderScenes()),
            ])
            ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));
    }

    /**
     * PRE : Jack Kills a Women [A Corpse on the Sidewalk]
     */
    public function preKill(){
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [ 'message' => "A Corpse on the Sidewalk" ])
            ->notify('page-board.switchActiveRole', [
                'role' => Game_Role::JACK
            ])
            ->send($this->getBoard()->getSocketIDs());

        if($this->getDay() === 3){
            /** Day 3 */
            JsonRpc::client()->batch()
                ->notify('page-board.addLogMessage', [ 'message' => "Pick and Kill two women now" ])
                ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));
        } else {
            /** Day 1, 2 and 4 */
            JsonRpc::client()->batch()
                ->notify('page-board.addLogMessage', [ 'message' => "Pick and Kill one woman now" ])
                ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));
        }
        
        $this->setActionMenuForKill();
    }
    

    /**
     * ACTION : Jack Kills a Women [A Corpse on the Sidewalk]
     * @param int $hideoutId
     * @param string $method
     */
    public function actionKill($hideoutId, $method)
    {
        $hideoutId = $this->validHideoutId($hideoutId);
        $this->throwExceptionIfCanNotKillAtLocation($hideoutId);
        $this->throwExceptionIfCanNotKillUsingMethod($method);

        // setMurderScene
        $this->setMurderScene($hideoutId);

        // notify everybody
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => "Kill took place at location [{$hideoutId}]"
            ])
            ->notify('page-board.putMurderSceneToken', [
                'hideoutId' => $hideoutId
            ])
            ->send($this->getBoard()->getSocketIDs());

        JsonRpc::client()->batch()
            ->notify('page-board.disableAllHideoutHover')
            ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));


        if ($method === self::METHOD_KILL_DECOY) {
            $this->setDecoyKillMade();
            $this->addJackMoveToTrack($hideoutId, -1);
            $this->storeMurderScene($hideoutId, -1);
        } else {
            $this->addJackMoveToTrack($hideoutId, 0);
            $this->storeMurderScene($hideoutId, 0);
        }

        /** all victims killed */
        if ($this->getMurdersCountForPresentDay() == $this->getMurderLimit()) {
            $this->setActionId(self::ACTION_ALARM_WHISTLES);
            return true;
        }

        $this->setActionMenuForKill();
    }

    /**
     * PRE : Reveal All Police Pawns [Alarm Whistles]
     */
    public function preRevealPolicePawns(){
        echo "ALARM WHSTLES : 1\n";
        $this->setupPoliceOfficerPositions();
        
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => "Alarm Whistles"
            ])
            ->notify('page-board.removeWretchedTokens', [ 
                'tokenType' => self::WRETCHED_TOKEN_MARKED
            ])
            ->notify('page-board.removePolicePawns')
            ->notify('page-board.putPolicePawns', [
                'pawns' => $this->getPoliceOfficersPositions()
            ])
            ->send($this->getBoard()->getSocketIDs());

        $this->setActionId($this->getDay() === self::DOUBLE_KILL_DAY
            ? self::ACTION_BEFORE_HUNTING_THE_MONSTER
            : self::ACTION_BEFORE_ESCAPE_IN_THE_NIGHT);
        return true;
    }

    /**
     * PRE : Escape Begins [Before, Escape In to The Night]
     */
    public function preBeforeEscapeInTheNight(){
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [ 'message' => "Escape In to The Night" ])
            ->notify('page-board.switchActiveRole', [
                'role' => Game_Role::JACK
            ])
            ->send($this->getBoard()->getSocketIDs());
        
        $this->setActionId(self::ACTION_ESCAPE_IN_THE_NIGHT);
        return true;
    }

    /**
     * HELPER : Visualize Jack move for given socketId or socketIDs
     * @param $socketIDs
     */
    protected function visualizeJackMove($socketIDs){
        if( ! $this->getJackLatestMoveMethod()){
            throw new Exception("Move method is empty");
        }

        $batch = JsonRpc::client()->batch();

        /** Place Movement Token on Jack Track */
        $batch
            ->notify('page-board.setCurrentMoveTo', [
                'value' => $this->getJackMoveCount()
            ])
            ->notify('page-board.putMoveToken', [
                'tokenType' => $this->getJackLatestMoveMethod(),
            ]);

            /** Use Jack Special Move */
            if($this->isJackSpecialMove($this->getJackLatestMoveMethod())){
                $batch
                    ->notify('page-board.setAvailableSpecialMoves', [
                        'moveType' => $this->getJackLatestMoveMethod(),
                        'value' => $this->getAvailableJackRemainSpecialMovesFor($this->getJackLatestMoveMethod())
                    ]);
            }

        /** Send to all given socket IDs */
        $batch->send($socketIDs);
    }

    /**
     * PRE : Jack Move [Escape In to The Night]
     */
    public function preEscapeInTheNight(){
        $jackAvailableMoves = $this->getJackAvailableMoves();

        /** If no available moves, Jack is boxed */
        if($jackAvailableMoves === false){
            return $this->endTheGameJackLost();
        }

        JsonRpc::client()->batch()
            ->notify('page-board.setActionMenuForJackMove', [
                'availableMoves' => $jackAvailableMoves
            ])
            ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));
    }

    /**
     * ACTION : Jack Move [Escape In to The Night]
     * @param int | array $hideoutId
     * @param strin $method walk | alley | carriage | reload
     */
    public function actionEscapeInTheNight($hideoutId, $method){

        /** Call preEscapeInTheNight once again */
        if($method === 'reload'){
            return true;
        }

        $this->throwExceptionIfInvalidJackMoveMethod($method);
        $this->throwExceptionIfInvalidJackMove($method, $hideoutId);
        $destinationHideoutId = is_array($hideoutId) ? $hideoutId[1] : $hideoutId;

        /** Carriage Intermediate Point */
        if($method == self::MOVEMENT_CARRIAGE && !is_array($hideoutId)){
            $intermediateHideoutIDs = Game_Pathfinder::getCarriageIntermediate(
                $this->getJackPosition(), $hideoutId
            );

            JsonRpc::client()->batch()
                ->notify('page-board.disableAllHideoutHover')
                ->notify('page-board.setSubActionMenuForJackCarriageMove', [
                    'intermediateHideoutIDs' => $intermediateHideoutIDs,
                    'toHideoutId' => $hideoutId
                ])
                ->notify('page-board.addLogMessage', [
                    'message' => "To use Carriage you need to add one Intermediate Point. "
                                ."Select one of following: " . implode(',', $intermediateHideoutIDs)
                ])
                ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));

            return;
        }

        /** Use Jack Special Move */
        if($this->isJackSpecialMove($method)){
            $this->useJackSpecialMove($method);
        }

        /** Remember recent jack movement method */
        $this->setJackLatestMoveMethod($method);

        /** Store Jacks move */
        $this->storeJackMove($hideoutId, $method);

        /** Add hideout(s) to jack's track */
        $this->addJackMoveToTrack($hideoutId);

        JsonRpc::client()->batch()

            /** Fill Jack's Track */
            ->notify('page-board.addJackTrackElement', [
                'hideoutIDs' => is_array($hideoutId) ? $hideoutId : [$hideoutId]
            ])
            /** Put Jack Marker */
            ->notify('page-board.putJackMarker', [
                'hideoutId' => is_array($hideoutId) ? $hideoutId[1] : $hideoutId
            ])

            /** Disable Jack's movement Menu */
            ->notify('page-board.disableAllHideoutHover')
            ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));

        /** Visualize Jack Movement for Jack */
        $this->visualizeJackMove($this->getBoard()->getSocketIdByRole(Game_Role::JACK));

        /** Jack can do no more moves */
        if($this->canDoNoMoreMoves()) {
            /** Jack has arrived to his hideout using normal (not-special) move */
            if($this->isJackHideout($destinationHideoutId) && ! $this->isJackSpecialMove($method)) {
                return $this->endTheDay();
            }

            /** Any other case */
            $this->endTheGameJackLost();
        }

        /** Jack has reached his hideout (before making the last move) */
        if($this->isJackHideout($destinationHideoutId)){
            /** Not a special move */
            if ( ! $this->isJackSpecialMove($method)) {
                //jack decides to enter hideout or not
                $this->setActionId(self::ACTION_ENTER_HIDEOUT);
                return true;
            }
        }

        /** Visualize Jack Movement for all except Jack */
        $this->visualizeJackMove($this->getBoard()->getSocketIDsWithoutRole(Game_Role::JACK));

        // go to Hunting The Monster
        $this->setActionId(self::ACTION_BEFORE_HUNTING_THE_MONSTER);
        return true;
    }

    /**
     * PRE : Jack decides to enter hideout
     */
    public function preEnterHideout(){
        /** Game was ended due to Jack cheating */
        if( ! $this->isJackHideout($this->getJackPosition())
            || $this->isJackSpecialMove($this->getJackLatestMoveMethod())){
            $this->endTheGameJackLost();
        }

        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => "Enter the hideout or pass through, decide now"
            ])
            ->notify('page-board.switchActiveRole', [
                'role' => Game_Role::JACK
            ])
            ->notify('page-board.openEnterHideoutPopup')
            ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));
    }

    /**
     * ACTION : Jack decides to enter hideout
     * @param string $action
     */
    public function actionEnterHideout($action){
        switch($action){
            case 'enter':

                return $this->endTheDay();
                break;

            case 'pass':

                /** Visualize Jack Movement for all except Jack */
                $this->visualizeJackMove($this->getBoard()->getSocketIDsWithoutRole(Game_Role::JACK));

                $this->setActionId(self::ACTION_BEFORE_HUNTING_THE_MONSTER);
                return true;
                break;

            default:
                throw new Exception("Invalid action name");
        }
    }

    /**
     * PRE : Before Police Officer Move [Hunting The Monster]
     */
    public function preBeforeHuntingTheMonster() {
        $this->setIndexToFirstPoliceOfficerInOrder();

        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => 'Hunting The Monster'
            ])
            ->send($this->getBoard()->getSocketIDs());

        $this->setActionId(self::ACTION_HUNTING_THE_MONSTER);
        return true;
    }

    /**
     * PRE : Police Officer Move [Hunting The Monster]
     */
    public function preHuntingTheMonster() {
        // everybody exept current police officer
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => Game_Role::getColorNameForRole($this->getCurrentPoliceOfficer())
                    . ', waiting for move'
            ])
            ->notify('page-board.switchActiveRole', [
                'role' => $this->getCurrentPoliceOfficer()
            ])
            ->notify('page-board.putPolicePawnMarker', [
                'junctionId' => $this->getPoliceOfficerPositionForRole($this->getCurrentPoliceOfficer())
            ])
            ->send($this->getBoard()->getSocketIDsWithoutRole($this->getCurrentPoliceOfficer()));

        // current police officer
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => Game_Role::getColorNameForRole($this->getCurrentPoliceOfficer())
                    . ', move your pawn now'
            ])
            ->notify('page-board.switchActiveRole', [
                'role' => $this->getCurrentPoliceOfficer()
            ])
            ->notify('page-board.putPolicePawnMarker', [
                'junctionId' => $this->getPoliceOfficerPositionForRole($this->getCurrentPoliceOfficer())
            ])
            ->notify('page-board.setActionMenuForPoliceOfficerMove', [
                'junctionId' => $this->getPoliceOfficerPositionForRole($this->getCurrentPoliceOfficer()),
                'junctionIDs' => $this->getPoliceOfficerAvailableMoves($this->getCurrentPoliceOfficer())
            ])
            ->send($this->getBoard()->getSocketIdByRole($this->getCurrentPoliceOfficer()));
    }

    /**
     * ACTION : Police Officer Move [Hunting The Monster]
     * @param int $junctionId where current police officer want to move.
     * If junction id is same as current police officer position
     * then police officer will stay where he are.
     * @throws Exception
     * @return boolean
     */
    public function actionHuntingTheMonster($junctionId) {
        $junctionId = $this->validJunctionId($junctionId);

        if(
            $junctionId !== $this->getPoliceOfficerPositionForRole($this->getCurrentPoliceOfficer())
            && ! in_array($junctionId, $this->getPoliceOfficerAvailableMoves($this->getCurrentPoliceOfficer()))
        ){
            throw new Exception("Invalid move, junctionId is expected to be legal move or current police officer position");
        }

        // Disable Junction hover
        JsonRpc::client()->batch()
            ->notify('page-board.disableAllJunctionHover')
            ->send($this->getBoard()->getSocketIdByRole($this->getCurrentPoliceOfficer()));

        // Move Pawn on board
        JsonRpc::client()->batch()
            ->notify('page-board.removePolicePawn', [
                'junctionId' => $this->getPoliceOfficerPositionForRole($this->getCurrentPoliceOfficer())
            ])
            ->notify('page-board.putPolicePawn', [
                'junctionId' => $junctionId,
                'pawnType' => $this->getCurrentPoliceOfficer()
            ])
            ->send($this->getBoard()->getSocketIDs());

        $this->setPoliceOfficerPosition($junctionId, $this->getCurrentPoliceOfficer());
        if($this->isIndexSetToLastPoliceOfficerInOrder()){
            $this->setActionId(self::ACTION_BEFORE_CLUES_AND_SUSPICION);
            
            /** Store Police Positions */
            $this->storePolicePositions();
            
        } else {
            $this->setIndexToNextPoliceOfficerInOrder();
        }

        return true;
    }

    /**
     * PRE : Before Police Officer Action [Clues And Suspicion]
     */
    public function preBeforeCluesAndSuspicion() {
        $this->setIndexToFirstPoliceOfficerInOrder();

        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => 'Clues and Suspicion'
            ])
            ->send($this->getBoard()->getSocketIDs());

        $this->setActionId(self::ACTION_CLUES_AND_SUSPICION);
        return true;
    }

    const POLICE_ACTION_ARREST = 'arrest';
    const POLICE_ACTION_SEARCH = 'search';

    /**
     * PRE : Police Officer Action [Clues And Suspicion]
     */
    public function preCluesAndSuspicion(){
        /** Reset Searched Hideouts for current Police Officer */
        $this->resetSearchedHideouts();

        // everybody exept current police officer
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => Game_Role::getColorNameForRole($this->getCurrentPoliceOfficer())
                    . ', waiting for action'
            ])
            ->notify('page-board.putPolicePawnMarker', [
                'junctionId' => $this->getPoliceOfficerPositionForRole($this->getCurrentPoliceOfficer())
            ])
            ->notify('page-board.switchActiveRole', [
                'role' => $this->getCurrentPoliceOfficer()
            ])
            ->send($this->getBoard()->getSocketIDsWithoutRole($this->getCurrentPoliceOfficer()));

        // current police officer
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => Game_Role::getColorNameForRole($this->getCurrentPoliceOfficer())
                    . ', search for clues or make arrest attempt now'
            ])
            ->notify('page-board.putPolicePawnMarker', [
                'junctionId' => $this->getPoliceOfficerPositionForRole($this->getCurrentPoliceOfficer())
            ])
            ->notify('page-board.switchActiveRole', [
                'role' => $this->getCurrentPoliceOfficer()
            ])
            ->notify('page-board.setActionMenuForPoliceOfficerActions', [
                'hideoutActions' => $this->getPoliceOfficerActionMenuData(),
                'junctionId' => $this->getPoliceOfficerPositionForRole($this->getCurrentPoliceOfficer()),
                'junctionAction' => 'skip'
            ])
            ->send($this->getBoard()->getSocketIdByRole($this->getCurrentPoliceOfficer()));
    }
    
    /**
     * Called to complete action for Clues And Suspicion
     * @return bool
     * @throws Exception
     */
    protected function doneCluesAndSuspicion(){
        // Disable Hideout hover
        JsonRpc::client()->batch()
            ->notify('page-board.disableAllHideoutHover')
            ->notify('page-board.disableAllJunctionHover')
            ->send($this->getBoard()->getSocketIdByRole($this->getCurrentPoliceOfficer()));

        if($this->isIndexSetToLastPoliceOfficerInOrder()){
            $this->setActionId(self::ACTION_BEFORE_ESCAPE_IN_THE_NIGHT);
        } else {
            $this->setIndexToNextPoliceOfficerInOrder();
        }

        /** Remove Police Pawn Marker */
        JsonRpc::client()->batch()
            ->notify('page-board.putPolicePawnMarker')
            ->send($this->getBoard()->getSocketIDs());

        return true;
    }

    /**
     * ACTION : Police Officer Action [Clues And Suspicion]
     * @param int $hideoutId
     * @param string $action 'arrest' | 'search' | 'skip'
     * @throws Exception
     * @return boolean
     */
    public function actionCluesAndSuspicion($hideoutId, $action){

        if($action == 'skip'){
            JsonRpc::client()->batch()
                ->notify('page-board.addLogMessage', [
                    'message' => Game_Role::getColorNameForRole($this->getCurrentPoliceOfficer())
                    .' has skipped his turn'
                ])
                ->send($this->getBoard()->getSocketIDs());

            return $this->doneCluesAndSuspicion();
         }

        /** ARREST or SEARCH (valid hideoutId required)*/
        $hideoutId = $this->validHideoutId($hideoutId);
        switch($action){
            case self::POLICE_ACTION_ARREST :
                // STORE
                $this->storePoliceArrestAction($hideoutId);

                if($this->hasSearchedHideouts()){
                    throw new Exception('Arrest can not follow Search action !');
                }

                /** Jack has been discovered */
                if($this->isJackPosition($hideoutId)){
                    $this->endTheGameJackLost();
                    return;
                }

                JsonRpc::client()->batch()
                    ->notify('page-board.addLogMessage', [
                        'message' => 'Arrest attempt on hideout ['. $hideoutId .']. Jack is not there.'
                    ])
                    ->notify('page-board.policeActionAnimation', [
                        'hideoutId' => $hideoutId,
                        'action' => self::POLICE_ACTION_ARREST
                    ])
                    ->send($this->getBoard()->getSocketIDs());


                return $this->doneCluesAndSuspicion();
                break;

            case self::POLICE_ACTION_SEARCH :
                // STORE
                $this->storePoliceSearchAction($hideoutId);

                /** Jack has been discovered */
                if($this->wasJackPosition($hideoutId)){

                    $this->setClue($hideoutId);
                    $this->storeClue($hideoutId);

                    JsonRpc::client()->batch()
                        ->notify('page-board.addLogMessage', [
                            'message' => 'Clue found at hideout ['. $hideoutId .']'
                        ])
                        ->notify('page-board.putClueToken', [
                            'hideoutId' => $hideoutId
                        ])
                        ->send($this->getBoard()->getSocketIDs());

                    return $this->doneCluesAndSuspicion();
                } else {

                    JsonRpc::client()->batch()
                        ->notify('page-board.addLogMessage', [
                            'message' => 'Search on Hideout ['. $hideoutId .']. Jack was not there.'
                        ])
                        ->notify('page-board.policeActionAnimation', [
                            'hideoutId' => $hideoutId,
                            'action' => self::POLICE_ACTION_SEARCH
                        ])
                        ->send($this->getBoard()->getSocketIDs());
                }

                /** Add new searched hideout */
                $this->addSearchedHideout($hideoutId);

                /** First search action... */
                if(count($this->getSearchedHideouts()) == 1){

                    // Disable All Hideout hover (unbind prev action menu)
                    // and create new menu without 'arrest' action

                    JsonRpc::client()->batch()
                        ->notify('page-board.disableAllHideoutHover')
                        ->notify('page-board.setActionMenuForPoliceOfficerActions', [
                            'hideoutActions' => $this->getPoliceOfficerActionMenuData(),
                            'junctionId' => $this->getPoliceOfficerPositionForRole($this->getCurrentPoliceOfficer()),
                            'junctionAction' => 'skip'
                        ])
                        ->send($this->getBoard()->getSocketIdByRole($this->getCurrentPoliceOfficer()));
                } else {

                    /** Following search action... */
                    // Disable Hideout hover by hideoutId
                    JsonRpc::client()->batch()
                        ->notify('page-board.disableHideoutHoverById', [
                            'hideoutId' => $hideoutId
                        ])
                        ->send($this->getBoard()->getSocketIdByRole($this->getCurrentPoliceOfficer()));
                }

                /** all hideouts searched */
                if($this->areAllHideoutsSearched()){
                    return $this->doneCluesAndSuspicion();
                }

                return;
                break;

            default:
                throw new Exception("Invalid Action name");
        }
    }


    public function actionEmpty(){
        echo "\n ->> EMPTY ACTION\n";
    }

    public function preEmpty(){
        echo "\n ->> EMPTY PRE\n";
    }


    /**
     * Return-Call this at the end of the day.
     * @return boolean;
     */
    protected function endTheDay(){
        /** last day ends, jack has won */
        if($this->isLastDay()){
            return $this->endTheGameJackWin();
        }

        /** any other day... */

        /** Notify Jack */
        JsonRpc::client()->batch()
            ->notify('page-board.removeJackMarker')
            ->notify('page-board.addLogMessage', [
                'message' => __('Day :day has ended', [':day' => $this->getDay()])
            ])
            ->notify('popup.note', [
                'message' => __('Well done. You have made it to your hideout. Day :day has ended.', [":day" => $this->getDay()])
            ])
            ->notify('popup.note', [
                'message' => __('Waiting for police officers readiness')
            ])
            ->send($this->getBoard()->getSocketIdByRole(Game_Role::JACK));


        /** Notify Police */
        JsonRpc::client()->batch()
            ->notify('page-board.addLogMessage', [
                'message' => __('Day :day has ended', [':day' => $this->getDay()])
            ])
            ->notify('popup.alert', [
                'payload' => __('Jack has made to his hideout. Day :day has ended. ' .
                    'Take your time to analyze Jack\'s moves. Confirm your readiness in order to proceed to the next day.',[ ':day' => $this->getDay()])
            ])
            ->send($this->getBoard()->getSocketIDsWithoutRole(Game_Role::JACK));

        $this->setNextDay();
        $this->setActionId(self::ACTION_READINESS);

        return true;
    }

    /**
     * Save Stats, return to Dash Board
     * and show summary for Jack Defeat
     */
    protected function endTheGameJackLost(){
        $storageDump = $this->getStorage();
        $storageDump['titleText'] = "Police've Win";
        JsonRpc::client()->notify($this->getBoard()->getSocketIDs(), 'page-board.popupGameOverview', [
            'data' => $storageDump
        ]);

        foreach($this->getBoard()->getSocketIDsWithoutRole(Game_Role::JACK) as $socketId){
            $this->gameEndedAsPoliceOfficerVictory($socketId);
        }

        $this->gameEndedAsJackDefeat($this->getBoard()->getSocketIdByRole(Game_Role::JACK));
        Service_Board::endGame();
    }
    
    /**
     * Save Stats, return to Dash Board
     * and show summary for Jack Victory
     */
    protected function endTheGameJackWin(){
        $storageDump = $this->getStorage();
        $storageDump['titleText'] = "Jack've Win";
        JsonRpc::client()->notify($this->getBoard()->getSocketIDs(), 'page-board.popupGameOverview', [
            'data' => $storageDump
        ]);


        foreach($this->getBoard()->getSocketIDsWithoutRole(Game_Role::JACK) as $socketId){
            $this->gameEndedAsPoliceOfficerDefeat($socketId);
        }

        $this->gameEndedAsJackVictory($this->getBoard()->getSocketIdByRole(Game_Role::JACK));
        Service_Board::endGame();
    }
}