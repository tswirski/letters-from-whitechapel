<?php

class Model_Friend extends DAO {

    const COLUMN_USER_ID = 'user_id';
    const COLUMN_FRIEND_ID = 'friend_user_id';
    const COLUMN_CREATED = 'created';
    const COLUMN_UPDATED = 'updated';

    protected function defineColumns() {
        return [
            self::COLUMN_USER_ID,
            self::COLUMN_FRIEND_ID,
        ];
    }

    protected function defineKey() {
        return [
            self::COLUMN_USER_ID,
            self::COLUMN_FRIEND_ID
        ];
    }

    protected function defineUpdateableColumns() {
        return [];
    }

    protected function defineCreatedTimestampColumn() {
        return null;
    }

    protected function defineUpdatedTimestampColumn() {
        return null;
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
                'digit', 'not_empty'
            ],
            self::COLUMN_FRIEND_ID => [
                'digit', 'not_empty'
            ]
        ];
    }

    protected function defineDeletedAtColumn() {
        return null;
    }

    protected function defineDefaultValues() {
        return [];
    }

    protected function defineInputFilter() {
        return [];
    }

    protected function defineOutputFilter() {
        return [];
    }

}
