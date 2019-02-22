<?php

class Model_Statistic extends DAO {

    const COLUMN_USER_ID = 'user_id';
    const COLUMN_DISCONNECTIONS = 'disconnections';
    const COLUMN_GAMES_WON_AS_JACK = 'games_won_as_jack';
    const COLUMN_GAMES_LOST_AS_JACK = 'games_lost_as_jack';
    const COLUMN_GAMES_WON_AS_POLICE = 'games_won_as_police';
    const COLUMN_GAMES_LOST_AS_POLICE = 'games_lost_as_police';
    const COLUMN_LAST_GAME_TIMESTAMP = 'last_game_timestamp';

    protected function defineColumns() {
        return [
            self::COLUMN_USER_ID,
            self::COLUMN_DISCONNECTIONS,
            self::COLUMN_GAMES_WON_AS_JACK,
            self::COLUMN_GAMES_LOST_AS_JACK,
            self::COLUMN_GAMES_WON_AS_POLICE,
            self::COLUMN_GAMES_LOST_AS_POLICE,
            self::COLUMN_LAST_GAME_TIMESTAMP
        ];
    }

    protected function defineKey() {
        return [
            self::COLUMN_USER_ID
        ];
    }

    protected function defineUpdateableColumns() {
        return [
            self::COLUMN_DISCONNECTIONS,
            self::COLUMN_GAMES_WON_AS_JACK,
            self::COLUMN_GAMES_LOST_AS_JACK,
            self::COLUMN_GAMES_WON_AS_POLICE,
            self::COLUMN_GAMES_LOST_AS_POLICE,
            self::COLUMN_LAST_GAME_TIMESTAMP
        ];
    }

    protected function defineCreatedTimestampColumn() {
        return NULL;
    }

    protected function defineUpdatedTimestampColumn() {
        return NULL;
    }

    protected function defineUpdateCountColumn() {
        return null;
    }

    protected function defineBooleanColumns() {
        return [];
    }

    protected function defineValidation() {
        return [
            self::COLUMN_USER_ID => [
                'digit',
                'not_empty'
            ],
            self::COLUMN_DISCONNECTIONS => ['digit'],
            self::COLUMN_GAMES_WON_AS_JACK => ['digit'],
            self::COLUMN_GAMES_LOST_AS_JACK => ['digit'],
            self::COLUMN_GAMES_WON_AS_POLICE => ['digit'],
            self::COLUMN_GAMES_LOST_AS_POLICE => ['digit'],
            self::COLUMN_LAST_GAME_TIMESTAMP => ['digit'],
        ];
    }

    protected function defineDeletedAtColumn() {
        return NULL;
    }

    protected function defineDefaultValues() {
        return [
            self::COLUMN_DISCONNECTIONS => 0,
            self::COLUMN_GAMES_WON_AS_JACK => 0,
            self::COLUMN_GAMES_LOST_AS_JACK => 0,
            self::COLUMN_GAMES_WON_AS_POLICE => 0,
            self::COLUMN_GAMES_LOST_AS_POLICE => 0,
            self::COLUMN_LAST_GAME_TIMESTAMP => 0,
        ];
    }

    protected function defineInputFilter() {
        return [];
    }

    protected function defineOutputFilter() {
        return [];
    }

    /**
     *
     */
    public function gameEndedAsJackVictory(){
        $this->{self::COLUMN_GAMES_WON_AS_JACK} ++;
        $this->{self::COLUMN_LAST_GAME_TIMESTAMP} = time();
    }

    /**
     *
     */
    public function gameEndedAsJackDefeat(){
        $this->{self::COLUMN_GAMES_LOST_AS_JACK} ++;
        $this->{self::COLUMN_LAST_GAME_TIMESTAMP} = time();
    }

    /**
     *
     */
    public function gameEndedAsPoliceOfficerVictory(){
        $this->{self::COLUMN_GAMES_WON_AS_POLICE} ++;
        $this->{self::COLUMN_LAST_GAME_TIMESTAMP} = time();
    }

    /**
     *
     */
    public function gameEndedAsPoliceOfficerDefeat(){
        $this->{self::COLUMN_GAMES_LOST_AS_POLICE} ++;
        $this->{self::COLUMN_LAST_GAME_TIMESTAMP} = time();
    }

    /**
     * The player was disconnect from server
     */
    public function gameEndedWithDisconnection(){
        $this->{self::COLUMN_DISCONNECTIONS} ++;
        $this->{self::COLUMN_LAST_GAME_TIMESTAMP} = time();
    }

    public function getGamesPlayed(){
        return
            $this->get(self::COLUMN_DISCONNECTIONS) +
            $this->get(self::COLUMN_GAMES_LOST_AS_POLICE) +
            $this->get(self::COLUMN_GAMES_WON_AS_POLICE) +
            $this->get(self::COLUMN_GAMES_LOST_AS_JACK) +
            $this->get(self::COLUMN_GAMES_WON_AS_JACK) ;
    }

    public function getGamesWon(){
        return
            $this->get(self::COLUMN_GAMES_WON_AS_POLICE) +
            $this->get(self::COLUMN_GAMES_WON_AS_JACK) ;
    }

    public function getGamesLost(){
        return
            $this->get(self::COLUMN_GAMES_LOST_AS_POLICE) +
            $this->get(self::COLUMN_GAMES_LOST_AS_JACK);
    }

    public function getGamesPlayedAsJack(){
        return
            $this->get(self::COLUMN_GAMES_WON_AS_JACK) +
            $this->get(self::COLUMN_GAMES_LOST_AS_JACK);
    }
    public function getGamesPlayedAsPolice(){
        return
            $this->get(self::COLUMN_GAMES_WON_AS_POLICE) +
            $this->get(self::COLUMN_GAMES_LOST_AS_POLICE);
    }
}
