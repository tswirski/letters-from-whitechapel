<?php

abstract class DAO_Model_Abstraction extends DAO_Model_Environment {

    private $_tableName = null;

    /** Table Name getter */
    protected function getTableName() {
        if (!$this->_tableName) {
            /* ASSIGN TABLE NAME */
            $tableName = (string) $this->defineTableName();

            if (empty($tableName)) {
                $this->throwException('Table name can not be empty');
            }

            $this->_tableName = $tableName;
        }
        return $this->_tableName;
    }

    private $_keyColumns = array();

    /**
     * Model Unique Index getter
     * @return {array} List of key column names
     */
    protected function getKeyColumns() {
        return $this->_keyColumns;
    }

    private $_autoIncrementColumn = null;

    /** Auto-Increment column getter */
    protected function getAutoIncrmentColumn() {
        return $this->_autoIncrementColumn;
    }

    /**
     * Abstraction Layer Constructor
     */
    protected function __construct() {
        parent::__construct();

        /** Init Table Name */
        $this->getTableName();

        /** ASSING KEY AND AUTO INCREMENT COLUMN */
        $keyConfig = $this->getKeyConfig();

        if (!is_array($keyConfig)) {
            $this->throwException('Key is not array');
        }

        if (empty($keyConfig)) {
            $this->throwException('Key can not be empty');
        }

        $autoIncrementCount = 0;
        foreach ($keyConfig as $key => $value) {
            if ($value === TRUE) {
                $autoIncrementCount ++;

                if ($autoIncrementCount > 1) {
                    $this->throwException('More then one auto increment column found');
                }

                $this->_autoIncrementColumn = $key;
                $this->_keyColumns[] = $key;
            } else {
                $this->_keyColumns[] = $value;
            }
        }

        /** CONFIGURATION TEST */
        /** Key must be included in model COLUMNS */
        $keyColumnsDiff = array_diff($this->getKeyColumns(), $this->getColumns());
        if ($keyColumnsDiff) {
            $keyColumnsDiff = implode(',', $keyColumnsDiff);
            $this->throwException('Unmapped key columns (' . $keyColumnsDiff . ')');
        }

        /** Updateables can be defined for COLUMNS only */
        $updateableColumnsDiff = array_diff($this->getUpdateableColumns(), $this->getColumns());
        if ($updateableColumnsDiff) {
            $updateableColumnsDiff = implode(',', $updateableColumnsDiff);
            $this->throwException('Unmapped updateable columns (' . $updateableColumnsDiff . ')');
        }

        /** Deleted At Column (if defined) can not be included in COLUMNS */
        $deletedAtColumn = $this->getDeletedAtColumn();
        if ($deletedAtColumn !== null && in_array($deletedAtColumn, $this->getColumns())) {
            $this->throwException('Deleted-At-Column should not be included in model columns');
        }

        if (!is_array($this->getValidationRules())) {
            $this->throwException("defineValidation should return array");
        }

        /** All of key columns must be included in validation rules */
        $keyValidationRulesDiff = array_diff($this->getKeyColumns(), array_keys($this->getValidationRules()));
        if ($keyValidationRulesDiff) {
            $keyValidationRulesDiff = implode(',', $keyValidationRulesDiff);
            $this->throwException('Key has no validation rules for columns (' . $keyValidationRulesDiff . ')');
        }

        /** Default values can be set for COLUMNS only */
        $defaultValuesDiff = array_diff(array_keys($this->getDefaultValues()), $this->getColumns());
        if ($defaultValuesDiff) {
            $defaultValuesDiff = implode(',', $defaultValuesDiff);
            $this->throwException('Unmapped key columns (' . $defaultValuesDiff . ')');
        }

        /** CREATED timestamp column must be included in model columns */
        if ($this->getCreatedTimestampColumn() !== NULL && !in_array($this->getCreatedTimestampColumn(), $this->getColumns())) {
            $this->throwException('Unmapped CREATED column');
        }

        /** UPDATED timestamp column must be included in model columns */
        if ($this->getUpdatedTimestampColumn() !== NULL && !in_array($this->getUpdatedTimestampColumn(), $this->getColumns())) {
            $this->throwException('Unmapped UPDATED column');
        }

        /** UPDATED count column must be included in model columns */
        if ($this->getUpdateCountColumn() !== NULL && !in_array($this->getUpdateCountColumn(), $this->getColumns())) {
            $this->throwException('Unmapped UPDATE COUNT column');
        }
    }

    /**
     * Model TABLE NAME configuration.
     * Default table name is equal to lowercased model name suffixed with 's'.
     * Example:  Model_Car_Part is corresponding to table 'car_parts'.
     * To use custom table name overwrite this function inside child class.
     * @returns {string}
     */
    protected function defineTableName() {
        $modelClassName = $this->getModel();

        if (strpos($modelClassName, 'Model_') !== 0) {
            $this->throwException('Model class name should starts with Model_');
        }

        $tableName = substr_replace($modelClassName, NULL, 0, 6);
        $tableName = strtolower($tableName);
        $tableName .= 's';

        return $tableName;
    }

    /**
     * Value for this column will be set automatically upon insert.
     * @return {string | null}
     */
    abstract protected function defineCreatedTimestampColumn();

    /** Created-Timestamp column getter */
    protected function getCreatedTimestampColumn() {
        return $this->defineCreatedTimestampColumn();
    }

    /**
     * Value for this column will be refreshed automatically upon update.
     * @return {string | null}
     */
    abstract protected function defineUpdatedTimestampColumn();

    /** Updated-Timestamp column getter */
    protected function getUpdatedTimestampColumn() {
        return $this->defineUpdatedTimestampColumn();
    }

    /**
     * Value for this column will be incremented automatically upon update
     * @return {string | null}
     */
    abstract protected function defineUpdateCountColumn();

    /** Created-Timestamp column getter */
    protected function getUpdateCountColumn() {
        return $this->defineUpdateCountColumn();
    }

    /**
     * Table's Key (unique index) configuration.
     * @returns {array} An array which describes how Table's Unique Index looks like.
     * The order of array elements shoud match INDEX of the table key.
     * All non-auto-incremented parts of table key are represented by value equal to column name.
     * If auto-increment part of table key is present then it is represented by key equal to column name with value of TRUE.
     * Example of MyISAM table configuration with key made of 3 columns where second column is auto-increment:
     * array('column_1', 'column_2'=>TRUE, 'column_3');
     */
    abstract protected function defineKey();

    /** Key config getter */
    private function getKeyConfig() {
        return $this->defineKey();
    }

    /**
     * Model COLUMN configuration.
     * @return {array} List of columns maintained by model.
     * @throws Exception if index-columns are missing.
     */
    abstract protected function defineColumns();

    protected function getColumns() {
        return $this->defineColumns();
    }

    /**
     * Model UPDATEABLE columns configuration.
     * @return {array} List of columns allowed to change  their  values.
     * (for existing rows)
     */
    abstract protected function defineUpdateableColumns();

    protected function getUpdateableColumns() {
        return $this->defineUpdateableColumns();
    }

    /**
     * Model BOOLEAN columns configuration.
     * Because MySQL stores BOOLEANS as INTEGERES [0,1] and INTEGERS are read as STRINGS
     * the on-the-fly conversion was introduced. While reading data from database '0' and 0 is converted to FALSE
     * also '1' or 1 is converted to TRUE. NULL remains unchanged. On SET (setting model attribute value) only
     * TRUE, FALSE and NULL are allowed. For any other Exception will be throwed.
     */
    abstract protected function defineBooleanColumns();

    protected function getBooleanColumns() {
        return $this->defineBooleanColumns();
    }

    /**
     * Model VALIDATION configuration.
     *
     * Validation takes place on ->import(), ->isValid*(), ->insert() and ->update();
     * While import(), and isValid*() are returning boolean to indicate if validation succeed of fail,
     * attempt to save (insert or update) model for which validation fails will couse Exeption throw.
     *
     * @returns {array} Map where key equals to column name and value is list of validation rules.
     * This feature is strongly based on Kohana Validation module which you should be familiar with at this point.
     * Example for model which has 3 fields, ID, NAME and PHONE would be:
     *  array(
     *      'id' => array('digit'),
     *      'name' => array(
     *          'not_empty',
     *          'max_length' => array(':value', 100)
     *          ,'alpha'
     *      ),
     *      'phone' => array('phone')
     * );
     */
    abstract protected function defineValidation();

    protected function getValidationRules() {
        return $this->defineValidation();
    }

    /**
     * Default Values
     */
    abstract protected function defineDefaultValues();

    protected function getDefaultValues() {
        return $this->defineDefaultValues();
    }

    /**
     * Set up Input filter.
     * @return {array} where key is equal to column name and value is callable function taking one
     * parameter (raw input value). The value set on the model is equal to whatever is returned from
     * filter function.
     */
    abstract protected function defineInputFilter();

    protected function getInputFilter() {
        return $this->defineInputFilter();
    }

    /**
     * Set up Output filter.
     * @return {array} where key is equal to column name and value is callable function taking one
     * parameter (raw output value). The value returned from the model is equal to whatever is returned from
     * filter function.
     */
    abstract protected function defineOutputFilter();

    protected function getOutputFilter() {
        return $this->defineOutputFilter();
    }

    /**
     * ON INSERT callback  (overwrite to use)
     * This function would be called JUST AFTER query database, upon INSERT opperation
     */
    protected function onInsert() {
        // nothig here :)
    }

    /**
     * ON UPDATE callback  (overwrite to use)
     * This function would be called JUST AFTER query database, upon UPDATE opperation
     */
    protected function onUpdate() {
        // nothig here :)
    }

    /**
     * ON SAVE callback  (overwrite to use)
     * This function would be called JUST AFTER query database, upon INSERT AND UPDATE opperations
     */
    protected function onSave() {
        // nothig here :)
    }

    /**
     * ON DELETE callback  (overwrite to use)
     * This function would be called JUST BEFORE query database, upon DELETE opperation
     */
    protected function onDelete() {
        // nothig here :)
    }

    /**
     * If DELETED AT is set, rows will be marked as deleted rather than removed from the database.
     * Column holding DELETED timestamp will be removed from output.
     * @return {string | null}
     */
    protected function defineDeletedAtColumn() {
        return null;
    }

    /** Deleted-Timestamp column getter */
    protected function getDeletedAtColumn() {
        return $this->defineDeletedAtColumn();
    }

}
