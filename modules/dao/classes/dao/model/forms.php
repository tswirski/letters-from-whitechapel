<?php

/**
 * Class DAO_Model_Forms
 * This class contains helpers to deal with <form>s and data import and export along with
 * prefix handling.
 */
    abstract class DAO_Model_Forms extends DAO_Model_Database
    {

        /** @var  {string | null} model prefix */
        protected $_prefix;

        /**
         * Set model prefix
         * @param $prefix
         * @return $this
         */
        public function setPrefix($prefix)
        {
            $this->_prefix = $prefix;
            return $this;
        }

        /**
         * Get model prefix (either custom or default)
         * @param (string) $prefix - if given it will be returned as-it-is;
         * @return string
         */
        protected function getPrefix($prefix = null)
        {

            if ($prefix !== null) {
                return (string)$prefix;
            }

            /** is Custom Prefix set ? */
            if ($this->_prefix) {
                return $this->_prefix;
            }

            $class = get_class($this);
            $class = strtolower($class);
            if (strpos($class, 'model_') === 0) {
                $class = substr($class, 6);
            }
            return $class . '.';
        }

        /**
         * Add prefix to given string.
         * Put Prefix is used to create <input> 'name', <label> 'for' and so on.
         * @param {string} $string
         * @param (string) optional prefix, if not set default or pre-set will be used.
         * @returns {string} prefixed string
         * @throws {Exception} if empty string
         */
        public function putPrefix($string, $prefix = null)
        {
            if (empty($string)) {
                $this->throwException('can not prefix empty string');
            }
            return $this->getPrefix($prefix) . (string)$string;
        }

        /**
         * Remove prefix from given string.
         * If prefix is successfully removed then returned value is {string}, {boolean} false otherwise.
         * @param {string} $string
         * @param (string) optional prefix, if not set default or pre-set will be used.
         * @return {string | false}
         */
        public function stripPrefix($string, $prefix = null)
        {
            if (strpos($string, $this->getPrefix($prefix)) !== 0) {
                return false;
            }
            return substr($string, strlen($this->getPrefix($prefix)));
        }

        /**
         * Add prefix to each key of array.
         * @param {array} $array
         * @param (string) optional prefix, if not set default or pre-set will be used.
         * @return {array}
         */
        private function putPrefixArr(array $array, $prefix = null)
        {
            $outputArray = [];
            foreach ($array as $key => $value) {
                $outputArray[$this->putPrefix($key, $prefix)] = $value;
            }
            return $outputArray;
        }

        /**
         * Traverses array removing all keys which are not prefixed,
         * and unprefixing those which are.
         * @param {array} $array
         * @param (string) optional prefix, if not set default or pre-set will be used.
         * @returns {array}
         */
        public function stripPrefixArr(array $array, $prefix = null)
        {
            $outputArray = [];
            foreach ($array as $key => $value) {
                $unprefixedKey = $this->stripPrefix($key, $prefix);
                if ($unprefixedKey !== false) {
                    $outputArray[$unprefixedKey] = $value;
                }
            }
            return $outputArray;
        }

        /**
         * @param array $data
         * @param array|null $filter
         * @param (string | null | false) $prefix where string is prefix to use,
         * null means default or pre-set prefix and false means noprefix.
         * @return bool, TRUE if imported data is VALID
         * @throws Kohana_Exception
         */
        public function _import(array $data, array $filter = null, $prefix = false)
        {
            /** Import all columns if no filter set */
            if (null === $filter) {
                $filter = $this->getColumns();
            }

            /** @var {array} output data */
            $_data = [];
            $dataKey = null;
            $columnName = null;

            foreach ($filter as $filterKey => $filterValue) {
                if (is_integer($filterKey)) {
                    $dataKey = ($prefix === false) ? $filterValue : $this->putPrefix($filterValue, $prefix);
                    $columnName = $filterValue;
                } else {
                    $dataKey = $filterKey;
                    $columnName = $filterValue;
                }

                if (!$this->isColumn($columnName) && !$this->isSetter($columnName)) {
                    $this->throwException("Neither column nor setter exists for '$columnName'.");
                }

                if (array_key_exists($dataKey, $data)) {
                    $_data[$columnName] = $data[$dataKey];
                } else if ($this->isBooleanColumn($columnName)) {
                    $_data[$columnName] = false;
                }
            }

            /* Is valid? */
            if (!$this->isValidData($_data)) {
                return FALSE;
            }

            $this->setArray($_data);
            return TRUE;
        }


        /**
         * Import data from array. Missing boolean columns are automatically set to FALSE to
         * support standard 'checkbox' behavior where unchecked checkbox are not present
         * in the data set. Second parameter is used to filter or remap data array.
         * If data is invalid in model context none of its elements will be imported.
         *
         * DIRECT MODE
         * By default (if no filter set) all data will be imported skipping keys from $data
         * table which are not related to any column in model.
         * In direct mode key in $data array has to be the same as column name of model.
         * example: $model->import(['a'=>1, 'b'=>2, 'c'=>3]);
         * Where if model contains column 'a', 'b', or 'c' the data will be imported.
         *
         * FILTER MODE
         * Filter is an array as $key => $value where $value is expected to be column name of the model.
         * If filter $key is integer then filter $value is used as $key of the $data table.
         * If filter $key is non-numeric then filter $key is used as $key of the $data table.
         *
         * @param array $data
         * @param array|null $filter
         * @returns {boolean} TRUE for valid data, FALSE otherwise
         */
        public function import(array $data, array $filter = null)
        {
            return $this->_import($data, $filter, false);
        }

        /**
         * This function is used usually while working with multiple models where $data contains
         * elements for more then one model. In this case $data keys are distinguished by model prefixes.
         *
         * DIRECT MODE:
         * Only those elements from $data array will be imported which are successfully unprefixed
         * and after that are pointing to existing column in model.
         *
         * FILTER MODE
         * Filter is an array as $key => $value where $value is expected to be column name of the model.
         * If filter $key is integer then filter $value is used as unprefixed $key of the $data table.
         * If filter $key is non-numeric then filter $key is used as $key of the $data table.
         *
         * @example
         *
         *  $_POST = [
         * 'user.id' => 7,
         * 'user.email' => 'test@test.pl',
         * 'user.password' => 'abcdefghijk',
         * 'address.street' => 'orlowskiego',
         * 'address.homenum' => '25',
         * ];
         *
         *
         * $user = DAO::factory('User', null);
         * $address = DAO::factory('Address', null);
         *
         * $user->importPrefixed($_POST, ['id', 'password', 'email']);
         * $address->importPrefixed($_POST, ['street', 'homenum', 'user.id' => 'id']);
         *
         * $user->save();
         * $address->save();
         *
         * @param array $data
         * @param array|null $filter
         */
        public function importPrefixed(array $data, array $filter = null, $prefix = null)
        {
            return $this->_import($data, $filter, $prefix);
        }
    }
