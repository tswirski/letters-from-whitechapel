<?php

/**
 * Data Access Objects
 * Framework: Kohana
 * Framework version: 3.2 (tested)
 */
abstract class DAO_Model extends DAO_Model_Forms
{
    /** @var {array} Data as set by the user */
    private $_data = array();

    /** @var {array} list of getter names */
    protected $_getters = array();

    /** @var {array} list of setter names */
    protected $_setters = array();

    /** @var {object|null} Kohana validation object */
    protected $_lastValidation;

    /** CONSTRUCTOR */
    protected function __construct()
    {
        parent::__construct();
        $this->_getters = $this->listGetters();
        $this->_setters = $this->listSetters();

        /** COLUMN may not be overridden by GETTER */
        $getterColumnConflict = array_intersect($this->getGetters(), $this->getColumns());
        if (!empty($getterColumnConflict)) {
            $getterColumnConflict = implode(',', $getterColumnConflict);
            $this->throwException('One or more columns are overridden by getter function. Conflicted names are: (' . $getterColumnConflict . ')');
        }

        /** COLUMN may not be overridden by SETTER */
        $setterColumnConflict = array_intersect($this->getSetters(), $this->getColumns());
        if (!empty($setterColumnConflict)) {
            $setterColumnConflict = implode(',', $setterColumnConflict);
            $this->throwException('One or more columns are overridden by setter function. Conflicted names are: (' . $setterColumnConflict . ')');
        }

        /** Booleans can be defined for COLUMNS only */
        $booleanColumnsDiff = array_diff($this->getBooleanColumns(), $this->getColumns());
        if ($booleanColumnsDiff) {
            $booleanColumnsDiff = implode(',', $booleanColumnsDiff);
            $this->throwException('Unmapped boolean columns (' . $booleanColumnsDiff . ')');
        }

        /** Input Filters can be defined for COLUMNS only */
        $inFilterColumnsDiff = array_diff(array_keys($this->getInputFilter()), $this->getColumns());
        if ($inFilterColumnsDiff) {
            $inFilterColumnsDiff = implode(',', $inFilterColumnsDiff);
            $this->throwException('Unmapped input filters (' . $inFilterColumnsDiff . ')');
        }

        /** Output Filters can be defined for COLUMNS only */
        $outFilterColumnsDiff = array_diff(array_keys($this->getOutputFilter()), $this->getColumns());
        if ($outFilterColumnsDiff) {
            $outFilterColumnsDiff = implode(',', $outFilterColumnsDiff);
            $this->throwException('Unmapped output filters (' . $outFilterColumnsDiff . ')');
        }
    }

    /**
     * Create a list of getter names
     * @return array
     */
    private function listGetters()
    {
        $getters = array();
        $model_methods = get_class_methods($this);
        $abstraction_methods = get_class_methods('DAO');
        $model_methods = array_diff($model_methods, $abstraction_methods);

        foreach ($model_methods as $method) {
            if (preg_match('/^get_(\w+)$/', $method, $match)) {
                $getters[] = $match[1];
            }
        }
        return $getters;
    }

    /**
     * List of getters
     * @return array
     */
    private function getGetters()
    {
        return $this->_getters;
    }

    /**
     * @param {string}
     * @return {bool} TRUE if model has getter with given name, FALSE otherwise
     */
    protected function isGetter($getterName)
    {
        return in_array((string)$getterName, $this->getGetters());
    }

    /**
     * Create a list of setter names
     * @return array
     */
    private function listSetters()
    {
        $setters = array();
        $model_methods = get_class_methods($this);
        $abstraction_methods = get_class_methods('DAO');
        $model_methods = array_diff($model_methods, $abstraction_methods);

        foreach ($model_methods as $method) {
            if (preg_match('/^set_(\w+)$/', $method, $match)) {
                $setters[] = $match[1];
            }
        }
        return $setters;
    }

    /**
     * Returns model file name
     * @return {string}
     */
    protected function getModel()
    {
        return get_class($this);
    }

    /**
     * List of getters
     * @return array
     */
    private function getSetters()
    {
        return $this->_setters;
    }

    /**
     * @param {string}
     * @return {bool} TRUE if model has setter with given name, FALSE otherwise
     */
    protected function isSetter($setterName)
    {
        return in_array((string)$setterName, $this->getSetters());
    }

    /**
     * Checks if value assigned to column name is empty.
     * @param {string} $column
     * @returns {bool}
     */
    public function isEmpty($column)
    {
        $element = $this->getUnfiltered($column);
        return empty($element);
    }

    /**
     * Checks if column has value equal to given
     * @param {string} $column
     * @param {mixed} $value
     * @returns {boolean} TRUE if column value is equal to $value, FALSE otherwise.
     */
    public function equal($column, $value)
    {
        return $this->get($column) === $value;
    }

    /**
     * Checks if column has value different to given
     * @param {string} $column
     * @param {mixed} $value
     * @returns {boolean} TRUE if column value is different to $value, FALSE otherwise.
     */
    public function notEqual($column, $value)
    {
        return $this->get($column) !== $value;
    }

    /**
     * Takes column name or list of column names.
     * Returns TRUE if any of given columns ware modified.
     * If no column given then test is made agains whole model.
     * @param type $col
     */
    public function isModified($col = NULL)
    {
        /** Check all fields */
        if (NULL === $col) {
            return !empty($this->_data);
        }

        /** If single column given as string ... */
        if (is_string($col)) {
            return array_key_exists($col, $this->_data);
        }

        /** Otherwise we expect an array */
        if (!is_array($col)) {
            $this->throwException("Invalid param");
        }
        /** Check for any modifications for given columns */
        foreach ($col as $c) {
            if (array_key_exists($c, $this->_data)) {
                return true;
            }
        }
    }

    /**
     * Apply INPUT filter for given column and update (by-reference-given) value.
     * If no filter was set for column, this function will have no effect.
     * @param {string} $column
     * @param {mixed} &$value
     * @return {null}
     */
    protected function applyInputFilter($column, &$value)
    {
        if (isset($this->defineInputFilter()[$column])) {
            $callable = $this->defineInputFilter()[$column];
            $value = $callable($value);
        }
    }

    /**
     * Apply OUTPUT filter for given column and update (by-reference-given) value.
     * If no filter was set for column, this function will have no effect.
     * @param {string} $column
     * @param {mixed} &$value
     * @return {null}
     */
    protected function applyOutputFilter($column, &$value)
    {
        if (isset($this->defineOutputFilter()[$column])) {
            $callable = $this->defineOutputFilter()[$column];
            $value = $callable($value);
        }
    }

    /** Magic Setter */
    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    /**
     * Sets the value by column or by setter
     * @param {string} column (real or virtual)
     * @param {mixed} value
     */
    public function set($column, $value)
    {
        if ($this->isColumn($column)) {
            /** Apply input filter */
            $this->applyInputFilter($column, $value);
            /** Type Cast Value */
            $this->_data[$column] = $this->dataTypeCast($column, $value);
            return;
        }

        if ($this->isSetter($column)) {
            $method = "set_$column";
            $this->$method($value);
            return;
        }
        $this->throwException("Can not set value for '{$column}'");
    }

    /**
     * Gets all modified data
     * @returns {array} _data
     */
    protected function getModifiedData()
    {
        return array_diff_assoc($this->_data, $this->getDatabaseData());
    }

    /**
     * Removes all unsaved data from model context
     */
    protected function clearModifiedData()
    {
        $this->_data = array();
    }

    /**
     * Set raw data to 'modifiedData' table.
     * @param array $data
     * @return $this
     */
    protected function setModifiedData(array $data)
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * Allows model data to be set from array
     * @param {array}
     * @returns {null}
     * @throws Exception if any column was modificated more then once in one call.
     */
    public function setArray(array $data)
    {
        $referenceData = $this->getArrayUnfiltered($this->getColumns());
        $modifiedColumns = array();

        foreach ($data as $dataColumn => $dataValue) {
            $this->set($dataColumn, $dataValue);
            $currentData = $this->getArrayUnfiltered($this->getColumns());
            $affectedColumns = array_keys(
                array_diff_assoc($currentData, $referenceData)
            );
            foreach ($affectedColumns as $affectedColumn) {
                if (in_array($affectedColumn, $modifiedColumns)) {
                    $this->throwException("Column '$affectedColumn' changed more than once");
                }
                $modifiedColumns[] = $affectedColumn;
            }
            /** Update reference data */
            $referenceData = $currentData;
        }
    }

    /**
     * Magic Getter
     */
    public function __get($column)
    {
        return $this->get($column);
    }

    /**
     * Return unfiltered output for given column
     * @param string $column
     * @return {string | bool | null}
     */
    protected function getUnfiltered($column)
    {
        return $this->_get($column, false);
    }

    /**
     * Return filtered output for given column
     * @param string $column
     * @return {string | bool | null}
     */
    public function get($column) {
        return $this->_get($column, true);
    }

    /**
     * Get column value.
     * @param {string} columnName
     * @param {bool} TRUE to use output filter
     * @return {string | bool | null}
     */
    protected function _get($column, $filterOutput = false) {
        /** Load data from database if not yet loaded */
        $this->load();
        $output = null;

        /** get by Getter */
        if ($this->isGetter($column)) {
            $method = "get_$column";
            return $this->$method();
        }

        /** No such column in model */
        if (!$this->isColumn($column)) {
            throw new Exception("Can not access column or virtual property ($column)");
        }

        /** Modified data */
        if (array_key_exists($column, $this->getModifiedData())) {
            $output = $this->getModifiedData()[$column];
        }

        /** If exists */
        else if ($this->exists()) {
            $output = $this->getDatabaseColumnData($column);
        }

        /** If its a key column */
        else if ($this->isKeyColumn($column)) {
            $output = $this->getKey()[$column];
        }

        else {
            /** Otherwise return default or null */
            $output = Arr::get($this->getDefaultValues(), $column);
        }

        /** Apply filters */
        if ($filterOutput == true) {
            $this->applyOutputFilter($column, $output);
            $output = $this->dataTypeCast($column, $output);
        }

        return $output;
    }

    /**
     * Get data as array unfiltered.
     * @param (array) limit output to those columns.
     * @return {array}
     */
    protected function getArrayUnfiltered(array $demandedColumns = NULL)
    {
        return $this->_getArray($demandedColumns, false);
    }

    /**
     * Get data as array filtered.
     * @param (array) limit output to those columns {array}
     * @return {array}
     */
    public function getArray(array $demandedColumns = NULL){
        return $this->_getArray($demandedColumns, true);
//        if($demandedColumns === null){
//            return $this->_getArray(null);
//        }
//
//        $_demandedColumns = [];
//        foreach($demandedColumns as $columnKey => $columnValue){
//            $_demandedColumns += is_integer($columnKey) ? $columnValue : $columnKey;
//        }
//
//        $data = $this->_getArray($_demandedColumns, true);
//
//        ...
//
//        zbadaæ ró¿nice miêdzy get() i getArray() !!
//        dodaæ mapowanie getErrors(error=>anotherErroName)
    }

    /**
     * Get model columns & virtuals as array.
     * @param (array) $demandedColumns limits returned elements to those...
     * @return {array}
     * @throws Exception if requested column doesn't exist.
     */
    protected function _getArray(array $demandedColumns = NULL, $filterOutput = false)
    {
        /** Load data from database if not yet loaded */
        $this->load();
        $output = array();

        if ($demandedColumns === NULL) {
            $demandedColumns = array_merge($this->getGetters(), $this->getColumns());
        }

        foreach($demandedColumns as $columnKey => $column){
            $output[is_integer($columnKey) ? $column : $columnKey] = $this->get($column, $filterOutput);
        }

        return $output;
    }

    /**
     * Checks if model is valid, returns TRUE for yes or FALSE for no.
     * For access to validation object call ->getValidationObject().
     * Also you can call ->getValidationErrors($i18nTranslationFileName) to get
     * translated errors of last validation.
     * @return {bool}
     */
    public function isValid()
    {
        return $this->isValidData($this->getArrayUnfiltered($this->getColumns()));
    }

    /**
     * Checks if data is valid, returns TRUE for yes or FALSE for no.
     * For access to validation object call ->getValidationObject().
     * Also you can call ->getValidationErrors($i18nTranslationFileName) to get
     * translated errors of last validation.
     * @return {bool}
     */
    public function isValidData(array $data)
    {
        $validation = Validation::factory($data);
        foreach ($this->getValidationRules() as $column => $validationRules) {

            /** validate only fields existsing in data array */
            if (!array_key_exists($column, $data)) {
                continue;
            }

            foreach ($validationRules as $dummyOrRule => $ruleOrParams) {
                if (is_array($ruleOrParams)) {
                    $validation->rule($column, $dummyOrRule, $ruleOrParams);
                } else {
                    $validation->rule($column, $ruleOrParams);
                }
            }
        }
        $this->_lastValidation = $validation;
        return $this->_lastValidation->check();
    }

    /**
     * Returns errors of last validation or empty array.
     * @param (string) $lang
     * @return array
     */
    public final function getErrors($lang = NULL)
    {
        if (is_null($lang)) {
            $lang = i18n::lang();
        }

        if (!$this->_lastValidation) {
            return array();
        }

        return $this->_lastValidation->errors($lang);
    }
}