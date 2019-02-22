<?php

class Model_Hideout extends DAO {

    const COLUMN_ID = 'id';
    const COLUMN_POSITION_X = 'position_x';
    const COLUMN_POSITION_Y = 'position_y';
    const COLUMN_IS_WRETCHED_STARTPOINT = 'is_victim_startpoint';

    protected function defineColumns() {
        return [
            self::COLUMN_ID,
            self::COLUMN_POSITION_X,
            self::COLUMN_POSITION_Y,
            self::COLUMN_IS_WRETCHED_STARTPOINT
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
            self::COLUMN_POSITION_Y
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
        return [
            self::COLUMN_IS_WRETCHED_STARTPOINT
        ];
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


    public static function count(){
        return (int) DB::select(DB::expr('count(*) as count'))
            ->from('hideouts')
            ->as_assoc(Model_Hideout::COLUMN_ID)
            ->execute()
            ->current()['count'];
    }
}
