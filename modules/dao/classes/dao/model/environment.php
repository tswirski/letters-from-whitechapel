<?php

/**
 * Class DAO_Model_Environment
 * Support for basic database communication.
 * This class was separated from model core due to attempt of creating
 * cross-framework DAO module. Yet some database method was left in other classes.
 */

abstract class DAO_Model_Environment {

    /** @var {string} database in use */
    private $_database = null;

    /**
     * To alter database name create a 'dao' config file and set the 'database' property.
     * @return {string}
     */
    protected function getDatabase() {
        return $this->_database;
    }

    /**
     * CONSTRUCTOR
     */
    protected function __construct() {
        $databaseName = Kohana::$config->load('dao')->get('database', 'default');
        $this->_database = Database::instance($databaseName);
    }

    /**
     * Throw custom exception.
     * @param {string} message
     * @throws {Exception} default or framework-based.
     */
    protected function throwException($message) {
        throw new Kohana_Exception('DAO [' . $this->getModel() . '] ' . $message);
    }

    /**
     * Create SELECT QUERY with limit set to "2" rows (!important).
     * (This mechanism is used to detect key collisions)
     * @param {string} $tableName
     * @param {array} $columnList
     * @param {assoc} $keyMap
     * @return {string} query
     */
    protected function _dbCompileRowSelectQuery($tableName, $columnList, $keyMap, $deletedAtColumn = null) {
        /** Prepare Select opperation */
        $dao = DB::select_array($columnList)->from($tableName);

        foreach ($keyMap as $column => $value) {
            $dao->where($column, '=', $value);
        }

        if ($deletedAtColumn !== null) {
            $dao->where($deletedAtColumn, 'IS', NULL);
        }
        $dao->limit(2);
        return $dao->compile($this->getDatabase());
    }

    /**
     * Query Database, Return Rows as list.
     * Result list may contain "zero", "one" or "two" rows.
     * (Where "two" rows means model key is not unique).
     * @param {string} $query
     * @return {array-array}
     */
    protected function _dbSelectRowByQuery($query) {
        /** Perform Select */
        $db = $this->getDatabase();
        $result = $db->query(Database::SELECT, $query, FALSE);

        return $result->as_array();
    }

    /**
     * Prepare INSERT query
     * @param {string} $tableName
     * @param {assoc} $data
     * @return {string} query
     */
    protected function _dbCompileRowInsertQuery($tableName, $data, $deletedAtColumn = null)
    {
        $dao = DB::insert($tableName)
            ->columns(array_keys($data))
            ->values(array_values($data));
        return $dao->compile($this->getDatabase());
    }

    /**
     * Insert row by query.
     * @param {string} $query
     * @return {int} Last Inserted ID : (>0)  if model has Auto-Increment column, (0) otherwise.
     */
    protected function _dbInsertRowByQuery($query) {
        $db = $this->getDatabase();
        /** Perform INSERT */
        $result = $db->query(Database::INSERT, $query);
        $affectedRows = $result[1];
        if ($affectedRows == 0) {
            $this->throwException("INSERT fail, $affectedRows rows written");
        }
        return $result[0];
    }

    /**
     * Prepare UPDATE query
     * @param {string} $tableName
     * @param {assoc} $modifiedData
     * @param {assoc} $keyMap
     * @return {string} query
     */
    protected function _dbCompileRowUpdateQuery($tableName, $modifiedData, $keyMap) {
        $dao = DB::update($tableName)
                ->set($modifiedData);
        foreach ($keyMap as $keyColumn => $keyColumnValue) {
            $dao->where($keyColumn, '=', $keyColumnValue);
        }
        return $dao->compile($this->getDatabase());
    }

    /**
     * Update  row by query.
     * @param {string} $query
     * @return {int} affected rows count
     */
    protected function _dbUpdateRowByQuery($query) {
        $db = $this->getDatabase();

        return $db->query(Database::UPDATE, $query);
    }

    /**
     * Prepare DELETE query
     * @param {string} $tableName
     * @param {assoc} $keyMap
     * @return {string} query
     */
    protected function _dbCompileRowDeleteQuery($tableName, $keyMap) {
        $dao = DB::delete($tableName);
        foreach ($keyMap as $keyColumn => $keyColumnValue) {
            $dao->where($keyColumn, '=', $keyColumnValue);
        }

        return $dao->compile($this->getDatabase());
    }

    /**
     * Delete row by query.
     * @param {string} $query
     * @return {int} affected rows count
     * @throws {DatabaseException}
     */
    protected function _dbDeleteRowByQuery($query) {
        $db = $this->getDatabase();

        return $db->query(Database::DELETE, $query);
    }

}
