<?php

class Game_Machine_MiddleWare extends Game_Machine_Abstraction
{

    /*************************************************
     * JACK HIDEOUT
     *************************************************/

    /**
     * @var int Jack Hideout
     */
    protected $jackHideout;

    protected function getJackHideout(){
        return $this->jackHideout;
    }

    /**
     * Set Jack Hideout ID
     * @param int $jackHideout
     * @return boolean
     * @throws Exception on failure
     */
    protected function setJackHideout($jackHideout)
    {
        /** Is already set? */
        if (!is_null($this->jackHideout)) {
            throw new Exception("Jack Hideout already set");
        }

        $this->jackHideout = $jackHideout;
        return true;
    }

    /**
     * Is Jack hideoutId pointing to acctual Jack Hideout ??
     * @param int $hideoutId
     * @return bool
     */
    protected function isJackHideout($hideoutId){
        return $hideoutId == $this->getJackHideout();
    }
    /*************************************************
     * CLUES
     *************************************************/

    /** @var array hideout IDs for clues */
    protected $clues = [];

    protected function getClues(){
        return $this->clues;
    }

    protected function setClue($hideoutId){
        $this->clues[] = $hideoutId;
    }

    protected function resetClues(){
        $this->clues = [];
    }
    /*************************************************
     * MURDER SCENES
     *************************************************/

    /**
     * @var array as
     * [hideoutId_1, hideoutId_2, ... ]
     */
    protected $murderScenes = [];

    /**
     * Get Murder Scenes as list of hideouts.
     * @return array
     */
    protected function getMurderScenes() {
        return $this->murderScenes;
    }

    /**
     * Set Murder Scene
     * @param int $hideoutId
     * @return boolean
     * @thorws Exception
     */
    protected function setMurderScene($hideoutId){
        if(!in_array($hideoutId, $this->getWomenHideoutIDs())){
            throw new Exception("Can not set Murder token there, no woman");
        }

        if(in_array($hideoutId, $this->getMurderScenes())){
            throw new Exception("Can not set Murder token there, already set");
        }

        $this->murderScenes[] = $hideoutId;
    }

    /*************************************************
     * JACK MOVES
     *************************************************/
    protected $jackMoveLimit;

    protected function getJackMoveBaseLimit(){
        return $this->getDay() == self::DOUBLE_KILL_DAY ? 14 : 15;
    }

    protected function getJackMoveMaxLimit(){
        return $this->getDay() == self::DOUBLE_KILL_DAY ? 19 : 20;
    }

    /** @var array Track of jack moves. */
    protected $jackMoveTrack = [];

    /**
     * Get Jack Move Count
     * @return int
     */
    protected function getJackMoveCount(){
        return count($this->getJackMoveTrack());
    }

    /**
     * Get Jack Position
     * @return int|mixed
     */
    protected function getJackPosition(){
        $track = $this->getJackMoveTrack();
        return end($track);
    }

    /**
     * Returns TRUE if $hideout is Jack latest position
     * @param int $hideoutId
     * @return boolean;
     */
    protected function isJackPosition($hideoutId){
        return $this->getJackPosition() == $hideoutId;
    }

    /**
     * Returns TRUE if $hideout is part of Jack Track
     * @param int $hideoutId
     * @return boolean;
     */
    protected function wasJackPosition($hideoutId){
        return in_array($hideoutId, $this->getJackMoveTrack());
    }

    /**
     * Add Jack Move to Track
     * @param int | array $hideoutId
     * @param int $index (optional, only if hideoutId is given by integer)
     * @throws Exception
     */
    protected function addJackMoveToTrack($hideoutId, $index = null){
        /** Multiple elements (carriage) */
        if(is_array($hideoutId)){
            $this->jackMoveTrack = array_merge($this->jackMoveTrack, $hideoutId);
            return true;
        }

        /** fixed index */
        if($index !== null){
            if(array_key_Exists($index, $this->getJackMoveTrack())){
                throw new Exception("Can not overwrite given index ({$index})");
            }
            $this->jackMoveTrack[$index] = $hideoutId;
            ksort($this->jackMoveTrack);
            return true;
        }

        /** One element (any other move) */
        $this->jackMoveTrack[] = $hideoutId;
        return true;
    }

    /**
     * Returns Jack Move Track
     * @return array
     */
    protected function getJackMoveTrack(){
        return $this->jackMoveTrack;
    }

    /**
     * Returns TRUE if Jack can do one move or more
     * within current limit.
     * @returns boolean
     */
    protected function canDoOneMoveOrMore(){
        //  !! jack murder scene is also part of the track
        return count($this->getJackMoveTrack()) <= $this->getJackMoveLimit();
    }

    /**
     * Returns TRUE if Jack can do two moves or more
     * within current limit.
     * @returns boolean
     */
    protected function canDoTwoMovesOrMore(){
        //  !! jack murder scene is also part of the track
        return count($this->getJackMoveTrack()) + 1 <= $this->getJackMoveLimit();
    }

    /**
     * Returns TRUE if Jack move limit has been reached
     * @returns boolean
     */
    protected function canDoNoMoreMoves(){
        //  !! jack murder scene is also part of the track
        return count($this->getJackMoveTrack()) - 1 === $this->getJackMoveLimit();
    }

    /**
     * Add one extra move to jack track
     * @throws Exception
     */
    protected function increaseJackMoveLimit(){
        if($this->canIncreaseJackMoveLimit()){
            throw new Exception("Jack moves limit reached");
        }
        $this->jackMoveLimit++;
    }

    /**
     * Returns TRUE if jack track can be exceeded
     * @return bool
     */
    protected function canIncreaseJackMoveLimit(){
        return $this->jackMoveLimit >= $this->getJackMoveMaxLimit();
    }

    /**
     * Returns current jack track move limit
     * @return int
     */
    protected function getJackMoveLimit(){
        return $this->jackMoveLimit;
    }

    /**
     * Reset Jack move limit
     */
    protected function resetJackTrack(){
        $this->jackMoveLimit = $this->getJackMoveBaseLimit();
        $this->jackMoveTrack = [];
    }

    /**
     * Get Jack Available Moves for 'carriage', 'alley' and 'walk'
     * @return array | false if jack is boxed, empty array if jack position is not defined
     */
    protected function getJackAvailableMoves(){
        if(!$this->getJackPosition()){
            return [];
        }

        $availableMoves =  Game_Pathfinder::getJackAvailableMoves(
            $this->getJackPosition(), $this->getPoliceOfficersJunctionIDs()
        );

        if( ! $this->isJackSpecialMoveAvailable(self::MOVEMENT_ALLEY)){
            unset($availableMoves[self::MOVEMENT_ALLEY]);
        }

        if( ! $this->isJackSpecialMoveAvailable(self::MOVEMENT_CARRIAGE)){
            unset($availableMoves[self::MOVEMENT_CARRIAGE]);
        }

        /** Return $availableMoves if any moves possible */
        foreach($availableMoves as $move){
            if(!empty($move)){
                return $availableMoves;
            }
        }

        /** Return false if Jack is boxed */
        return false;
    }

    /**
     * Is Jack Move Method valid?
     * @param string $method
     * @throws Exception
     */
    protected function throwExceptionIfInvalidJackMoveMethod($method){
        if(!in_array($method, [self::MOVEMENT_WALK, self::MOVEMENT_ALLEY, self::MOVEMENT_CARRIAGE])){
            throw new Exception("Invalid Jack Move Method");
        }
    }
    
    /**
     * Can Jack Move There?
     * @param string $method alley | carriage | walk
     * @param int $hideoutId
     *
     * @throws Exception
     */
    protected function throwExceptionIfInvalidJackMove($method, $hideoutId){
        if($method == self::MOVEMENT_CARRIAGE){
            if( ! $this->canDoTwoMovesOrMore()){
                throw new Exception("You require at least two moves to use carriage");
            }
        } else {
            if($this->canDoNoMoreMoves()){
                throw new Exception("You have no more moves left");
            }
        }

        if($this->isJackSpecialMove($method) && ! $this->isJackSpecialMoveAvailable($method)){
            throw new Exception("Can not use special move [{$method}], none left.");
        }

        /**
         * Carriage complete move
         */
        if($method === self::MOVEMENT_CARRIAGE && is_array($hideoutId)){
            if(count($hideoutId) !== 2){
                throw new Exception("Invalid data format");
            }

            $this->validHideoutId($hideoutId[0]);
            $this->validHideoutId($hideoutId[1]);

            if( ! in_array($hideoutId[1], $this->getJackAvailableMoves()[self::MOVEMENT_CARRIAGE])){
                throw new Exception("Invalid Jack Move Destination");
            }

            $intermediateHideoutIDs = Game_Pathfinder::getCarriageIntermediate(
                $this->getJackPosition(), $hideoutId[1]
            );

            if( ! in_array($hideoutId[0], $intermediateHideoutIDs)){
                throw new Exception("Invalid Carriage Intermediate Point");
            }

            return true;
        }

        /** Anty other case */
        $this->validHideoutId($hideoutId);
        if( ! in_array($hideoutId, $this->getJackAvailableMoves()[$method])){
            throw new Exception("Invalid Jack Move Destination");
        }
    }


    /*************************************************
     * JACK KILLS
     *************************************************/

    const METHOD_KILL = 'kill';
    const METHOD_KILL_DECOY = 'decoy';

    /**
     * Decoy Kill Flag
     * @var bool
     */
    protected $decoyKillMade = false;

    /**
     * Set Decoy Kill Made flag to TRUE
     */
    protected function setDecoyKillMade(){
        $this->decoyKillMade = true;
    }

    /**
     * Check Decoy Kill flag
     * @return bool
     */
    protected function isDecoyKillMade(){
        return $this->decoyKillMade;
    }

    /**
     * Tell how many murders was committed at the present day
     * @return int 0 | 1 | 2
     */
    protected function getMurdersCountForPresentDay(){
        $murderCount = count($this->getMurderScenes());

        switch($this->getDay()){
            case 1:
                break;
            case 2:
                $murderCount -= 1;
                break;
            case 3:
                $murderCount -= 2;
                break;
            case 4:
                $murderCount -= 4;
                break;
        }

        return $murderCount;
    }

    /**
     * Throws exception if:
     * -> location does not hold wretched token
     * -> location already is murder scene
     * -> (including decoy murder)
     *
     * Nights 1, 2, 4
     * -> murder already took place at this night
     *
     * Night 3
     * -> two murders already took place at this night
     *
     * @param int $hideoutId
     * @throws Exception
     */
    protected function throwExceptionIfCanNotKillAtLocation($hideoutId){
        if($this->getMurdersCountForPresentDay() >= $this->getMurderLimit()){
            throw new Exception("Murder limit for present day already reached");
        }

        if(!in_array($hideoutId, $this->getWomenHideoutIDs())){
            throw new Exception("There is no woman to kill at hideout [{$hideoutId}]");
        }

        if(in_array($hideoutId, $this->getMurderScenes())){
            throw new Exception("Murder already took place at hideout [{$hideoutId}]");
        }
    }


    /**
     * Get list of allowed murder methods allowed at certain point of the day.
     * If none available, returns empty array.
     * @return array
     * @throws Exception
     */
    public function getAllowedKillMethods(){
        switch($this->getDay()) {
            case 1:
            case 2:
            case 4:
                if ($this->getMurdersCountForPresentDay() === 0) {
                    return [self::METHOD_KILL];
                }
                return [];
                break;
            case 3:
                if ($this->getMurdersCountForPresentDay() === 0) {
                    return [self::METHOD_KILL, self::METHOD_KILL_DECOY];
                }

                if ($this->getMurdersCountForPresentDay() === 1) {
                    return [$this->isDecoyKillMade() ? self::METHOD_KILL : self::METHOD_KILL_DECOY];
                }

                return [];
        }
    }

    /**
     * Returns map as method => description,
     * of available methods for given hideoutId.
     * @param int $hideoutId
     * @return array
     */
    public function getKillWretchedMenuData($hideoutId){
        $this->validHideoutId($hideoutId);
        $this->throwExceptionIfCanNotKillAtLocation($hideoutId);

        $translations = [
            self::METHOD_KILL => 'Kill',
            self::METHOD_KILL_DECOY => 'Kill and Escape (decoy)'
        ];

        $allowedKillMethods = $this->getAllowedKillMethods();

        return array_intersect_key($translations, array_flip($allowedKillMethods));
    }

    /**
     * Throws exception if method not allowed at the certain point of the day.
     * @param string $method
     * @throws Exception
     */
    protected function throwExceptionIfCanNotKillUsingMethod($method){
        if(! in_array($method, $this->getAllowedKillMethods())){
            throw new Exception("Invalid kill method");
        }
    }


    /*************************************************
     * JACK SPECIAL MOVES
     *************************************************/

    /** @var array */
    protected $availableJackSpecialMoves;

    /**
     * Allow to set Jack Special Moves for current round
     * @param array $specialMoves
     */
    protected function setAvailableJackSpecialMoves(array $specialMoves){
        $this->availableJackSpecialMoves = $specialMoves;
    }

    /**
     * Return Jack Special moves left
     * @return array
     */
    protected function getAvailableJackSpecialMoves(){
        return $this->availableJackSpecialMoves;
    }

    /**
     * Return Jack Special moves left for method
     * @param string $method
     * @return int
     */
    protected function getAvailableJackRemainSpecialMovesFor($method){
        return $this->availableJackSpecialMoves[$method];
    }

    /**
     * Can i use this special move?
     * @param string $method
     * @return bool
     */
    protected function isJackSpecialMoveAvailable($method){
        return $this->getAvailableJackRemainSpecialMovesFor($method) > 0;
    }


    /**
     * Is Jack special move?
     * @param string $method
     * @return bool
     */
    protected function isJackSpecialMove($method){
        return in_array($method, [self::MOVEMENT_CARRIAGE, self::MOVEMENT_ALLEY]);
    }


    /**
     * Use special move
     * @param string $method
     */
    protected function useJackSpecialMove($method){
        $this->availableJackSpecialMoves[$method] --;
    }

    /*************************************************
     * WRETCHED TOKENS
     *************************************************/

    /**
     * This array is used when setting Wretched Tokens and until Wretched is murdered.
     * @var array
     */
    protected $wretchedTokensSet = [
        self::WRETCHED_TOKEN_BLANK => [],
        self::WRETCHED_TOKEN_MARKED => []
    ];

    /**
     * Call to reset Wretched Tokens
     */
    protected function clearWretchedTokenAllocation(){
        $this->wretchedTokensSet = [
            self::WRETCHED_TOKEN_BLANK => [],
            self::WRETCHED_TOKEN_MARKED => []
        ];
    }

    /**
     * Returns info of how many wretched tokens can still be assigned.
     * @return array
     */
    public function getWretchedAvailableTokens()
    {
        $blankLimit = $this->getWretchedTokenLimit()[self::WRETCHED_TOKEN_BLANK];
        $markedLimit = $this->getWretchedTokenLimit()[self::WRETCHED_TOKEN_MARKED];

        return [
            self::WRETCHED_TOKEN_BLANK => $blankLimit - count($this->wretchedTokensSet[self::WRETCHED_TOKEN_BLANK]),
            self::WRETCHED_TOKEN_MARKED => $markedLimit - count($this->wretchedTokensSet[self::WRETCHED_TOKEN_MARKED])
        ];
    }

    /**
     * Returns info required to generate menu. Each element has keys as 'description', 'action' and 'id'
     * @param int $hideoutId
     * @return array
     */
    public function getWretchedPutTokenMenuData($hideoutId)
    {
        if (!in_array($hideoutId, Game_Pathfinder::getWretchedAvailableStartPoints($this->getMurderScenes()))) {
            throw new Exception("Can not place wretched token on Hideout [{$hideoutId}]");
        }

        $output = [];
        foreach ($this->getWretchedAvailableTokens() as $tokenType => $quantity) {
            if ($quantity == 0) {
                continue;
            }
            switch ($tokenType) {
                case self::WRETCHED_TOKEN_BLANK:
                    $output[] = [
                        'action' => self::WRETCHED_TOKEN_BLANK,
                        'description' => 'Put Blank Token',
                        'id' => $hideoutId
                    ];
                    break;

                case self::WRETCHED_TOKEN_MARKED:
                    $output[] = [
                        'action' => self::WRETCHED_TOKEN_MARKED,
                        'description' => 'Put Marked Token',
                        'id' => $hideoutId
                    ];
                    break;
            }
        }

        return $output;
    }

    /**
     * Sets Wretched token
     * @param int $hideoutId
     * @param string $tokenType type of token (black or marked)
     * @throws Exception on failure
     */
    protected function setWretchedToken($hideoutId, $tokenType)
    {
        if (!in_array($tokenType, [self::WRETCHED_TOKEN_BLANK, self::WRETCHED_TOKEN_MARKED])) {
            throw new Exception("Invalid wretched token type");
        }

        if ($this->getWretchedAvailableTokens()[$tokenType] <= 0) {
            throw new Exception("None wretched tokens of type [$tokenType] left");
        }

        if (in_array($hideoutId, $this->wretchedTokensSet[$tokenType])) {
            throw new Exception("Token already placed on hideout [{$hideoutId}]");
        }

        if (!in_array($hideoutId, Game_Pathfinder::getWretchedAvailableStartPoints($this->getMurderScenes()))) {
            throw new Exception("Can not place wretched token on Hideout [{$hideoutId}]");
        }

        $this->wretchedTokensSet[$tokenType][] = $hideoutId;
    }


    /**
     * Return array data (list) of whereever Woman (marked wretched tokens) are placed.
     * @return mixed
     */
    protected function getWomenHideoutIDs(){
        return $this->wretchedTokensSet[self::WRETCHED_TOKEN_MARKED];
    }

    /**
     * Change Woman Position
     * @param int $fromHideoutId
     * @param int $toHideoutId
     */
    protected function changeWomanHideoutId($fromHideoutId, $toHideoutId){
        foreach($this->wretchedTokensSet[self::WRETCHED_TOKEN_MARKED] as &$hideoutId){
            if($hideoutId == $fromHideoutId){
                $hideoutId = $toHideoutId;
            }
        }
    }
    /*************************************************
     * POLICE OFFICER POSITIONS
     *************************************************/

    /**
     * @var array as role => junctionId
     * Police Officer positions. Usually those values will be exactly the same as corresponding
     * Police Officer Pawn positions with one exception. At the moment when Chief Of Investigation
     * is re-allocating police officer positions values of this array are the reference for that operation.
     */
    protected $policeOfficersPositions = [
        Game_Role::BLUE_POLICE_OFFICER => null,
        Game_Role::BROWN_POLICE_OFFICER => null,
        Game_Role::GREEN_POLICE_OFFICER => null,
        Game_Role::RED_POLICE_OFFICER => null,
        Game_Role::YELLOW_POLICE_OFFICER => null,
    ];

    /**
     * Remove Police Officer Positions
     */
    protected function clearPoliceOfficersPositions(){
      $this->policeOfficersPositions = [
          Game_Role::BLUE_POLICE_OFFICER => null,
          Game_Role::BROWN_POLICE_OFFICER => null,
          Game_Role::GREEN_POLICE_OFFICER => null,
          Game_Role::RED_POLICE_OFFICER => null,
          Game_Role::YELLOW_POLICE_OFFICER => null,
      ];
    }

    /**
     * @return array of police officer positions as role => junctionId
     */
    protected function getPoliceOfficersPositions() {
        return $this->policeOfficersPositions;
    }

    /**
     * @param string $role
     * @returns array police officer positions all, except for one given by $role
     */
    protected function getPoliceOfficersPositionsWithoutRole($role){
        return array_diff_key($this->getPoliceOfficersPositions(), [
            $role => $this->getPoliceOfficerPositionForRole($role)
        ]);
    }

    /**
     * @return array of police officer positions as list [x , x , x ...]
     */
    protected function getPoliceOfficersJunctionIDs(){
        $assigned = array_filter($this->getPoliceOfficersPositions(), function($element){
            return ! is_null($element);
        });

        return array_values($assigned);
    }

    /**
     * @return array of police officer positions as list [x , x , x ...]
     * @param string $role to ommit
     * @return array
     */
    protected function getPoliceOfficersJunctionIDsWithoutRole($role){
        $assigned = array_filter($this->getPoliceOfficersPositionsWithoutRole($role), function($element){
            return ! is_null($element);
        });

        return array_values($assigned);
    }

    /**
     * Get Police Officer Position for given role
     * @param string $role
     * @return int | null
     * @throws Exception
     */
    public function getPoliceOfficerPositionForRole($role) {
        if (!array_key_exists($role, $this->getPoliceOfficersPositions())) {
            throw new Exception("Invalid Role Type");
        }

        return $this->getPoliceOfficersPositions()[$role];
    }

    /**
     * Set police officer position for given role and junctionId
     * @param int $junctionId
     * @param string $role
     * @throws Exception
     * @return null
     */
    protected function setPoliceOfficerPosition($junctionId, $role) {
        if ( ! array_key_exists($role, $this->getPoliceOfficersPositions())) {
            throw new Exception("Invalid Police Officer type");
        }


        if (in_array($junctionId, $this->getPoliceOfficersPositionsWithoutRole($role))) {
            throw new Exception("Junction [{$junctionId}] occupied");
        }

        $this->policeOfficersPositions[$role] = $junctionId;
    }

    /**
     * Copy data from Police Pawn Allocations to Police Officer Positions
     */
    protected function setupPoliceOfficerPositions(){
        $allocations = array_flip($this->getPolicePawnAllocations());
        $this->clearPolicePawnAllocations();
        $this->clearPoliceOfficersPositions();

        foreach(array_keys($this->policeOfficersPositions) as $role){
            $this->setPoliceOfficerPosition($allocations[$role], $role);
        }
    }

    /**
     * Proxy for Game_Pathfinder.
     * Get all Hideout IDs where given Policeman can make Action
     * @param $junctionId a place where PoliceOfficer is standing
     * @return array
     */
    protected function getPoliceOfficerAvailableActionHideoutIDs($junctionId){
        return Game_Pathfinder::getPoliceOfficerAvailableActionHideoutIDs($junctionId);
    }

    /**
     * Proxy for Game_Pathfinder::getPolicemanAvailableMoves()
     * Get all possible moves of given Police Officer
     * @param string $role
     * @return array
     */
    protected function getPoliceOfficerAvailableMoves($role){
        return Game_Pathfinder::getPoliceOfficerAvailableMoves(
            $this->getPoliceOfficerPositionForRole($role),
            $this->getPoliceOfficersJunctionIDs()
        );
    }

    /**
     * List of HideoutIDs current police officer can interact with
     * @return array
     * @throws Exception
     */
    protected function getCurrentPoliceOfficerActionHideoutIDs(){
        return $this->getPoliceOfficerAvailableActionHideoutIDs(
            $this->getPoliceOfficerPositionForRole($this->getCurrentPoliceOfficer())
        );
    }

    /**
     * List of HideoutIDs current police officer can search in
     * @return array
     */
    protected function getCurrentPoliceOfficerSearchAbleHideoutIDs(){
        return array_diff($this->getCurrentPoliceOfficerActionHideoutIDs(),
            $this->getSearchedHideouts(), $this->getMurderScenes(), $this->getClues());
    }

    /*************************************************
     * POLICE PAWN POSITIONS
     *************************************************/

    const MAX_POLICE_BLANK_PAWNS = 2;
    const POLICE_REALLOCABLE_PAWNS = 2;
    const POLICE_BLANK_PAWN = 'blank';

    /**
     * @var array as junctionId => pawn type.
     * Where 'paawn type' is Police Officer Role or 'blank';
     * Array may hold up to two junctionId related to 'blank' type.
     */
    protected $policePawnAllocations = [];

    /**
     * Get Police Officers Pawns positions as $junctionId => $role | blank
     * @return array
     */
    protected function getPolicePawnAllocations() {
        return $this->policePawnAllocations;
    }

    /**
     * Get Police Officer Pawn Positions as junctionId list
     * @return array
     */
    protected function getPolicePawnAllocatedJunctionIDs() {
        return array_keys($this->getPolicePawnAllocations());
    }

    /**
     * Unset Police Pawn Positions
     */
    protected function clearPolicePawnAllocations() {
        $this->policePawnAllocations = [];
    }

    /**
     * Tells how many junctions can still be populated with police pawn.
     * @return int
     */
    protected function getPolicePawnAllocAbleJunctionCount(){
        return count($this->getPolicePawnAllocAbleJunctionIDs());
    }

    /**
     * Returns TRUE if police pawn can be still placed on police start point during
     * police pawn allocation phase
     * @return boolean
     */
    protected function canAllocatePolicePawnStartPoint(){
        $freeBaseStartPoints = Game_Pathfinder::getPolicemanAvailableBaseStartPoints(
            $this->getPoliceOfficersPositions()
        );

        $freeStartPoints = Game_Pathfinder::getPolicemanAvailableBaseStartPoints(
            $this->getPolicePawnAllocatedJunctionIDs() + $this->getPoliceOfficersPositions()
        );

        return count(array_diff($freeBaseStartPoints, $freeStartPoints)) < self::POLICE_REALLOCABLE_PAWNS;
    }

    /**
     * Returns free base start points
     * @return bool
     */
    protected function getPoliceUnusedBaseStartPoints(){
        return Game_Pathfinder::getPolicemanAvailableBaseStartPoints(
            $this->getPoliceOfficersPositions()
        );
    }

    /**
     * Return Juncion IDs which can be populated with Police Pawn
     * @return array
     */
    protected function getPolicePawnAllocAbleJunctionIDs() {
        // 1 DAY
        if($this->getDay() === 1){
            return Game_Pathfinder::getPolicemanAvailableBaseStartPoints($this->getPolicePawnAllocatedJunctionIDs());
        }

        // 2, 3, 4 DAY
        $freeBaseStartPoints = Game_Pathfinder::getPolicemanAvailableBaseStartPoints(
            $this->getPoliceOfficersPositions()
        );

        $freeStartPoints = Game_Pathfinder::getPolicemanAvailableBaseStartPoints(
            $this->getPolicePawnAllocatedJunctionIDs() + $this->getPoliceOfficersPositions()
        );

        $output = array_diff($this->getPoliceOfficersPositions(), $this->getPolicePawnAllocatedJunctionIDs());

        if(count(array_diff($freeBaseStartPoints, $freeStartPoints)) < self::POLICE_REALLOCABLE_PAWNS){
            $output += $freeStartPoints;
        }

        return array_values($output);
    }

    /**
     * @param int $junctionId
     * @param string $pawnType
     * @return boolean
     * @throws Exception if junctionId can not be populated with Police Pawn at the moment.
     */
    protected function throwExceptionIfNotLegalPolicePawnAllocation($junctionId, $pawnType = null) {
        if (!in_array($junctionId, $this->getPolicePawnAllocAbleJunctionIDs())) {
            throw new Exception("Can not place police pawn on junction [{$junctionId}]");
        }

        if($pawnType === null) {
            return true;
        }

        if(Game_Role::isPoliceOfficerRole($pawnType)) {
            if (in_array($pawnType, $this->getPolicePawnAllocations())) {
                throw new Exception(Game_Role::getColorNameForRole($pawnType) . " Pawn already placed");
            }
        } else if($pawnType === self::POLICE_BLANK_PAWN) {
            if($this->getPolicePawnInHandBlankCount() === 0) {
                throw new Exception("Only Two Blank Police Pawns are allowed");
            }
        } else {
            throw new Exception("Invalid Pawn Type");
        }
    }

    /**
     * Set Police Pawn Position
     * @param int $junctionId
     * @param string $pawnType must a police officer role or 'blank'
     * @throws Exception
     */
    protected function setPolicePawn($junctionId, $pawnType) {
        $this->throwExceptionIfNotLegalPolicePawnAllocation($junctionId, $pawnType);
        $this->policePawnAllocations[$junctionId] = $pawnType;
    }

    /**
     * Array of PoliceOfficer Roles for missing (not yet assigned) police officer pawns
     * @return array
     */
    protected function getPolicePawnInHandRoles()
    {
        return array_values(array_diff(
            array_keys($this->getPoliceOfficersPositions()), $this->getPolicePawnAllocations()
        ));
    }

    /**
     * Get info of how many blank police officer pawns can be still placed on the board;
     * @return int 0, 1 or 2
     */
    protected function getPolicePawnInHandBlankCount()
    {
        $pawnCountByType = array_count_values($this->getPolicePawnAllocations());
        $usedBlanks = isset($pawnCountByType[self::POLICE_BLANK_PAWN]) ?
            $pawnCountByType[self::POLICE_BLANK_PAWN] : 0;

        if ($usedBlanks > self::MAX_POLICE_BLANK_PAWNS) {
            throw new Exception("Too many blank Police Officer pawns already in use!");
        }

        return self::MAX_POLICE_BLANK_PAWNS - $usedBlanks;
    }


    /**
     * Menu data requiret to display a "Put Pawn menu" in allocation phase of the game.
     * @param $junctionId
     * @return array
     * @throws Exception
     */
    public function getPolicePutPawnMenuData($junctionId) {
        $this->throwExceptionIfNotLegalPolicePawnAllocation($junctionId);

        $output = [];
        foreach ($this->getPolicePawnInHandRoles() as $role) {
            $output[] = [
                'action' => $role,
                'description' => Game_Role::getColorNameForRole($role),
                'id' => $junctionId
            ];
        }

        if ($this->getPolicePawnInHandBlankCount() > 0) {
            $output[] = [
                'action' => self::POLICE_BLANK_PAWN,
                'description' => 'Blank Pawn',
                'id' => $junctionId
            ];
        }

        return $output;
    }

    /**
     * Is police pawn location? True or False.
     * @param int $junctionId
     * @return bool
     */
    protected function isPolicePawnLocation($junctionId){
        return isset($this->getPolicePawnAllocations()[$junctionId]);
    }

    /**
     * Returns TRUE if junction holds blank police pawn, false otherwise.
     * @param int $junctionId
     * @return bool
     */
    protected function isPoliceBlankPawn($junctionId){
        return $this->getPolicePawnAllocations()[$junctionId] === self::POLICE_BLANK_PAWN;
    }

    /**
     * Removes police pawn from allocated pawn list.
     * This is used to remove blank pawns from list.
     * @param int $junctionId
     */
    protected function unsetPolicePawnAllocation($junctionId){
        unset($this->policePawnAllocations[$junctionId]);
    }

    const POLICE_ACTION_ARREST = 'arrest';
    const POLICE_ACTION_SEARCH = 'search';

    /**
     * Returns police action menu data. Array with keys equal to hideoutIDs and value - array containing
     * one or two elements with values of 'search' and/or 'arrest'.
     * @return array
     */
    protected function getPoliceOfficerActionMenuData(){
        $output = [];

        $actionHideouts = $this->getCurrentPoliceOfficerActionHideoutIDs();
        $searchableHideouts = $this->getCurrentPoliceOfficerSearchAbleHideoutIDs();

        foreach($actionHideouts as $hideoutId){
            if( ! $this->hasSearchedHideouts()){
                $output[$hideoutId][] = self::POLICE_ACTION_ARREST;
            }

            if(in_array($hideoutId, $searchableHideouts)){
                $output[$hideoutId][] = self::POLICE_ACTION_SEARCH;
            }
        }

        return $output;
    }

    /*************************************************
     * WOMAN MOVES
     *************************************************/

    /** @var array list of woman who ware moved */
    protected $womenWhoWareMoved = [];

    /** @var array list of woman hideoutId => [hideoutId list] who can move from => [to] */
    protected $womenAvailableMoves = [];

    /**
     * Returns women available moves map where key is current woman position
     * and value is available moves list (array)
     * @returns array
     */
    protected function getWomenAvailableMoves(){
        return $this->womenAvailableMoves;
    }

    /**
     * Get Available moves for certain woman
     * @param $hideoutId
     * @return mixed
     * @throws Exception
     */
    public function getWomanAvailableMoves($hideoutId){
        if(!isset($this->getWomenAvailableMoves()[$hideoutId])){
            throw new Exception("There is no Woman for given hideout Id");
        }
        return $this->getWomenAvailableMoves()[$hideoutId];
    }

    /**
     * Return list of new position of moved women
     * @return array
     */
    protected function getMovedWomen(){
        return $this->womenWhoWareMoved;
    }

    /**
     * @param int $toHideoutId
     */
    protected function markWomanAsMoved($toHideoutId){
        $this->womenWhoWareMoved[] = $toHideoutId;
    }

    /**
     * Clear Moved Women
     */
    protected function clearMovedWoman(){
        $this->womenWhoWareMoved = [];
    }

    /**
     * Generates new list of movable women. Returns TRUE if list is not empty, FALSE otherwise.
     * @returns boolean
     */
    protected function hasMovableWomen(){
        $this->womenAvailableMoves = [];
        $womenHideoutIDs = array_diff($this->getWomenHideoutIDs(), $this->getMovedWomen());

        foreach($womenHideoutIDs as $hideoutId){
            $canWalkTo = Game_Pathfinder::getWretchedAvailableMoves(
                $hideoutId,
                $this->getPolicePawnAllocatedJunctionIDs(),
                $this->getWomenHideoutIDs(),
                $this->getMurderScenes()
            );

            if(!empty($canWalkTo)){
                $this->womenAvailableMoves[$hideoutId] = $canWalkTo;
            }
        }

        return ! empty($this->womenAvailableMoves);
    }

    /**
     * Return list containing positions of women who ware not yet moved
     * and who are able to do a legal move. If non legal move can be done
     * or all women ware moved function will return empty array.
     * @return array
     */
    protected function getMovableWomen(){
        return array_keys($this->womenAvailableMoves);
    }

    /**
     * Check if woman cen move from $fromHideoutId to $toHideoutId
     * @param int $fromHideoutId
     * @param int $toHideoutId
     * @returns bool
     */
    protected function isWomanAllowedToMove($fromHideoutId, $toHideoutId){
        if(!isset($this->getWomenAvailableMoves()[$fromHideoutId])){
            return false;
        }

        if(!in_array($toHideoutId, $this->getWomenAvailableMoves()[$fromHideoutId])){
            return false;
        }

        return true;
    }

    /**
     * Returns info required to generate menu. Each element has keys as 'fromHideoutId', 'toHideoutId' and 'description'
     * @param int $hideoutId
     * @return array
     */
    public function getWomanMovesMenuData($hideoutId)
    {
        $output = [];
        foreach($this->getWomanAvailableMoves($hideoutId) as $toHideoutId){
            $output[] = [
                'fromHideoutId' => $hideoutId,
                'description' => "Move Woman to [{$toHideoutId}]",
                'toHideoutId' => $toHideoutId
            ];
        }
        return $output;
    }

    /*************************************************
     * POLICE SEARCH ACTION HELPER
     **************************************************/

    /** @var array hideout list searched by current police officer */
    protected $searchedHideouts = [];

    /**
     * @return array
     */
    protected function getSearchedHideouts(){
        return $this->searchedHideouts;
    }

    /**
     * Add element to searched hideouts list
     * @param int $hideoutId
     */
    protected function addSearchedHideout($hideoutId){
        $this->searchedHideouts[] = $hideoutId;
    }

//    /**
//     * Test if hideout was searched by current police officer
//     * @param int $hideoutId
//     * @return bool
//     */
//    protected function isSearchedHideout($hideoutId){
//        return in_array($hideoutId, $this->getSearchedHideouts());
//    }

    /**
     * Returns TRUE if any hideout was searched by current police officer
     */
    protected function hasSearchedHideouts(){
        return count($this->getSearchedHideouts()) > 0;
    }

    /**
     * Reset Searched Hideouts array
     */
    protected function resetSearchedHideouts(){
        $this->searchedHideouts = [];
    }


    /**
     * Returns TRUE if all available hideouts has been searched. FALSE otherwise.
     * @return boolean
     */
    protected function areAllHideoutsSearched(){
        return empty($this->getCurrentPoliceOfficerSearchAbleHideoutIDs());
    }


    /*************************************************
     * JACK DELAYED MOVE
     *************************************************/
    
    /**
     * @var string jack latest move method
     */
    protected $jackLatestMoveMethod;

    protected function setJackLatestMoveMethod($method){
        $this->jackLatestMoveMethod = $method;
    }

    protected function getJackLatestMoveMethod(){
        return $this->jackLatestMoveMethod;
    }

    /*************************************************
     * STORAGE
     **************************************************/

    protected $storage = [];

    /**
     * Returns Storage Array
     * @return array
     */
    protected function getStorage(){
        return $this->storage;
    }

    /**
     * Store Murder Scene
     * @param int $hideoutId
     * @param int $index, 0 | 1
     */
    protected function storeMurderScene($hideoutId, $index){
        $this->storeJackMove($hideoutId, null, $index);
    }

    /**
     * Store Clue
     * @param int $hideoutId
     */
    protected function storeClue($hideoutId){
        $this->storage['clues'][$this->getDay()][$this->getJackMoveCount()][] = $hideoutId;
    }

    /**
     * Store Police Search Action
     * @param int $hideoutId
     */
    protected function storePoliceSearchAction($hideoutId){
        $this->storage['policeActions'][$this->getDay()][$this->getJackMoveCount()]['search'][] = $hideoutId;
    }

    /**
     * Store Police Arrest Action
     * @param int $hideoutId
     */
    protected function storePoliceArrestAction($hideoutId){
        $this->storage['policeActions'][$this->getDay()][$this->getJackMoveCount()]['arrest'][] = $hideoutId;
    }

    /**
     * Store Police Officers Positions (current)
     */
    protected function storePolicePositions(){
        $this->storage['policePositions'][$this->getDay()][$this->getJackMoveCount()] = $this->getPoliceOfficersPositions();
    }

    const STORE_HIDEOUT_ID = 'hideoutId';
    const STORE_MOVE_TYPE = 'moveType';

    /**
     * Store Jack Movement
     * @param int $hideoutId
     * @param string | null $moveType
     * @param int $index, optional
     * @return boolean
     */
    protected function storeJackMove($hideoutId, $moveType, $index = null){
        if($index !== null) {
            if (isset($this->storage['jackMovementTrack'][$this->getDay()][$index])) {
                throw new Exception("Murder Scene with given index already exist in storage");
            }
            $this->storage['jackMovementTrack'][$this->getDay()][$index] = [
                self::STORE_HIDEOUT_ID => $hideoutId
            ];

            ksort($this->storage['jackMovementTrack'][$this->getDay()]);
            return true;
        }

        $this->storage['jackMovementTrack'][$this->getDay()][$this->getJackMoveCount()] = [
            'moveType' => $moveType,
            self::STORE_HIDEOUT_ID => $hideoutId,
            self::STORE_MOVE_TYPE => $moveType
        ];
        return true;
    }

    /**
     * Save Jack Track Limit for current day
     */
    protected function storeJackTrackLimit(){
        $this->storage['jackMovementTrackLimit'][$this->getDay()] = $this->getJackMoveLimit();
    }

    /*************************************************
     * UPDATE STATS AT GAME END
     **************************************************/

    protected function gameEndedAsPoliceOfficerVictory($socketId){
        $statistic = DAO::factory('Statistic', Server::getUserIdBySocketId($socketId));
        $statistic->gameEndedAsPoliceOfficerVictory();
        $statistic->save();
    }

    protected function gameEndedAsPoliceOfficerDefeat($socketId){
        $statistic = DAO::factory('Statistic', Server::getUserIdBySocketId($socketId));
        $statistic->gameEndedAsPoliceOfficerDefeat();
        $statistic->save();
    }

    protected function gameEndedAsJackVictory($socketId){
        $statistic = DAO::factory('Statistic', Server::getUserIdBySocketId($socketId));
        $statistic->gameEndedAsJackVictory();
        $statistic->save();
    }

    protected function gameEndedAsJackDefeat($socketId){
        $statistic = DAO::factory('Statistic', Server::getUserIdBySocketId($socketId));
        $statistic->gameEndedAsJackDefeat();
        $statistic->save();
    }

    /*************************************************
     * DEBUG
     **************************************************/

    public function debugSetDay($day){
        $this->setDay($day);
    }

    public function debugWoman(){
        return [
            'tokens' => $this->getWomenHideoutIDs(),
            'toMove' => $this->getMovableWomen(),
            'moved' => $this->getMovedWomen(),
            'moves' => $this->getWomenAvailableMoves()
        ];
    }

    public function debugPolice(){
        return [
            'canAllocatePoliceStartPoints' => $this->canAllocatePolicePawnStartPoint(),
            'policePositions' => $this->getPoliceOfficersPositions(),
            'pawnAllocations' => $this->getPolicePawnAllocations(),
            'currentAvailableMoves' => $this->getPoliceOfficerAvailableMoves($this->getCurrentPoliceOfficer()),
            'currentAvailableActions' => $this->getPoliceOfficerAvailableActionHideoutIDs(
                $this->getPoliceOfficerPositionForRole($this->getCurrentPoliceOfficer())
            ),
            'searchedHideouts' => $this->getSearchedHideouts(),
            'currentOrder' => $this->getPoliceOfficerOrder(),
            'positionsWithoutCurrentOfficer' => $this->getPoliceOfficersPositionsWithoutRole($this->getCurrentPoliceOfficer()),
            'junctionIDsWithoutCurrentOfficer' => $this->getPoliceOfficersJunctionIDsWithoutRole()
        ];
    }

    public function debugJack(){
        return [
            'Move Track' => $this->getJackMoveTrack(),
            'Current Move' => $this->getJackMoveCount(),
            'Current Limit' => $this->getJackMoveLimit(),
            'Current Position' => $this->getJackPosition(),
            'Available Moves' => $this->getJackAvailableMoves(),
            'MurderCountPresentDay' => $this->getMurdersCountForPresentDay(),
            'MurderLimitPresentDay' => $this->getMurderLimit()
        ];
    }

    public function debugStorage(){
        return [
            'Storage' => $this->getStorage()
        ];
    }

    public function debugSet($data){
        if(isset($data['carriage'])){
            $this->availableJackSpecialMoves[self::MOVEMENT_CARRIAGE] = $data['carriage'];
        }

        if(isset($data['alley'])){
            $this->availableJackSpecialMoves[self::MOVEMENT_ALLEY] = $data['alley'];
        }

        if(isset($data['track'])){
            $this->jackMoveTrack = $data['track'];
        }
    }

    public function debugSkipPoliceTurn(){
        $this->setActionId(self::ACTION_BEFORE_ESCAPE_IN_THE_NIGHT);
        $this->run();
    }
}