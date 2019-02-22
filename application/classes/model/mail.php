<?php

class Model_Mail extends DAO {

    const COLUMN_ID = 'id';
    const COLUMN_TEXT = 'text';
    const COLUMN_HTML = 'html';
    const COLUMN_TO = 'to';
    const COLUMN_FROM = 'from';
    const COLUMN_SUBJECT = 'subject';
    const COLUMN_CREATED = 'created';
    const COLUMN_DELIVERED = 'delivered';

    protected function defineColumns() {
        return [
            self::COLUMN_ID,
            self::COLUMN_TEXT,
            self::COLUMN_HTML,
            self::COLUMN_TO,
            self::COLUMN_SUBJECT,
            self::COLUMN_FROM,
            self::COLUMN_CREATED,
            self::COLUMN_DELIVERED
        ];
    }

    protected function defineKey() {
        return [
            self::COLUMN_ID => TRUE,
        ];
    }

    protected function defineUpdateableColumns() {
        return [
            self::COLUMN_DELIVERED
        ];
    }

    protected function defineCreatedTimestampColumn() {
        return self::COLUMN_CREATED;
    }

    protected function defineUpdatedTimestampColumn() {
        return null;
    }

    protected function defineUpdateCountColumn() {
        return null;
    }

    protected function defineBooleanColumns() {
        return array(
            self::COLUMN_DELIVERED
        );
    }

    protected function defineValidation() {
        return [
            self::COLUMN_ID => [
                'digit',
            ],
        ];
    }

    protected function defineDeletedAtColumn() {
        return null;
    }

    protected function defineDefaultValues() {
        return [
            self::COLUMN_DELIVERED => false
        ];
    }

    protected function defineInputFilter() {
        return [];
    }

    protected function defineOutputFilter() {
        return [];
    }

}
