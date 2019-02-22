<?php

/**
 * Class DAO_Model_Database
 * This class holds database abstraction which opperates on database environment.
 * Environment functions should be implemented to meet abstraction required format.
 */

abstract class DAO_Model_Database extends DAO_Model_Abstraction {
    /**
     * ABSTRACTION
     */

    /**
     * Should set raw data replacing whatever is in model 'modified' data table.
     */
    abstract protected function setModifiedData(array $data);

    /**
     * Should return array as column => modified value
     */
    abstract protected function getModifiedData();

    /**
     * Should clear unsaved data from model context
     */
    abstract protected function clearModifiedData();

    /**
     * @param {string | array} column name, or array of column names.
     * Should return TRUE if any of given columns ware modified.
     */
    abstract protected function isModified($col);

    /**
     * Should return TRUE if model has setter with given name
     */
    abstract protected function isSetter($setterName);

    /**
     * Should return TRUE if model has getter with given name
     */
    abstract protected function isGetter($getterName);

    /**
     * Should return TRUE if model validation suceed. FALSE otherwise.
     */
    abstract public function isValid();

    /**
     * Should run validation only for columns included in dataset.
     */
    abstract public function isValidData(array $data);

    /**
     * This should allows a data to be set from array
     * @param {array}
     * @returns {null}
     * @throws Exception if column is modificated more then once in one call.
     */
    abstract public function setArray(array $data);

    /**
     * Get data as array unfiltered.
     * @param {array} $expectedColumns
     * @returns {array} unfiltered data
     */
    abstract protected function getArrayUnfiltered(array $expectedColumns);

    /**
     * OVERRIDES
     */

    /**
     * Get Default Values converted (on-the-fly) to (bool) or (string).
     * @return {array}
     */
    protected function getDefaultValues() {
        $defaultValues = parent::getDefaultValues();

        /** Convert DEFAULT VALUES to bool or string */
        foreach ($defaultValues as $column => $value) {
            $defaultValues[$column] = $this->dataTypeCast($column, $value);
        }

        return $defaultValues;
    }

    /**
     * VARIABLES
     */

    /** @var {bool} TRUE if database SELECT attempt was made, FALSE otherwise */
    private $_synchronized = false;

    /** @var {bool} TRUE if model exists in database, FALSE otherwise */
    private $_exists = false;

    /** @var {array} data as stored in database */
    private $_databaseData = array();

    /** @var {array} key as set upon model creation */
    private $_modelKey = array();

    /** @var {string | null} last query */
    private $_lastQuery;

    /** @var {bool} if set to TRUE - changing key of an existsing model is allowed */
    protected $_allowKeyChange = false;

    /**
     * GETTERS
     * */

    /**
     * Get column info.
     * @return {object | null}
     */
    public function getColumnInfo($column) {
        $database = $this->getDatabase();
        $table = $this->getTableName();

        return
                        DB::select()
                        ->from('information_schema.COLUMNS')
                        ->where('TABLE_SCHEMA', '=', $database)
                        ->where('TABLE_NAME', '=', $table)
                        ->where('COLUMN_NAME', '=', $column)
                        ->as_object()->execute($this->getDatabase())->current();
    }

    /**
     * Returns database-stored data for column
     * @param {string} $column
     * @return {mixed}
     */
    protected function getDatabaseColumnData($column) {
        return $this->_databaseData[$column];
    }

    /**
     * Returns data as in database
     * @param {array}
     * @return {array}
     */
    protected function getDatabaseData(array $expectedColums = null) {
        $databaseData = $this->_databaseData;
        if ($expectedColums !== null) {
            $databaseData = array_intersect_key($databaseData, array_flip($expectedColums));
        }
        return $databaseData;
    }

    /**
     * Returns last query used
     * @return {string | null}
     */
    public function getQuery() {
        return $this->_lastQuery;
    }

    /**
     * Returns most recent model key
     * @return {array}
     */
    public function getKey() {
        return $this->_modelKey;
    }

    /**
     * Remove Key from Data
     * @param {array} $data
     * @returns {array} $data without Key columns
     */

    protected function removeKeyFromData(array $data){
        foreach($data as $column => $value){
            if($this->isKeyColumn($column)){
                unset($data[$column]);
            }
        }
        return $data;
    }

    /**
     * CHECKERS
     */

    /**
     * Returns TRUE if column of given name exists in model context
     * @param {string} $column
     * @return {bool}
     */
    protected function isColumn($column) {
        return in_array((string) $column, $this->getColumns());
    }

    /**
     * Tells if model is in sync with DB
     * @return {bool}
     */
    protected function isSynchronized() {
        return $this->_synchronized;
    }

    /**
     * Tests if column is part of the key
     * @param {string} $column
     * @return {bool}
     */
    protected function isKeyColumn($column) {
        return in_array($column, $this->getKeyColumns());
    }

    /**
     * Resets 'allowKeyChange' flag to FALSE and returns whatever was set before this opperation.
     * @return {bool}
     */
    protected function isKeyChangeAllowed() {
        $allowKeyChange = $this->_allowKeyChange;
        $this->_allowKeyChange = false;
        return $allowKeyChange;
    }

    /**
     * Tells if model exists in the DB.
     * Makes a database query if not yet done.
     * @return {bool}
     */
    public function exists() {
        $this->load();
        return $this->_exists;
    }

    /**
     * Bang of $this->xists();
     * @return bool
     */
    public function notExists(){
        return !$this->exists();
    }

    /**
     * SETTERS
     * */

    /**
     * Sets synchronization flag
     * @param {bool}
     * @return {$this}
     */
    protected function setSynchronized($b) {
        $this->_synchronized = (bool) $b;
        return $this;
    }

    /**
     * Saves the last query
     * @param {string}
     * @return {null}
     */
    protected function setLastQuery($query) {
        $this->_lastQuery = $query;
        return null;
    }

    /**
     * To use inside ::factory() method.
     * Checks if passed key contains expected columns.
     * Throws exception if more than one column has NULL value or if NULL value was set
     * for column which is not auto-increment.
     * This function should be called once per model.
     * @param array $key
     * @thorws Exception
     */
    private final function setKey($key) {
        if (!empty($this->_modelKey)) {
            $this->throwException('Key already set');
        }

        $keyArr = $this->_keyToArray($key);
        $this->_keyThrowExceptionIfNotValid($keyArr);
        $this->_modelKey = $keyArr;

        /** If AutoIncrement defined and has value of NULL */
        if ($this->getAutoIncrmentColumn() && $keyArr[$this->getAutoIncrmentColumn()] === NULL) {
            $this->setExists(FALSE);
            $this->setSynchronized(TRUE);
        }
    }

    /**
     * Update model key.
     * @param {mixed} $key
     */
    private final function updateModelKey($key){
        $keyArr = $this->_keyToArray($key);
        $this->_keyThrowExceptionIfNotValid($keyArr);
        $this->_modelKey = $keyArr;
    }

    /**
     * Sets 'Exists' flag
     * @param bool $b
     * @return \DAO_Model_Database
     */
    protected function setExists($b) {
        $this->_exists = (bool) $b;
        return $this;
    }

    /**
     * Sets allowKeyChange flag to TRUE
     */
    public function allowKeyChange() {
        $this->_allowKeyChange = true;
    }

    /**
     * Set Database Data
     * @param array $data
     * @throws Exception if attempt to overwrite model database data detected;
     * @throws Exception if input array contains key not pointing to model column
     * @throws Exception if input array doesnt contain values for all of model columns.
     */
    protected function setDatabaseData(array $data) {
        if ($this->getDatabaseData()) {
            $this->throwException('Database Data can be set only once');
        }

        /* What do we expect? */
        $expectedColumns = $this->getColumns();
        $expectedColumns = array_flip($expectedColumns);

        $missingColumns = array_diff_key($expectedColumns, $data);
        if (!empty($missingColumns)) {
            $missingColumns = implode(',', $missingColumns);
            $this->throwException("At least one column is missing. Those ware not found: {$missingColumns}");
        }
        $unexpectedKeys = array_diff_key($data, $expectedColumns);
        if (!empty($unexpectedKeys)) {
            $unexpectedKeys = implode(',', $unexpectedKeys);
            $this->throwException("At least one column found in input data doesnt match model. Those ware found: {$unexpectedKeys}");
        }

        /** Convert to bool or string */
        foreach ($data as $dataKey => $dataValue) {
            $data[$dataKey] = $this->dataTypeCast($dataKey, $dataValue);
        }

        /** Set Database Data */
        $this->_databaseData = $data;

        /** Update Model Key */
        $this->updateModelKey($this->getDatabaseData($this->getKeyColumns()));

        $this->setSynchronized(true);
        $this->setExists(true);
    }

    /**
     * Updates database-data-array storage
     * @param array $data
     */
    protected function updateDatabaseData(array $data) {
        foreach ($data as $column => $value) {
            $this->_databaseData[$column] = $value;
        }

        /** Update Model Key */
        $this->updateModelKey($this->getDatabaseData($this->getKeyColumns()));
    }

    /**
     * OTHER
     */

    /**
     * Casts all non-NULL values to STRING or BOOLEAN.
     * @param {string} $column
     * @param {mixed} $value <= THIS can be passed as EXTERNAL REFERENCE.
     * @return { string | bool | null }
     */
    protected function dataTypeCast($column, $value) {
        /* NULL values are NOT converted */
        if ($value === NULL) {
            return NULL;
        }

        if ($this->isBooleanColumn($column)) {
            $value = $this->toBoolean($value, $column);
            return $value;
        }

        $value = (string) $value;
        return $value;
    }


    /**
     * Checks whenever value is boolean type
     * @param {string} column
     * @return {bool}
     */
    protected function isBooleanColumn($column) {
        return in_array($column, $this->getBooleanColumns());
    }

    /**
     * Removes all data from model-database-array storage
     */
    protected function clearDatabaseData() {
        $this->_databaseData = array();
    }

    /**
     * Loads the model data from database.
     * This function takes effect only if model is not yet loaded.
     * @return self
     */
    public function load() {
        if ( ! $this->isSynchronized()) {
            $this->dbSelect();
        }

        return $this;
    }

    /**
     * Reload model data from database.
     * @return self
     */
    public function reload(){
        /** Removes all modified Data */
        $this->clearModifiedData();

        /** Removes all stored data */
        $this->clearDatabaseData();

        $this->setSynchronized(false);
        return $this->load();
    }

    /**
     * To use in ::setKey() and updateKey() only;
     * Convert simple key to array.
     * @param {mixed}
     * @return {array}
     */
    private function _keyToArray($key) {
        /** Key is already an array */
        if (is_array($key)) {
            return $key;
        }

        $keyColumns = $this->getKeyColumns();

        /** Convert simple key to array */
        if (count($keyColumns) === 1) {
            return array(
                $keyColumns[0] => $key
            );
        }

        $this->throwException('Can not convert value to compound key in model');
    }

    /**
     * To use in ::setKey() only.
     * Tests if given key is valid.
     * Throws exception if not.
     * @param array $keyArr
     * @throws Exception
     */
    private function _keyThrowExceptionIfNotValid(array $keyArr) {

        /** Key Length Check */
        if (count($this->getKeyColumns()) !== count($keyArr)) {
            throw new Kohana_Exception("Key length doesn't match.");
        }

        /** If key length match */
        foreach ($this->getKeyColumns() as $expectedKeyColumn) {
            /** Check if given key columns matches model key columns */
            if (!array_key_exists($expectedKeyColumn, $keyArr)) {
                $this->throwException("Expected key column  '{$expectedKeyColumn}' not found in given key.");
            }

            /** Only one NULL value is allowed, and only for AI column */
            if ($keyArr[$expectedKeyColumn] === NULL && $this->getAutoIncrmentColumn() !== $expectedKeyColumn) {
                $this->throwException("Column  '{$expectedKeyColumn}' contains NULL, though it is Auto-Increment.");
            }
        }

        /** Are all columns valid ? */
        if (!$this->isValidData($keyArr)) {
            $errors = implode(',', $this->getErrors());
            $this->throwException("Invalid key: ({$errors})");
        }
    }

    /**
     * Convert value to boolean
     * @param {int | string | bool} value
     * @return {bool}
     * @throws Exception
     */
    protected function toBoolean($value, $column = null) {
        if (in_array($value, array(1, '1', true), true)) {
            return true;
        } else if (in_array($value, array(0, '0', false), true)) {
            return false;
        }
        if ($column) {
            $this->throwException("Can not convert $value to boolean for column ($column)");
        }
        $this->throwException("Can not convert $value to boolean");
    }

    /**
     * Loads data from Database
     * @global {bool} $_exists;
     * @return {bool} TRUE if row exists, FALSE otherwise
     * @throws Exception
     */
    protected function dbSelect() {
        if ($this->isSynchronized()) {
            $this->throwException('Can not call ::dbSelect() twice');
        }

        foreach ($this->getKey() as $column => $value) {
            /** Check for NULL values */
            if (NULL === $value) {
                $this->throwException("Model key is ambiguous, unexpected NULL found for {$column}");
            }
        }

        /** Save current query */
        $query = $this->_dbCompileRowSelectQuery($this->getTableName(), $this->getColumns(), $this->getKey(), $this->getDeletedAtColumn());
        $this->setLastQuery($query);

        /** DATABASE ACTION */
        $rows = $this->_dbSelectRowByQuery($query);

        /** We expect one or zero rows */
        if (count($rows) >= 2) {
            $this->throwException("Multiple rows found, query ({$query})");
        }

        /** None rows found, model has not yet been stored in the Database */
        if (empty($rows)) {
            $this->setExists(false); // if model exists this flag will be set elsewhere
            $this->setSynchronized(true); // if model exists this flag will be set elsewhere
            return FALSE;
        }

        $this->setDatabaseData($rows[0]);
        return TRUE;
    }

    /**
     *  Saves model data by performing insert (if model doesn't exist) or update (if it does).
     */
    public function save() {
        return $this->exists() ? $this->update() : $this->insert();
    }

    /**
     * Tests if key was changed and checks against isKeyChangeAllowed flag.
     * @return {bool} TRUE if key change is allowed, FALSE otherwise.
     * @throws Exception if key change is not allowed AND key was modified.
     */
    protected function keyChangeApprovalTest($opperation = "DATABASE CALL") {
        /** Was key changed? */
        $modifiedData = $this->getModifiedData();
        $key = $this->getKey();
        $isKeyChangeAllowed = $this->isKeyChangeAllowed();

        if (array_intersect_key($key, $modifiedData) && !$isKeyChangeAllowed) {
            $this->throwException("{$opperation} failed, key was modified");
        }
        return $isKeyChangeAllowed;
    }

    /**
     * Perform INSERT opperation.
     * @return NULL
     * @throws Exception if model validation test failed.
     */
    public function insert() {
        if ($this->exists()) {
            $this->throwException('Model already exists, INSERT fail');
        }

        /** Was key changed? */
        $this->keyChangeApprovalTest('INSERT');

        if (!$this->isValid()) {
            $errors = implode(',', $this->getErrors());
            $this->throwException("Validation failed on INSERT ($errors)");
        }

        /** Get Data */
        $data = $this->getArrayUnfiltered($this->getColumns());

        /** Created Timestamp */
        if ($this->getCreatedTimestampColumn()) {
            $data[$this->getCreatedTimestampColumn()] = @time();
        }

        /** Save current query */
        $query = $this->_dbCompileRowInsertQuery($this->getTableName(), $data);
        $this->setLastQuery($query);

        /** DATABASE ACTION */
        $lastInsertedID = $this->_dbInsertRowByQuery($query);

        /* Fill auto-incremented column data */
        if ($this->getAutoIncrmentColumn()) {
            $data[$this->getAutoIncrmentColumn()] = $lastInsertedID;
        }

        $this->clearModifiedData();
        $this->setDatabaseData($data);

        /** Run ON INSERT and ON SAVE callbacks */
        $this->onInsert();
        $this->onSave();

        /** Those flags are set in setDatabaseData() */
        //$this->setSynchronized(true);
        //$this->setExists(true);
    }

    /**
     * Perform UPDATE opperation.
     * @return {bool}
     * @throws Exception if model validation test failed.
     */
    public function update() {
        /** Was key changed? */
        $this->keyChangeApprovalTest('UPDATE');

        if (!$this->exists()) {
            $this->throwException("Model do not exists, UPDATE fail");
        }

        if (!$this->isValid()) {
            $errors = implode(',', $this->getErrors());
            $this->throwException("Validation failed on UPDATE ($errors)");
        }

        /** Get modified data */
        $modifiedData = $this->getModifiedData();
        if (!$modifiedData) {
            return FALSE;  // nothing to change
        }

        /** Test if all modified elements can be updated */
        foreach ($modifiedData as $modifiedColumn => $modifiedValue) {
            if (!in_array($modifiedColumn, $this->getUpdateableColumns())) {
                $this->throwException("Column ($modifiedColumn) can not be updated");
            }
        }

        /** UPDATED Timestamp */
        if ($this->getUpdatedTimestampColumn()) {
            $modifiedData[$this->getUpdatedTimestampColumn()] = @time();
        }

        /** UPDATED Count */
        if ($this->getUpdateCountColumn()) {
            $modifiedData[$this->getUpdateCountColumn()] ++;
        }

        /** Save current query */
        $query = $this->_dbCompileRowUpdateQuery($this->getTableName(), $modifiedData, $this->getKey());
        $this->setLastQuery($query);

        /** DATABASE ACTION */
        $affectedRows = $this->_dbUpdateRowByQuery($query);
        if ($affectedRows !== 1) {
            $this->throwException("UPDATE $affectedRows rows affected");
        }

        /** Move data to database-data array */
        $this->updateDatabaseData($modifiedData);

        /** Clear data array */
        $this->clearModifiedData();

        /** Run ON UPDATE and ON SAVE callbacks */
        $this->onUpdate();
        $this->onSave();

        return TRUE;
    }

    /**
     * Delete model, throw exception if deleted model count is different than "1";
     */
    public function delete() {
        $this->_delete(false);
        return $this;
    }

    /**
     * Delete model, do not throw exception if deleted model count is different than "1";
     */
    public function deleteIgnore() {
        $this->_delete(true);
        return $this;
    }

    /**
     * Perform DELETE opperation. Remove value from auto-incremented column.
     * @param {bool} for TRUE it throw exception if deleted row count is different then "1";
     * @return {bool}
     */
    protected function _delete($deleteIgnore = false) {
        /** If not yet saved... */
        if (!$this->exists()) {
            return FALSE; //nothing to delete
        }

        /** Was key changed? */
        if($this->keyChangeApprovalTest('DELETE')){
            $modelKeyArr = $this->getKey();
            foreach($this->getModifiedData() as $column => $value){
                if($this->isKeyColumn($column)){
                    $modelKeyArr[$column] = $value;
                }
            }
            $this->updateModelKey($modelKeyArr);
        }

        /** Stash model data */
        $dataStash = $this->removeKeyFromData($this->getArrayUnfiltered());

        /** Run ON DELETE callback */
        $this->onDelete();

        /** Delete by REMOVING row */
        if ($this->getDeletedAtColumn() === null) {
            /** Save current query */
            $query = $this->_dbCompileRowDeleteQuery($this->getTableName(), $this->getKey());
            $this->setLastQuery($query);

            /** DATABASE ACTION */
            $affectedRows = $this->_dbDeleteRowByQuery($query);

            if ($affectedRows !== 1 && $deleteIgnore === false) {
                $this->throwException("DELETE $affectedRows rows affected");
            }
        }

        /** Delete by TAGGING row as DELETED */
        if ($this->getDeletedAtColumn() !== null) {
            /** Save current query */
            $query = $this->_dbCompileRowUpdateQuery($this->getTableName(), [$this->getDeletedAtColumn() => @time()], $this->getKey());
            $this->setLastQuery($query);

            /** DATABASE ACTION */
            $affectedRows = $this->_dbUpdateRowByQuery($query);

            if ($affectedRows !== 1 && $deleteIgnore === false) {
                $this->throwException("DELETE $affectedRows rows affected");
            }
        }

        /** Mark model as NOT EXISTS and SYNCHRONIZED */
        $this->setSynchronized(true);
        $this->setExists(false);

        /** Removes all stored data */
        $this->clearDatabaseData();
        /** Removes all modified Data */
        $this->clearModifiedData();

        /** Restore model data */
        $this->setModifiedData($dataStash);

        return TRUE;
    }

    /**
     * FACTORY  &&  FIND
     */

    /**
     * Factory
     * @param {string} $modelClass
     * @param {array|string|int} $modelMixedKey
     * @return \mo-delName
     */
    public static function factory($modelClass, $modelMixedKey) {
        $model = self::instance($modelClass);
        $model->setKey($modelMixedKey);
        return $model;
    }

    /**
     * Return an empty instance of model.
     * @param {string} $modelClass
     * @return {object}
     */
    public static function instance($modelClass) {
        /** Add Model_ prefix to given name */
        if (strpos($modelClass, 'Model_') !== 0)
            $modelClass = 'Model_' . $modelClass;

        return (new $modelClass);
    }

    /**
     * Find
     * @param {string} $modelName
     * @param {array|string|int} $modelMixedKey
     * @return \mo-delName
     */
    public static function find($modelClass) {
        return (new DAO_Model_Collection($modelClass));
    }

}
