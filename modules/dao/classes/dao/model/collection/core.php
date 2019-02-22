<?php

class DAO_Model_Collection_Core {

    const CONFIG_COLUMNS = 'COLUMNS';
    const CONFIG_TABLE = 'TABLE';
    const CONFIG_KEY_COLUMNS = 'KEY_COLUMNS';
    const CONFIG_DELETE_AT_COLUMN = 'DELETE_AT';

    /** @var {string} Base Model Alias* */
    private $_primaryModelAlias;

    /** @var {array} Map Model-Alias to Model-Class */
    private $_modelClassAliasMap = [];

    /** @var {array} Map where model class points to configuration array having two keys:
     * TABLE and COLUMNS.  TABLE holds {string} table name, and COLUMNS holds {array} list of columns.
     */
    private $_modelToConfigMap = [];

    /** @var {object} query builder object */
    private $_queryBuilder;

    /** @var {object} data base object */
    private $_database;

    /** @var {bool} set to true to indicate IN [] opperation */
    protected $_clauseInEmptyArray = false;

    /**
     * Fix model class name.
     * @param {string} & $modelName
     */
    protected static function fixModelClassName(&$modelName) {
        /** Add Model_ prefix to given name */
        if (strpos($modelName, 'Model_') !== 0)
            $modelName = 'Model_' . $modelName;
        return $modelName;
    }

    /**
     * Throw custom exception.
     * @param {string} message
     * @throws {Exception} default or framework-based.
     */
    protected function throwException($message) {
        throw new Kohana_Exception('DAO | ' . $message);
    }

    /**
     * Get Query Builder Object
     * @return {object}
     */
    public function queryBuilder() {
        return $this->_queryBuilder;
    }

    /**
     * Get Database Object
     * @return {object}
     */
    public function getDatabase() {
        return $this->_database;
    }

    /**
     * @return {string} database query
     */
    public function getQuery() {
        return $this->queryBuilder()->compile($this->getDatabase());
    }

    /**
     * Takes data row and returns data for selected (by alias) model.
     * @param {string} $alias
     * @param {array} $data
     * @return {array}
     */
    protected function extractAliasedData($alias, array $data) {
        $output = [];
        foreach ($data as $column => $value) {
            if (strpos($column, $alias) === 0) {
                $column = substr($column, strlen($alias) + 1);
                $output[$column] = $value;
            }
        }
        return $output;
    }

    /**
     * Extract model key from model data
     * @param {string} $modelClass
     * @param {array} $data
     * @return {array}
     */
    protected function extractModelKey($modelClass, $data) {
        $output = [];
        $modelKeyColumns = $this->getModelKeyColumns($modelClass);
        foreach ($modelKeyColumns as $keyColumn) {
            $output[$keyColumn] = $data[$keyColumn];
        }
        return $output;
    }

    /**
     * Create result for single-table search.
     * / or extract data for specfied alias.
     * @return {array} list of models
     */
    protected function _produceSingleTableSearchResults($databaseRows, $modelAlias = null) {
        if ($modelAlias === null) {
            $modelAlias = $this->getPrimaryModelAlias();
        }

        $modelClass = $this->getModelClassByAlias($modelAlias);
        $output = [];

        foreach ($databaseRows as $databaseRow) {
            $data = $this->extractAliasedData($modelAlias, $databaseRow);
            $key = $this->extractModelKey($modelClass, $data);
            $model = DAO::factory($modelClass, $key);

            /** Store Database Data. Hack into private method */
            $modelSetDatabaseData = new ReflectionMethod($modelClass, 'setDatabaseData');
            $modelSetDatabaseData->setAccessible(true);
            $modelSetDatabaseData->invoke($model, $data);

            $output[] = $model;
        }
        return $output;
    }

    /**
     * Create result for joined-tables search.
     * @param {array} $databaseRows
     * @param {null | string} $extractAlias - extract model by alias (string) from joined-table response.
     * @return {array} list maps
     */
    protected function _produceJoinedTablesSearchResults($databaseRows, $extractAlias) {
        $extractAlias = (string) $extractAlias;
        $aliases = array_keys($this->getAliasMap());
        $output = [];

        /** Extract single model */
        if ($extractAlias) {
            if (!in_array($extractAlias, $aliases)) {
                $this->throwException("unknown alias '$extractAlias'");
            }
            return $this->_produceSingleTableSearchResults($databaseRows, $extractAlias);
        }

        /** Get multiple models */
        foreach ($databaseRows as $databaseRow) {
            $outputRow = [];
            foreach ($aliases as $modelAlias) {
                $modelClass = $this->getModelClassByAlias($modelAlias);

                $data = $this->extractAliasedData($modelAlias, $databaseRow);
                $key = $this->extractModelKey($modelClass, $data);
                $model = DAO::factory($modelClass, $key);

                /** Store Database Data. Hack into private method */
                $modelSetDatabaseData = new ReflectionMethod($modelClass, 'setDatabaseData');
                $modelSetDatabaseData->setAccessible(true);
                $modelSetDatabaseData->invoke($model, $data);

                $outputRow[$modelAlias] = $model;
            }
            $output [] = $outputRow;
        }
        return $output;
    }

    /**
     * Execute query, get models
     * @return {array}
     */
    protected function execute($extractAlias = null) {

        if ($this->_clauseInEmptyArray === true) {
            return [];
        }

        /** Result array */
        $output = [];
        $databaseRows = $this->queryBuilder()->execute($this->getDatabase())->as_array();

        /** Single table search */
        if ($this->isSingleTableQuery()) {
            if ($extractAlias !== null) {
                $this->throwException('Can not extract Model-by-Alias for single-table search');
            }
            return $this->_produceSingleTableSearchResults($databaseRows);
        }

        /** Search in joined tables */
        return $this->_produceJoinedTablesSearchResults($databaseRows, $extractAlias);
    }

    /**
     * Constructor
     * @param type $modelClass
     */
    public function __construct($modelClass) {

        /** Assign Data Base */
        $databaseName = Kohana::$config->load('dao')->get('database', 'default');
        $this->_database = Database::instance($databaseName);

        /** Assign Query Builder */
        $this->_queryBuilder = DB::select();

        /** Register model in module */
        $this->registerModel($modelClass, $modelAlias);

        /** Save model class name as PRIMARY alias */
        $this->_primaryModelAlias = $modelAlias;

        /** Get TABLE for model */
        $table = $this->getTableForModel($modelClass);

        /** QUERY - set FROM */
        $this->queryBuilder()->from([$table, $modelAlias]);
    }

    /**
     * Returns PRIMARY Model Alias
     * @return string
     */
    protected function getPrimaryModelAlias() {
        return $this->_primaryModelAlias;
    }

    /**
     * Returns PRIMARY Model Class
     * @return string
     */
    protected function getPrimaryModelClass() {
        return $this->getModelClassByAlias($this->getPrimaryModelAlias());
    }

    /**
     * Returns Alias - to - Model Class map.
     * @return {array}
     */
    protected function getAliasMap() {
        return $this->_modelClassAliasMap;
    }

    /**
     * Returns Configuration for model
     * @param {string} $modelClass
     * @return {array}
     * @throws Exception for unregistered model classes.
     */
    protected function getConfig($modelClass) {
        $config = Arr::get($this->_modelToConfigMap, $modelClass);
        if ($config === null) {
            $this->throwException("Model ($modelClass) REFLECTION config not found.");
        }
        return $config;
    }

    /**
     * Returns Model Class by Alias
     * @param {string} $modelAlias
     * @return {string} $modelClass
     * @throws Exception for unassigned aliases
     */
    protected function getModelClassByAlias($modelAlias) {
        $modelClass = Arr::get($this->getAliasMap(), $modelAlias);
        if ($modelClass === null) {
            $this->throwException("Alias ($modelAlias) not assigned to any model");
        }
        return $modelClass;
    }

    /**
     * Returns Table Name for given Model Class.
     * @param {string} $modelClass
     * @return {string} tableName
     * @throws Exception model does not exist
     */
    protected function getTableForModel($modelClass) {
        $config = $this->getConfig($modelClass);
        return Arr::get($config, self::CONFIG_TABLE);
    }

    /**
     * Returns Column list for given Model Class
     * @param {string} $modelClass
     * @return {array} List of columns
     * @throws Exception if model does not exist
     */
    protected function getColumnsForModel($modelClass) {
        $config = $this->getConfig($modelClass);
        return Arr::get($config, self::CONFIG_COLUMNS);
    }

    /**
     * Return model key-column list.
     * @param {string} $modelClass
     * @return {array}
     */
    protected function getModelKeyColumns($modelClass) {
        $config = $this->getConfig($modelClass);
        return Arr::get($config, self::CONFIG_KEY_COLUMNS);
    }

    /**
     * Returns TRUE if single model involved in query, FALSE for multiple models (joined)
     * @return {bool}
     */
    protected function isSingleTableQuery() {
        return count($this->getAliasMap()) === 1;
    }

    /**
     * Returns TRUE if alias already assigned to model, FALSE otherwise.
     * @param {string} $modelAlias
     * @return {bool}
     */
    protected function isRegisteredAlias($modelAlias) {
        return isset($this->_modelClassAliasMap[$modelAlias]);
    }

    /**
     * Register MODEL to the module.
     * @param {string} $modelClass
     * @param {string} $modelAlias
     * @throws {exception} for duplicated alias or model names.
     * @return {null}
     */
    protected final function registerModel(&$modelClass, &$modelAlias = null) {
        /** If no alias given - use class name */
        if ($modelAlias === null) {
            $modelAlias = $modelClass;
        }

        /** Model class has to have Model_ prefix */
        self::fixModelClassName($modelClass);

        /** If model with given classname/alias already registered */
        if ($this->isRegisteredAlias($modelAlias)) {
            $this->throwException("[{$modelAlias}] Already exists");
        }

        /** Save alias - model class relation */
        $this->_modelClassAliasMap[$modelAlias] = $modelClass;

        /** Create TABLE and COLUMNS configuration */
        if (!isset($this->_modelToConfigMap[$modelClass])) {
            /** instance EMPTY model */
            $model = DAO::instance($modelClass);

            /** Create Method Reflections */
            $model_COLUMNS = new ReflectionMethod($modelClass, 'getColumns');
            $model_TABLE = new ReflectionMethod($modelClass, 'getTableName');
            $model_KEY_COLUMNS = new ReflectionMethod($modelClass, 'getKeyColumns');
            $model_DELETE_AT = new ReflectionMethod($modelClass, 'getDeletedAtColumn');

            /** Allow calling PRIVATE / PROTECTED methods */
            $model_COLUMNS->setAccessible(true);
            $model_TABLE->setAccessible(true);
            $model_KEY_COLUMNS->setAccessible(true);
            $model_DELETE_AT->setAccessible(true);

            $this->_modelToConfigMap[$modelClass][self::CONFIG_COLUMNS] = $model_COLUMNS->invoke($model);
            $this->_modelToConfigMap[$modelClass][self::CONFIG_TABLE] = $model_TABLE->invoke($model);
            $this->_modelToConfigMap[$modelClass][self::CONFIG_KEY_COLUMNS] = $model_KEY_COLUMNS->invoke($model);
            $this->_modelToConfigMap[$modelClass][self::CONFIG_DELETE_AT_COLUMN] = $model_DELETE_AT->invoke($model);
        }

        /** Add Model to Query */
        foreach ($this->_modelToConfigMap[$modelClass][self::CONFIG_COLUMNS] as $column) {
            $this->queryBuilder()->select([$modelAlias . '.' . $column, $modelAlias . '.' . $column]);
        }

        $deletedAtColumn = $this->_modelToConfigMap[$modelClass][self::CONFIG_DELETE_AT_COLUMN];
        /** Remove DELETED 'by tag' elements from result set */
        if ($deletedAtColumn !== null) {
            $this->where([$modelAlias, $deletedAtColumn], 'IS', NULL);
        }
    }

}
