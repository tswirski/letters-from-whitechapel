<?php

defined('SYSPATH') or die('No direct script access.');

class Server
{
    /** @var array Serwisy z których użytkownik powinien zostać wyrejestrowany podczas opuszczania serwisu */
//    protected static $services = [
//        Service_Dashboard_General::class,
//        Service_Dashboard_Game::class
//    ];

    /** @var {int} ID aktualnie przetwarzanego socketu */
    protected static $currentSocketId;

    /**
     * @var {array} Tablica w której kluczem jest ID socketu, a wartością ID użytkownika
     */
    protected static $socketIdToUserId = [];

    /**
     * @var {array} Tablica w której kluczem jest ID użytkownika,
     * a wartością obiekt modelu reprezentujący użytkownika.
     */
    protected static $userIdToModel = [];

    /**
     * @var {array} Tablica w której kluczem jest ID socketu, a wartością ID serwisu.
     */
//    protected static $socketIdToServiceId = [];

    /**
     * Ustawia ID aktualnie przetwarzanego socket'a użytkownika
     * @param {int} $socketId
     * @return null
     */
    public static function setSocketId($socketId) {
        self::$currentSocketId = $socketId;
    }

    /**
     * Zwraca ID aktualnie przetwarzanego socket'a użytkownika
     * @returns int | null (jeśli nie zainicjalizowano);
     */
    public static function getSocketId() {
        return self::$currentSocketId;
    }

    /**
     * Zwraca ID użytkownika dla podanego ID socketu lub null
     * @param int $socketId
     * @return int | null
     */
    public static function getUserIdBySocketId($socketId) {
        return Arr::get(self::$socketIdToUserId, $socketId);
    }

    /**
     * Zwraca ID socketu podanego użytkownika lub null
     * @param int $userId
     * @return int | null
     */
    public static function getSocketIdByUserId($userId) {
        if (!$socketId = array_search($userId, self::$socketIdToUserId)) {
            return null;
        }
        return $socketId;
    }

    /**@todo albo przy ustawianiu socketId zerować userId i User = null i jesli === null to wyszukać
     * jednorazowo w tablicy, przypisac do zmiennej aby drugi raz nie szukać albo w funkcjach uzywajacych
     * getUserId stworzyc zmienną $userId = getUserId aby nie szukac za kazdym faken razem */

    /**
     * Zwraca ID aktualnie przetwarzanego użytkownika
     * @return int | null (jeśli nie zainicjalizowano)
     */
    public static function getUserId(){
        return self::getUserIdBySocketId(self::getSocketId());
    }

    /**
     * Zwraca model użytkownika dla podanego user Id
     * @param int $userId
     * @returns object /Model_User
     * @throws Kohana_Exception gdy użytkownik o podanym ID nie istnieje.
     */
    public static function getUserById($userId)
    {
        $user = isset(self::$userIdToModel[$userId])
            ? self::$userIdToModel[$userId]
            : DAO::factory('User', $userId);

        if (!$user->exists()) {
            throw new Kohana_Exception("User $userId not found");
        }
        return $user;
    }

    /**
     * Zwraca TRUE jeśli podany socket i aktualny socket są tożsame
     * @param {int} $socketId
     */
    public static function isSameSocketAs($socketId){
        return $socketId === Server::getSocketId();
    }

    /**
     * Zwraca model użytkownika dla podanego socket Id
     * @param int $socketId
     * @returns object /Model_User
     * @throws Kohana_Exception gdy użytkownik o podanym ID nie istnieje.
     */
    public static function getUserBySocketId($socketId)
    {
        return self::getUserById(self::getUserIdBySocketId($socketId));
    }

    /**
     * Zwraca model aktualnie przetwarzanego użytkownika.
     * @returns object /Model_User
     * @throws Kohana_Exception
     */
    public static function getUser() {
        return self::getUserById(self::getUserId());
    }

    /**
     * Zwraca listę ID zarejestrowanych Socketów
     * @return array
     */
    public static function getRegisteredSocketIDs()
    {
        return array_keys(self::$socketIdToUserId);
    }

    /**
     * Zwraca listę ID aktywnych użytkowników
     * @return array
     */
    public static function getRegisteredUserIDs()
    {
        return array_keys(self::$userIdToModel);
    }

    /**
     * Zwraca TRUE gdy socket jest zarejestrowany, FALSE w przeciwnym wypadku
     * @param {int} $socketId
     * @return bool
     */
    public static function isSocketActive($socketId)
    {
        return isset(self::$socketIdToUserId[$socketId]);
    }

    /**
     * Sprawdza czy dany użytkownik jest zalogowany.
     * Opcjonalnie czy podany socketId jest zarejestrwoany i przypisany do podanego userId.
     * @param int $userId
     * @return bool
     */
    public static function isUserLoggedIn($userId, $socketId = null)
    {
        if ($socketId !== null) {
            if (!isset(self::$socketIdToUserId[$socketId])) {
                return false;
            }

            if (self::$socketIdToUserId[$socketId] !== $userId) {
                return false;
            }
            return true;
        }
        return (self::getSocketIdByUserId($userId) !== null);
    }

    /**
     * Tworzy strukturę nowego użytkownika
     * @param int $userId
     * @param int $socketId
     * @param object $user Model Użytkownika
     * @return null
     */
    public static function registerUserAndSocket($userId, $socketId, $user)
    {
        self::$socketIdToUserId[$socketId] = $userId;
        self::$userIdToModel[$userId] = $user;
    }

    /**
     * Pozwala zamienić socket dla aktualnego użytkownika
     * @param int $newSocketId
     * @return boolean
     */
    public static function reassignUserSocket($newSocketId)
    {
        Service::unsubscribe();
        self::$socketIdToUserId[$newSocketId] = self::getUserId();
        Arr::remove(self::$socketIdToUserId, self::getSocketId());
        return true;
    }

    /**
     * Usuwa dane z pamięci podręcznej dla użytkownika przypisanego do aktualnego socketId
     * gdy $socketId wskazuje na aktywny socket.
     * @return bool
     */
    public static function unregister()
    {
        $socketId = Server::getSocketId();
        if (!self::isSocketActive($socketId)) {
            return false;
        } 

        Service::unsubscribe();
        echo "\n[socket: $socketId] UNREGISTER\n";

        Arr::remove(self::$userIdToModel, self::getUserIdBySocketId($socketId));
        Arr::remove(self::$socketIdToUserId, $socketId);
        return true;
    }

    /**
     * Zwraca informację czy użytkownik może zostać zalogowany przy użyciu podanego kodu logowania
     * @param (int) $user_id
     * @param string $code
     * @return boolean
     */
    public static function isUserAuthorized($user, $wsToken)
    {
        if (!$user->exists() || $user->get(Model_User::COLUMN_WS_TOKEN) !== $wsToken) {
            return false;
        }
        return true;
    }

    /**
     * Deaktualizacja kodu użytkownika. Użytkownik może zalogować się przy pomocy jednego kodu tylko raz.
     * @param {int} $user_id
     * @return boolean
     */
    public static function resetUserCode($user)
    {
        if (!$user->exists()) {
            return false;
        }
        $user->set(Model_User::COLUMN_WS_TOKEN, NULL);
        $user->update();
        return true;
    }
}
