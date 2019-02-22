<?php

    class Game_Machine_Abstraction
    {
        /** const */
        const GRAPH_ROLE = 'role';
        const GRAPH_ACTION = 'action';
        const GRAPH_PRE = 'pre';

        const MOVEMENT_CARRIAGE = 'carriage';
        const MOVEMENT_ALLEY = 'alley';
        const MOVEMENT_WALK = 'walk';

        const WRETCHED_TOKEN_BLANK = 'blank';
        const WRETCHED_TOKEN_MARKED = 'marked';

        const FIRST_DAY = 1;
        const DOUBLE_KILL_DAY = 3;
        const LAST_DAY = 4;

        /** vars */
        protected
            /** @var Service_Board_Object */
            $board,
            /** @var int 1 | 2 | 3 | 4  */
            $gameDay,
            /** @var int  */
            $gameActionId,
            /** @var array as [int actionId => [config]] */
            $gameGraph = [];

        const ACTION_SET_JACK_HIDEOUT = 1;
        const ACTION_SET_WRETCHED_TOKENS = 2;
        const ACTION_SET_POLICE_PAWNS = 3;
        const ACTION_VICTIMS_ARE_CHOSEN = 4;
        const ACTION_KILL_OR_WAIT = 5;
        const ACTION_MOVE_WOMAN_TOKENS = 6;
        const ACTION_REVEAL_POLICE_PAWN = 7;
        const ACTION_KILL = 8;
        const ACTION_ALARM_WHISTLES = 9;
        const ACTION_BEFORE_ESCAPE_IN_THE_NIGHT = 10;
        const ACTION_ESCAPE_IN_THE_NIGHT = 11;
        const ACTION_ENTER_HIDEOUT = 12;
        const ACTION_BEFORE_HUNTING_THE_MONSTER = 13;
        const ACTION_HUNTING_THE_MONSTER = 14;
        const ACTION_BEFORE_CLUES_AND_SUSPICION = 15;
        const ACTION_CLUES_AND_SUSPICION = 16;
        const ACTION_READINESS = 30;

        protected function setupGraph(){
            $this->gameGraph = [
                self::ACTION_SET_JACK_HIDEOUT => [
                    self::GRAPH_ROLE => Game_Role::JACK,
                    self::GRAPH_ACTION => 'actionSetJackHideout',
                    self::GRAPH_PRE => 'preSetJackHideout'
                ],

                self::ACTION_SET_WRETCHED_TOKENS => [
                    self::GRAPH_ROLE => Game_Role::JACK,
                    self::GRAPH_ACTION => 'actionSetWretchedToken',
                    self::GRAPH_PRE => 'preSetWretchedTokens'
                ],

                self::ACTION_SET_POLICE_PAWNS => [
                    self::GRAPH_ROLE => function(){
                        return $this->getChiefOfInvestigation();
                    },
                    self::GRAPH_ACTION => 'actionSetPolicePawn',
                    self::GRAPH_PRE => 'preSetPolicePawns'
                ],

                self::ACTION_VICTIMS_ARE_CHOSEN => [
                    self::GRAPH_PRE => 'preVictimsAreChosen'
                ],

                self::ACTION_KILL_OR_WAIT => [
                    self::GRAPH_ROLE => Game_Role::JACK,
                    self::GRAPH_ACTION => 'actionKillOrWait',
                    self::GRAPH_PRE => 'preKillOrWait'
                ],

                self::ACTION_MOVE_WOMAN_TOKENS => [
                    self::GRAPH_ROLE => function(){
                        return $this->getChiefOfInvestigation();
                    },
                    self::GRAPH_ACTION => 'actionMoveWoman',
                    self::GRAPH_PRE => 'preMoveWomen'
                ],

                self::ACTION_REVEAL_POLICE_PAWN => [
                    self::GRAPH_ROLE => Game_Role::JACK,
                    self::GRAPH_ACTION => 'actionRevealPolicePawn',
                    self::GRAPH_PRE => 'preRevealPolicePawn'
                ],

                self::ACTION_KILL => [
                    self::GRAPH_ROLE => Game_Role::JACK,
                    self::GRAPH_ACTION => 'actionKill',
                    self::GRAPH_PRE => 'preKill'
                ],

                self::ACTION_ALARM_WHISTLES => [
                    self::GRAPH_PRE => 'preRevealPolicePawns'
                ],

                self::ACTION_BEFORE_ESCAPE_IN_THE_NIGHT =>[
                    self::GRAPH_PRE => 'preBeforeEscapeInTheNight'
                ],

                self::ACTION_ESCAPE_IN_THE_NIGHT => [
                    self::GRAPH_ROLE => Game_Role::JACK,
                    self::GRAPH_ACTION => 'actionEscapeInTheNight',
                    self::GRAPH_PRE => 'preEscapeInTheNight'
                ],

                self::ACTION_ENTER_HIDEOUT => [
                    self::GRAPH_ROLE => Game_Role::JACK,
                    self::GRAPH_ACTION => 'actionEnterHideout',
                    self::GRAPH_PRE => 'preEnterHideout'
                ],

                self::ACTION_BEFORE_HUNTING_THE_MONSTER => [
                    self::GRAPH_PRE => 'preBeforeHuntingTheMonster'
                ],

                self::ACTION_HUNTING_THE_MONSTER => [
                    self::GRAPH_ROLE => function(){
                        return $this->getCurrentPoliceOfficer();
                    },
                    self::GRAPH_ACTION => 'actionHuntingTheMonster',
                    self::GRAPH_PRE => 'preHuntingTheMonster'
                ],

                self::ACTION_BEFORE_CLUES_AND_SUSPICION => [
                    self::GRAPH_PRE => 'preBeforeCluesAndSuspicion'
                ],


                self::ACTION_CLUES_AND_SUSPICION => [
                    self::GRAPH_ROLE => function(){
                        return $this->getCurrentPoliceOfficer();
                    },
                    self::GRAPH_ACTION => 'actionCluesAndSuspicion',
                    self::GRAPH_PRE => 'preCluesAndSuspicion'
                ],
                self::ACTION_READINESS => [
                    self::GRAPH_ROLE => function(){
                       return $this->getBoard()->getSocketIdByRole(Game_Role::JACK) !== Server::getSocketId();
                    },
                    self::GRAPH_ACTION => 'actionConfirmPoliceReadiness',
                    self::GRAPH_PRE => 'preConfirmPoliceReadiness'
                ]
            ];
        }

        /**
         * JACK SPECIAL MOVES
         */

        /**
         * @var array jack special moves indexed with day
         */
        protected $jackSpecialMoveLimit = [
            1 => [
                self::MOVEMENT_ALLEY => 2,
                self::MOVEMENT_CARRIAGE => 3
            ],

            2 => [
                self::MOVEMENT_ALLEY => 2,
                self::MOVEMENT_CARRIAGE => 2
            ],

            3 => [
                self::MOVEMENT_ALLEY => 1,
                self::MOVEMENT_CARRIAGE => 2
            ],

            4 => [
                self::MOVEMENT_ALLEY => 1,
                self::MOVEMENT_CARRIAGE => 1
            ],
        ];

        /**
         * Jack special move limit for day
         * @param int $day
         * @return array
         */
        protected function getJackSpecialMoveLimitForDay($day){
            return $this->jackSpecialMoveLimit[$day];
        }

        /**
         * Jack special move limit for current day
         * @return array
         */
        protected function getJackSpecialMoveLimit(){
            return $this->getJackSpecialMoveLimitForDay($this->getDay());
        }

        /**
         * POLICE OFFICER ORDER
         */
        
        /** @var array (global) */
        protected $policeOfficerOrderBase = [
            Game_Role::RED_POLICE_OFFICER,
            Game_Role::YELLOW_POLICE_OFFICER,
            Game_Role::BLUE_POLICE_OFFICER,
            Game_Role::GREEN_POLICE_OFFICER,
            Game_Role::BROWN_POLICE_OFFICER,
        ];

        /** @var array Police Officer Order (current day) */
        protected $policeOfficerOrder;

        /** @var int current police officer order index */
        protected $policeOfficerOrderIndex = 0;

        /**
         * Returns police officer order array
         * @return array
         */
        protected function getPoliceOfficerOrder(){
            return $this->policeOfficerOrder;
        }

        /**
         * Returns current police officer order Index
         * @return int
         */
        protected function getPoliceOfficerOrderIndex(){
            return $this->policeOfficerOrderIndex;
        }

        /**
         * Returns current police officer
         * @return string
         */
        protected function getCurrentPoliceOfficer(){
            return $this->getPoliceOfficerOrder()[$this->getPoliceOfficerOrderIndex()];
        }

        /**
         * Returns TRUE if current index points to last police officer in order
         * @return bool
         */
        protected function isIndexSetToLastPoliceOfficerInOrder(){
            return  $this->getPoliceOfficerOrderIndex() == (count($this->getPoliceOfficerOrder()) - 1);
        }


        /**
         * Go to Next Police Officer Index
         */
        protected function setIndexToNextPoliceOfficerInOrder(){
            if($this->isIndexSetToLastPoliceOfficerInOrder()){
                throw new Exception("Can not increment Police Officer Index");
            }

            $this->policeOfficerOrderIndex ++;
        }
        
        /**
         * Reset Police Officer Index
         */
        protected function setIndexToFirstPoliceOfficerInOrder(){
            $this->policeOfficerOrderIndex = 0;
        }

        /**
         * Compile Police Officer Order for Day
         */
        protected function setPoliceOfficerOrderForCurrentDay(){
            // reset police officer order array
            $this->policeOfficerOrder = [];
            // get base index for Chief of Investigation
            $startIndex = array_search($this->getChiefOfInvestigation(), $this->policeOfficerOrderBase);

            for($i = $startIndex; $i < count($this->policeOfficerOrderBase); $i++){
                $this->policeOfficerOrder[] = $this->policeOfficerOrderBase[$i];
            }

            for($i = 0; $i < $startIndex; $i++){
                $this->policeOfficerOrder[] = $this->policeOfficerOrderBase[$i];
            }
        }

        /**
         * WRETCHED TOKENS
         */

        /**
         * @var array wretched token limit indexed with day
         */
        protected $wretchedTokenLimit = [
            1 => [
                self::WRETCHED_TOKEN_BLANK => 3,
                self::WRETCHED_TOKEN_MARKED => 5
            ],

            2 => [
                self::WRETCHED_TOKEN_BLANK => 3,
                self::WRETCHED_TOKEN_MARKED => 4
            ],

            3 => [
                self::WRETCHED_TOKEN_BLANK => 3,
                self::WRETCHED_TOKEN_MARKED => 3
            ],

            4 => [
                self::WRETCHED_TOKEN_BLANK => 3,
                self::WRETCHED_TOKEN_MARKED => 1
            ],
        ];

        /**
         * Return wretched token limit for given day
         * @param int $day
         * @return array
         */
        protected function getWretchedTokenLimitForDay($day){
            return $this->wretchedTokenLimit[$day];
        }

        /**
         * Return wretched token limit for current day
         * @return array
         */
        protected function getWretchedTokenLimit(){
            return $this->getWretchedTokenLimitForDay($this->getDay());
        }

        /**
         * @var array murder limit indexed with day
         */
        protected $murderLimit = [
            1 => 1,
            2 => 1,
            3 => 2,
            4 => 1
        ];

        /**
         * Get murder limit for present day
         * @return int
         */
        protected function getMurderLimit(){
            return $this->murderLimit[$this->getDay()];
        }

        /**
         * CHIEF OF INVESTIGATION
         */
        
        /** @var array chief of investigation user ID list */
        protected $chiefOfInvestigationOrder = [];

        /**
         * Set Chief of investingation in random order
         */
        protected function randomizeChiefOfInvestigation(){
            $this->chiefOfInvestigationOrder = [
                Game_Role::BLUE_POLICE_OFFICER,
                Game_Role::RED_POLICE_OFFICER,
                Game_Role::GREEN_POLICE_OFFICER,
                Game_Role::BROWN_POLICE_OFFICER,
                Game_Role::YELLOW_POLICE_OFFICER
            ];

            shuffle($this->chiefOfInvestigationOrder);
        }

        /**
         * Returns Chief Of Investigation role name for given day
         * @param int $dayNo
         * @return string
         */
        protected function getChiefOfInvestigationForDay($dayNo){
            return $this->chiefOfInvestigationOrder[$dayNo];
        }

        /**
         * Returns current Chief of Investigation
         * @return string (role)
         */
        protected function getChiefOfInvestigation(){
            return $this->getChiefOfInvestigationForDay($this->getDay());
        }

        /**
         * @return Service_Board_Object
         */
        public function getBoard(){
            return $this->board;
        }

        /**
         * DAY
         */

        /**
         * @return int day
         */
        protected function getDay(){
            return $this->gameDay;
        }

        /**
         * Returns TRUE if first day, false otherwise
         * @returns boolean
         */
        public function isFirstDay(){
            return $this->getDay() === self::FIRST_DAY;
        }

        /**
         * Returns TRUE if last day, false otherwise
         * @returns boolean
         */
        public function isLastDay(){
            return $this->getDay() === self::LAST_DAY;
        }

        /**
         * Allows to set DAY
         * @param int $day
         * @returns boolean
         */
        protected function setDay($day){
            $day = (int) $day;
            if($day < self::FIRST_DAY || $day > self::LAST_DAY){
                throw new Exception ('Invalid Day Value');
            }
            return $this->gameDay = $day;
        }

        /**
         * Set to next day
         * @throws Exception on failure
         */
        public function setNextDay(){
            if($this->isLastDay()){
                throw new Exception("This is the last day");
            }

            $this->gameDay++;
        }

        /**
         * ACTION ID
         */

        /**
         * Set game state without affecting DAY no.
         * @param int actionId
         */
        public function setActionId($actionId){
            if(! array_key_exists($actionId, $this->gameGraph)){
                throw new Exception("Invalid Action ID");
            }

            $this->gameActionId = $actionId;
        }

        /**
         * @return int actionId
         */
        protected function getActionId(){
            return $this->gameActionId;
        }

        /**
         * Get Game Graph
         * @return array
         */
        protected function getGameGraph(){
            return $this->gameGraph;
        }

        /**
         * Returns single element of current graph state
         * @param string element key
         * @return string
         */
        public function getCurrentElement($element){
            return $this->getGameGraph()[$this->getActionId()][$element];
        }

        /**
         * Get current graph 'pre' method name
         * @return string
         */
        protected function getGraphPre(){
            return $this->getCurrentElement(self::GRAPH_PRE);
        }

        /**
         * Get current graph 'action' method name
         * @return string
         */
        protected function getGraphAction(){
            return $this->getCurrentElement(self::GRAPH_ACTION);
        }

        /**
         * Get SocketID for current Graph Role
         * @return int
         */
        protected function getSocketIdForGraphRole(){
            return $this->getBoard()->getSocketIdByRole($this->getGraphRole());
        }

        /**
         * Get SocketIDs for all except current Graph Role
         * @return array
         */
        protected function getSocketIDsWithoutGraphRole(){
            return $this->getBoard()->getSocketIDsWithoutRole($this->getGraphRole());
        }

        /**
         * Get current graph role
         * @return string
         */
        public function getGraphRole(){
            $graphRole = $this->getCurrentElement(self::GRAPH_ROLE);
            return is_callable($graphRole) ? $graphRole() : $graphRole;
        }

        /**
         * Magic method used to access graph action method
         * @param string $method
         * @param array $params
         */
        public function call($method, $params){
            if($this->getGraphRole() !== true && $this->getSocketIdForGraphRole() !== Server::getSocketId()){
                echo "Can not call this function at the moment";
                return false;
            }

            if($method !== $this->getGraphAction()){
                echo "Method not found, " + $method;
                return false;
            }

            $result = call_user_func_array([$this, $method], $params);

            if($result === true){
                $this->run();
            }
            return $result;
        }

        /**
         * Kikstart the machine
         */
        public function run() {
            if($this->getActionId() == self::ACTION_SET_JACK_HIDEOUT){
                call_user_func([$this, 'dayBegins']);
            }

            /**
             * Pre-fast-forward. Skip action phase and go to another pre by setting different day or action id
             * from within Pre function and return true to ignite.
             */
            $day = $this->getDay();
            $action = $this->getActionId();

            if(call_user_func([$this, $this->getGraphPre()]) === true){
                if($day !== $this->getDay() || $action !== $this->getActionId()){
                    $this->run();
                }
            };
        }

        /**
         * Constructor
         * @param Service_Board_Object $board
         */
        public function __construct(Service_Board_Object $board) {
            $this->board = $board;
            $this->setDay(self::FIRST_DAY);
            $this->setupGraph();
            $this->setActionId(self::ACTION_SET_JACK_HIDEOUT);
            $this->randomizeChiefOfInvestigation();
        }

        /**
         * Validate Hideout Id
         * @param $hideoutId
         * @return int
         * @throws Exception
         */
        public function validHideoutId($hideoutId){
            $hideoutId = (int) $hideoutId;

            if($hideoutId < 1 || $hideoutId > 195){
                throw new Exception("Invalid Hideout Id");
            }

            return $hideoutId;
        }

        /**
         * Validate Junction Id
         * @param $junctionId
         * @return int
         * @throws Exception
         */
        public function validJunctionId($junctionId){
            $junctionId = (int) $junctionId;

            if($junctionId < 1 || $junctionId > 234){
                throw new Exception("Invalid Junction Id");
            }

            return $junctionId;
        }
    }