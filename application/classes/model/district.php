<?php

class Model_District extends DAO {

    const COLUMN_ID = 'id';
    const COLUMN_POSITION_X = 'position_x';
    const COLUMN_POSITION_Y = 'position_y';

    protected function defineColumns() {
        return [
            self::COLUMN_ID,
            self::COLUMN_POSITION_X,
            self::COLUMN_POSITION_Y,
        ];
    }

    protected function defineKey() {
        return [
            self::COLUMN_ID => TRUE,
        ];
    }

    protected function defineUpdateableColumns() {
        return [
            self::COLUMN_POSITION_X,
            self::COLUMN_POSITION_Y,
        ];
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
            self::COLUMN_ID => [
                'digit',
            ],
            self::COLUMN_POSITION_X => [
                'not_empty'
            ],
            self::COLUMN_POSITION_Y => [
                'not_empty'
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
