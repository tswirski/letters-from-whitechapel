<?php

class DAO_Model_Collection extends DAO_Model_Collection_Core {

    const IS_COLUMN = 1;
    const IS_OPERATOR = 2;
    const IS_VALUE = 3;

    public $queryBuilderMethodMap = [
        'on' => [self::IS_COLUMN, self::IS_OPERATOR, self::IS_COLUMN],
        'where' => [self::IS_COLUMN, self::IS_OPERATOR, self::IS_VALUE],
        'where_open' => [],
        'where_close' => [],
        'and_where' => [self::IS_COLUMN, self::IS_OPERATOR, self::IS_VALUE],
        'and_where_open' => [],
        'and_where_close' => [],
        'or_where' => [self::IS_COLUMN, self::IS_OPERATOR, self::IS_VALUE],
        'or_where_open' => [],
        'or_where_close' => [],
        'having' => [self::IS_COLUMN, self::IS_OPERATOR, self::IS_VALUE],
        'having_open' => [],
        'having_close' => [],
        'and_having' => [self::IS_COLUMN, self::IS_OPERATOR, self::IS_VALUE],
        'and_having_open' => [],
        'and_having_close' => [],
        'or_having' => [self::IS_COLUMN, self::IS_OPERATOR, self::IS_VALUE],
        'or_having_open' => [],
        'or_having_close' => [],
        'limit' => [self::IS_OPERATOR],
        'offset' => [self::IS_OPERATOR],
        'order_by' => [self::IS_COLUMN, self::IS_OPERATOR]
    ];

    /**
     * EXECUTE METHODS OF QUERY-BUILDER
     */
    public function __call($name, $arguments) {
        $actions = Arr::get($this->queryBuilderMethodMap, $name);
        if (!$actions) {
            $this->throwException('Method "' . $name . '" not found in DAO Collection');
        }

        /** Opperation */
        $opperation = null;

        foreach ($arguments as $i => $argument) {
            if (self::IS_COLUMN === $actions[$i]) {
                $arguments[$i] = $this->aliasPrefixColumn($argument);
                continue;
            }

            if (self::IS_OPERATOR === $actions[$i]) {
                $arguments[$i] = trim(strtoupper($arguments[$i]));
                $opperation = $arguments[$i];
            }

            if (self::IS_VALUE === $actions[$i]) {
                if (is_array($arguments[$i]) && empty($arguments[$i])) {

                    if ($opperation === 'NOT IN') {
                        return $this;
                    }

                    if ($opperation === 'IN') {
                        $this->_clauseInEmptyArray = true;
                        return $this;
                    }
                }
            }
        }

        call_user_func_array(array($this->queryBuilder(), $name), $arguments);
        return $this;
    }

    /**
     * @param {string} COLUMN or {array} [ALIAS, COLUMN]
     * if {string} param given, a base model would be used as ALIAS prefix.
     * @return {object} / ALIAS.COLUMN
     */
    protected function aliasPrefixColumn($stringOrArray) {
        $modelAlias = is_array($stringOrArray) ? $stringOrArray[0] : $this->getPrimaryModelAlias();
        $column = is_array($stringOrArray) ? $stringOrArray[1] : $stringOrArray;
        return DB::expr(implode('.', [$modelAlias, $column]));
    }

    /**
     * INNER JOIN model with optional ALIAS (for multiple joins to the same table).
     * @param {string} $modelClass
     * @param {string} $modelAlias
     */
    public function join($modelClass, $modelAlias = null) {
        $this->registerModel($modelClass, $modelAlias);
        $this->queryBuilder()->join([$this->getTableForModel($modelClass), $modelAlias]);
        return $this;
    }

    /**
     * Set WHERE as array
     * @param {array or string} $aliasOrData
     * @param (array) $data
     */
    public function whereArray($aliasOrData, array $data = null) {
        if (is_array($aliasOrData)) {
            $modelAlias = $this->getPrimaryModelAlias();
            $data = $aliasOrData;
        } else {
            $modelAlias = $aliasOrData;
        }

        foreach ($data as $column => $value) {
            $this->where([$modelAlias, $column], '=', $value);
        }

        return $this;
    }

    /**
     * Run "Select count (*)"
     * @return {int}
     */
    public function getCount() {
        /** Hack into PRIVATE property */
        $selectReplacer = function(Database_Query_Builder_Select $queryBuilder) {
            $queryBuilder->_select = array();
        };
        $selectReplacer = Closure::bind($selectReplacer, null, $this->queryBuilder());
        $selectReplacer($this->queryBuilder());

        return (int) $this->queryBuilder()->select(DB::expr('count(*) as count'))->execute()->current()['count'];
    }

    /**
     * Returns list of models (single table), or list of model collections (joined tables) where models are distinguished by its alias.
     * Can return list of models for joned tables by extracting {modelAlias} from the collections.
     * Returns an empty empty-array to indicate no-results.
     * @param {string} $extract
     * @return {array | array-array}
     * @throws Exception if model alias given for single table query.
     */
    public function getAll($modelAlias = null) {
        return $this->execute($modelAlias);
    }

    /**
     * Get Array of values extracted from model.
     * @param {string} $column to extract
     * @param (string) $modelAlias optional
     * This function can only opperate over list of models,
     * that means it requires modelAlias for joined-tables opperation.
     * @returns {array}
     */
    public function getExtractColumn($column, $modelAlias = null) {
        if (!$this->isSingleTableQuery() && $modelAlias === null) {
            $this->throwException("modelAlias is required for joined tables opperation");
        }
        $models = $this->getAll($modelAlias);
        $output = [];
        foreach ($models as $model) {
            $output[] = $model->get($column);
        }
        return $output;
    }

    /**
     * Get limited amount of models defined by chunkSize (limit) and chunkOrdinal (offset multiplier).
     * As pagination pages starts with "1" rather than "0", the chunkOrdinal for first "n" elements should be set to "1" (default).
     * To get second page set chunkOrdinal to "2", for third "3" and so on.
     * Output shape is the same as for ::getAll();
     * @param {string} $extract
     * @return {array | array-array}
     * @throws Exception if model alias given for single table query.
     */
    public function getPagination($chunkSize, $chunkOrdinal = 1, $modelAlias = null) {
        if ($chunkOrdinal < 1) {
            $this->throwException("Chunk ordinal should be equal or greater than '1',  '$chunkOrdinal' given.");
        }

        return $this
                        ->limit($chunkSize)
                        ->offset($chunkSize * ($chunkOrdinal - 1))
                        ->execute($modelAlias);
    }

    /**
     * Returns FIRST model (single table) or FIRST model collection (joined tables).
     * Can return model for joned tables by extracting {modelAlias} from the collection.
     * Returns NULL to indicate no-results.
     * @param (string) modelAlias used to extract model from joined-tables
     * @return {object | array | null}
     * @throws Exception if model alias given for single table query.
     */
    public function getFirst($modelAlias = null) {
        $result = $this->getPagination(1, 1, $modelAlias);
        return Arr::get($result, 0);
    }

    /**
     * Returns UNIQUE model (single table) or UNIQUE model collection (joined tables).
     * Can return model for joned tables by extracting {modelAlias} from the collection.
     * Returns NULL to indicate no-results.
     * @param (string) modelAlias used to extract model from joined-tables
     * @return {object | array | null}
     * @throws Exception if model alias given for single table query.
     * @throws Exception if more then one row found for given query.
     */
    public function getOne($modelAlias = null) {
        /** Look for model data (Database row) */
        $result = $this->getPagination(2, 1, $modelAlias);
        if (count($result) === 2) {
            $this->throwException("Expected unique model, multiple found");
        }
        return Arr::get($result, 0);
    }

    /**
     * DELETE all of models found (single table) or all models from all collections (joined tables)
     * @return {null}
     */
    public function delete() {
        $models = $this->getAll();
        foreach ($models as $model) {
            if (Arr::is_array($model)) {
                foreach ($model as $_model) {
                    $_model->deleteIgnore();
                }
            } else {
                $model->deleteIgnore();
            }
        }
    }

    /**
     * Update all rows at once. For single table query {$data} should be an array corresponding to COLUMNS of updated model.
     * If joined tables each model should have its own data therefor {$data} should contain keys for each {$modelAlias} we would like to update.
     * @param {array | array-array} $data
     * @return {null}
     */
    public function update(array $data) {
        $models = $this->getAll();
        foreach ($models as $model) {
            if (Arr::is_array($model)) {
                foreach ($model as $_modelAlias => $_model) {
                    if (isset($data[$_modelAlias])) {
                        $_model->setArray($data[$_modelAlias]);
                        $_model->update();
                    }
                }
            } else {
                $model->setArray($data);
                $model->update();
            }
        }
    }

//DB::select()->group_by($columns);
//DB::select()->distinct($value);
//DB::select()->union($B, $all);
//DB::select()->using($columns);
}
