<?php

class Model_Correlation extends DAO {

    const NODE_JUNCTION = 'junction';
    const NODE_HIDEOUT = 'hideout';
    const NODE_DISTRICT = 'district';
    const COLUMN_BASE_NODE_ID = 'base_node_id';
    const COLUMN_REMOTE_NODE_ID = 'remote_node_id';
    const COLUMN_BASE_NODE_TYPE = 'base_node_type';
    const COLUMN_REMOTE_NODE_TYPE = 'remote_node_type';
    const COLUMN_DIRECT = 'direct';

    protected function defineColumns() {
        return [
            self::COLUMN_BASE_NODE_ID,
            self::COLUMN_REMOTE_NODE_ID,
            self::COLUMN_BASE_NODE_TYPE,
            self::COLUMN_REMOTE_NODE_TYPE,
            self::COLUMN_DIRECT
        ];
    }

    protected function defineKey() {
        return [
            self::COLUMN_BASE_NODE_ID,
            self::COLUMN_BASE_NODE_TYPE,
            self::COLUMN_REMOTE_NODE_ID,
            self::COLUMN_REMOTE_NODE_TYPE
        ];
    }

    protected function defineUpdateableColumns() {
        return [
            self::COLUMN_BASE_NODE_ID,
            self::COLUMN_BASE_NODE_TYPE,
            self::COLUMN_REMOTE_NODE_ID,
            self::COLUMN_REMOTE_NODE_TYPE,
            self::COLUMN_DIRECT
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
            self::COLUMN_DIRECT
        ];
    }

    protected function defineValidation() {
        return [
            self::COLUMN_BASE_NODE_ID => [
                'digit', 'not_empty'
            ],
            self::COLUMN_BASE_NODE_TYPE => [
                'alpha', 'not_empty'
            ],
            self::COLUMN_REMOTE_NODE_ID => [
                'digit', 'not_empty'
            ],
            self::COLUMN_REMOTE_NODE_TYPE => [
                'alpha', 'not_empty'
            ]
        ];
    }

    protected function defineDeletedAtColumn() {
        return null;
    }

    protected function defineDefaultValues() {
        return [
            self::COLUMN_DIRECT => false
        ];
    }

    protected function defineInputFilter() {
        return [];
    }

    protected function defineOutputFilter() {
        return [];
    }

}
