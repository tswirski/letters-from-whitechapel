<?php

class Model_User extends DAO {
    public static function getAvatarDirectory() {
        return 'images/';
    }
    CONST DEFAULT_AVATAR_FILE = '0.jpg';

    /**
     * @return string|void
     */
    public function getAvatarPath(){
        if($this->isEmpty(self::COLUMN_AVATAR)){
            return self::getDefaultAvatarPath();
        }
        return self::getAvatarDirectory() . $this->get(self::COLUMN_AVATAR);
    }

    /**
     * @return string
     */
    public static function getDefaultAvatarPath(){
        return self::getAvatarDirectory() . self::DEFAULT_AVATAR_FILE;
    }

    const COLUMN_ID = 'id';
    const COLUMN_PASSWORD = 'password';
    const COLUMN_NICKNAME = 'nickname';
    const COLUMN_WS_TOKEN = 'ws_token';
    const COLUMN_WS_TIMESTAMP = 'ws_timestamp';
    const COLUMN_CREATED = 'created';
    const COLUMN_UPDATED = 'updated';
    const COLUMN_AVATAR = 'avatar';

    protected function defineColumns() {
        return [
            self::COLUMN_ID,
            self::COLUMN_PASSWORD,
            self::COLUMN_NICKNAME,
            self::COLUMN_WS_TOKEN,
            self::COLUMN_WS_TIMESTAMP,
            self::COLUMN_CREATED,
            self::COLUMN_UPDATED,
            self::COLUMN_AVATAR
        ];
    }

    protected function defineKey() {
        return [
            self::COLUMN_ID => TRUE,
        ];
    }

    protected function defineUpdateableColumns() {
        return [
            self::COLUMN_ID,
            self::COLUMN_PASSWORD,
            self::COLUMN_WS_TOKEN,
            self::COLUMN_WS_TIMESTAMP,
            self::COLUMN_UPDATED,
            self::COLUMN_AVATAR
        ];
    }

    protected function defineCreatedTimestampColumn() {
        return self::COLUMN_CREATED;
    }

    protected function defineUpdatedTimestampColumn() {
        return self::COLUMN_UPDATED;
    }

    protected function defineUpdateCountColumn() {
        return null;
    }

    protected function defineBooleanColumns() {
        return [
            
        ];
    }

    protected function defineValidation() {
        return [
            self::COLUMN_ID => [
                'digit',
            ],
            self::COLUMN_PASSWORD => [
                'not_empty'
            ],
        ];
    }

    protected function defineDeletedAtColumn() {
        return 'deleted_at';
    }

    protected function defineDefaultValues() {
        return [
        ];
    }

    protected function defineInputFilter() {
        return [
            self::COLUMN_PASSWORD => function($password){
                return Blowfish::hash($password);
            }
        ];
    }

    protected function defineOutputFilter() {
        return [];
    }

    public static function nickname_exists($nickname) {
        return DAO::find('User')
                        ->where(self::COLUMN_NICKNAME, '=', $nickname)
                        ->getCount() == 1;
    }

    public static function nickname_is_available($nickname) {
        return !self::nickname_exists($nickname);
    }

    public static function email_exists($email) {
        return DAO::find('User')
                        ->where(self::COLUMN_EMAIL, '=', $email)
                        ->getCount() == 1;
    }

    public static function email_is_available($email) {
        return !self::email_exists($email);
    }

    public static function count(){
        return DAO::find('User')->getCount();
    }
}
