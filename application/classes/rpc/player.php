<?php defined('SYSPATH') or die('No direct script access.');

class Rpc_Player{
    /**
     * Zwraca TRUE gdy podany u�ytkownik jest dodany do znajomych aktualnego u�ytkownika
     * @param {int} $friendId
     * @return boolean
     */
    public static function isFriendOfMine($friendId){
        if($friendId == Server::getUserId()){
            return false;
        }

        return DAO::find('Friend')
            ->where(Model_Friend::COLUMN_USER_ID, '=', Server::getUserId())
            ->where(Model_Friend::COLUMN_FRIEND_ID, '=', $friendId)
            ->getOne() !== null;
    }


    /**
     * Zwraca TRUE je�li u�ytkownik $remoteUserId jest w kr�gu przyja�ni u�ytkownika $localUserId
     * @param {int} $localUserId
     * @param {int} $remoteUserId
     * @return boolean
     */
    public static function areFriends($localUserId, $remoteUserId){
        return DAO::find('Friend')
            ->where(Model_Friend::COLUMN_USER_ID, '=', $localUserId)
            ->where(Model_Friend::COLUMN_FRIEND_ID, '=', $remoteUserId)
            ->getOne() !== null;
    }


        /**
     * Linkuje konta jako znajomych
     * @param {int} $userId
     * @param {int} $friendId
     */
    protected static function _linkFriends($userId, $friendId){
        DAO::factory('Friend', [
                Model_Friend::COLUMN_USER_ID =>$userId,
                Model_Friend::COLUMN_FRIEND_ID =>$friendId]
        )->save();

        return true;
    }

    /**
     * @param {int} $friendId
     * @return boolean
     */
    public static function linkFriends($friendId){
        if($friendId == Server::getUserId()){
            return false;
        }

        return self::_linkFriends(Server::getUserId(), $friendId);
    }

    /**
     * Odlinkowuje konta jako znajomych
     * @param {int} $userId
     * @param {int} $friendId
     */
    protected static function _unlinkFriends($userId, $friendId){
        DAO::factory('Friend', [
                Model_Friend::COLUMN_USER_ID =>$userId,
                Model_Friend::COLUMN_FRIEND_ID =>$friendId]
        )->delete();

        return true;
    }

    /**
     * @param {int} $friendId
     * @return boolean
     */
    public static function unlinkFriends($friendId){

        if($friendId == Server::getUserId()){
            return false;
        }

        return self::_unlinkFriends(Server::getUserId(), $friendId);
    }


    /**
     * Zwraca tablic� ze statystykami u�ytkownika o podanym ID
     * @param {int} userId
     * @return {array}
     */
    public static function details($userId){
        $isMyAccount = $userId === Server::getUserId();
        $statistic = DAO::factory('Statistic', $userId);
        $isFriendOfMine = self::isFriendOfMine($userId);
        return [
            'userId' => $userId,
            'userName' => Server::getUserById($userId)->get(Model_User::COLUMN_NICKNAME),
            'avatarUrl' => Server::getUserById($userId)->getAvatarPath(),
            'isMyAccount' => $isMyAccount,
            'isFriend' => $isFriendOfMine,
            'gamesPlayed' => $statistic->getGamesPlayed(),
            'gamesWon' => $statistic->getGamesWon(),
            'asJack' => $statistic->getGamesPlayedAsJack(),
            'wonAsJack' => $statistic->get(Model_Statistic::COLUMN_GAMES_WON_AS_JACK),
            'asPoliceman' => $statistic->getGamesPlayedAsPolice(),
            'wonAsPoliceman' => $statistic->get(Model_Statistic::COLUMN_GAMES_WON_AS_POLICE),
            'disconnections' => $statistic->get(Model_Statistic::COLUMN_DISCONNECTIONS),
            'latestGameDateTime' => $statistic->get(Model_Statistic::COLUMN_LAST_GAME_TIMESTAMP)
                ? date('h:i / d.m.y', $statistic->get(Model_Statistic::COLUMN_LAST_GAME_TIMESTAMP))
                : 'never played'
        ];
    }
}