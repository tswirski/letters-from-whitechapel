<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * Json Rpc 2.0 Server for Kohana 3.2
 */
class JsonRpc_Server extends Abstract_Singleton {

    /**
     * @var {array} registered functions.
     */
    protected $registered_functions;

    /**
     * Get ReflectionMethod objects for All public methods of given class (or object).
     * @param {object | string} $objectOrClassName
     * @return {array}
     */
    protected function _getMethods($objectOrClassName) {
        $reflection = new ReflectionClass($objectOrClassName);
        return $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    }

    /**
     * Get list of all public static methods of given class (or object).
     * @param {object | string} $objectOrClassName
     * @return {array}
     */
    protected function getPublicStaticMethodNames($objectOrClassName) {
        $output = [];
        foreach ($this->_getMethods() as $method) {
            if ($method->isStatic()) {
                $output[] = $method->name;
            }
        }
        return $output;
    }

    /**
     * Get list of all public non-static methods of given class (or object).
     * @param {object | string} $objectOrClassName
     * @return {array}
     */
    protected function getPublicMethodNames($objectOrClassName) {
        $output = [];
        foreach ($this->_getMethods() as $method) {
            if (!$method->isStatic()) {
                $output[] = $method->name;
            }
        }
        return $output;
    }

    /**
     * Register a callable.
     * @param $procedureKey {string}
     * @param $callable {callable | string | array} :
     * use {callable} to register lambda function.
     * use {string} to register global function by its name.
     * use [{string}, {string}]  to register class static public method.
     * use [{object}, {string}] to register object public method (static or not).
     * @return {object} /JsonRpc_Server
     */
    public function register($functionName, $callable) {
        if (!is_callable($callable)) {
            JsonRpc::throwException("Incorrect handler for {$functionName} (not a function or protected method)");
        }

        /**
         * If function with given name already registered.
         */
        if (isset($this->registered_functions[$functionName])) {
            JsonRpc::throwException($functionName . ', function with given name is already registered');
        }

        /**
         * Register given function
         */
        $this->registered_functions[$functionName] = $callable;

        return $this;
    }

    /**
     * Register All Object Methods.
     * @param {string | object} $instanceOrClassName
     * Use {string} to register PUBLIC STATIC functions.
     * Use {object} to register PUBLIC methods of instance.
     * @param (string) class name replacement
     */
    public function registerObject($instanceOrClassName, $classNameReplacement = null) {

        if (is_object($instanceOrClassName)) {
            /**
             * Register Object Instance Methods
             */
            $className = $classNameReplacement ?
                    $classNameReplacement :
                    get_class($instanceOrClassName);
            $methods = $this->getPublicMethodNames($objectOrClassName);
        } else {
            /**
             * Register Class Static Methods
             */
            $className = $classNameReplacement ?
                    $classNameReplacement :
                    $instanceOrClassName;

            $methods = $this->getPublicStaticMethodNames($objectOrClassName);
        }

        foreach ($methods as $method) {
            $this->register($className . '.' . $method, [$instanceOrClassName, $method]);
        }
        return $this;
    }

    /**
     * Check if function is registered
     * @param string The name of the function (route name)
     * @return bool, TRUE if function exists, FALSE otherwise
     */
    protected function exists($functionName) {
        return isset($this->registered_functions[$functionName]);
    }

    /**
     * Calls the function by name $functionName.
     * @param type $functionName
     * @param type $data
     * @returns ResponseStructure
     */
    protected function execute($functionName, $data) {

        if (!isset($this->registered_functions[$functionName])) {
            JsonRpc::throwRpcException(JsonRpc::ERR_INVALID_METHOD, "Function '{$functionName}' not registered with JsonRpc");
        }

        /** Copy Callable */
        $callable = $this->registered_functions[$functionName];

        $parameterReflections = is_array($callable) ?
                (new ReflectionMethod($callable[0], $callable[1]))->getParameters() :
                (new ReflectionFunction($callable))->getParameters();

        /**
         * Throw INVALID PARAMS when $data contains more parameters than function can take.
         */
        if (count($data) > count($parameterReflections)) {
            JsonRpc::throwRpcException(
                    JsonRpc::ERR_INVALID_PARAMS, "Too many parameters"
            );
        }

        /**
         * Named Parameters
         */
        $dataKeys = array_keys($data);
        if (array_keys($dataKeys) !== $dataKeys) {

            $argNames = array_map(function($reflection) {
                return $reflection->getName();
            }, $parameterReflections);

            /**
             * Throw INVALID PARAMS if $data contains named parameters
             * which are not accepted by called function.
             */
            $unmatchedParams = array_diff($dataKeys, $argNames);
            if ($unmatchedParams) {
                $unmatchedParams = implode(', ', $unmatchedParams);
                JsonRpc::throwRpcException(
                        JsonRpc::ERR_INVALID_PARAMS, "Function doesn't take named parameters like those: ({$unmatchedParams})"
                );
            }

            $_data = [];
            $_missingParam = null;
            foreach ($argNames as $argName) {
                if (array_key_exists($argName, $data)) {
                    if ($_missingParam !== null) {
                        /**
                         * Throw INVALID PARAMS if undefined param followed by defined param
                         */
                        JsonRpc::throwRpcException(
                                JsonRpc::ERR_INVALID_PARAMS, "Missing Named Parameter ({$_missingParam})"
                        );
                    }

                    $_data[] = $data[$argName];
                } else {
                    $_missingParam = $argName;
                }
            }
            $data = $_data;
        }

        /**
         * Throw INVALID PARAMS if at least one omitted param doesn't have default value.
         */
        for ($i = count($data); $i < count($parameterReflections); $i++) {

            if (!$parameterReflections[$i]->isDefaultValueAvailable()) {
                /** Throw INVALID PARAMS if one of omitted params doesn't have default value */
                JsonRpc::throwRpcException(
                        JsonRpc::ERR_INVALID_PARAMS, " {$parameterReflections[$i]->getName()} doesn't have default value"
                );
            }
        }

        /** Call function */
        return call_user_func_array($callable, $data);
    }

    /**
     * Creates an success-result-object
     * @param mixed $result - a result returned from the function
     * @param int id
     * @return array
     */
    public function createSuccessResponse($result, $id) {
        return
                [
                    JsonRpc::RESPONSE_KEY_ID => $id,
                    JsonRpc::RESPONSE_KEY_RESULT => $result,
                    JsonRpc::RESPONSE_KEY_JSONRPC => JsonRpc::JSONRPC2
        ];
    }

    /**
     * Creates an error-resul-object
     * @param int id (required)
     * @param int errorCode (required)
     * @apram string errorMessage (required)
     * @param mixed data (optional)
     * @return array
     */
    public function createErrorResponse($errorCode, $errorMessage, $id = null, $data = null) {
        $return = [
            JsonRpc::RESPONSE_KEY_ID => $id,
            JsonRpc::RESPONSE_KEY_JSONRPC => JsonRpc::JSONRPC2,
            JsonRpc::RESPONSE_KEY_ERROR => [
                JsonRpc::RESPONSE_ERROR_KEY_CODE => $errorCode,
                JsonRpc::RESPONSE_ERROR_KEY_MESSAGE => $errorMessage
            ]
        ];

        if (!empty($data)) {
            $return[JsonRpc::RESPONSE_KEY_ERROR][JsonRpc::RESPONSE_ERROR_KEY_DATA] = $data;
        }

        return $return;
    }

    /**
     * The request is NOTIFICATION if there is no ID element
     * @return bool
     */
    protected function isNotification($request) {
        return !isset($request[JsonRpc::REQUEST_KEY_ID]);
    }

    /**
     * Checks if the Request Structure matches JSON RPC 2.0 specification
     * @return bool
     */
    protected function isValidJsonRpc2Request($request, &$errorValidationMsg) {
        /* missing JSONRPC version */
        if (!isset($request[JsonRpc::REQUEST_KEY_JSONRPC]) || $request[JsonRpc::REQUEST_KEY_JSONRPC] !== JsonRpc::JSONRPC2
        ) {
            $errorValidationMsg = "Missing JSONRPC key, or value not equal '2.0'";

            return false;
        }
        /* PARAMS may be omitted, but if present they have to be an array */
        if (isset($request[JsonRpc::REQUEST_KEY_PARAMS]) && !is_array($request[JsonRpc::REQUEST_KEY_PARAMS])
        ) {
            $errorValidationMsg = "Wrong PARAMS value, array expected";

            return false;
        }
        /* Check if method exists, and if its an string not-starting-with "rpc." */
        if (!isset($request[JsonRpc::REQUEST_KEY_METHOD]) || empty($request[JsonRpc::REQUEST_KEY_METHOD]) || strripos($request[JsonRpc:: REQUEST_KEY_METHOD], 'rpc.') === 0
        ) {
            $errorValidationMsg = "Missing METHOD key, empty METHOD name or invalid name prefix";

            return false;
        }
        return true;
    }

    /**
     * Process a single Request Structure and returns either
     * Success Response Structure (array), Error Response Structure (array) or null (for Notifications)
     * @param array $request
     * @return null | array
     */
    protected function processRequest($request) {
        /* Request has invalid structure */
        if (!$this->isValidJsonRpc2Request($request, $errorValidationMsg)) {
            return
                    $this->createErrorResponse(
                            JsonRpc::ERR_INVALID_REQUEST, JsonRpc::getErrorMessage(
                                    JsonRpc:: ERR_INVALID_REQUEST
                            ), null, $errorValidationMsg
            );
        }

        /* Method doesn't exist */
        if (!$this->exists($request[JsonRpc::REQUEST_KEY_METHOD])) {
            /* Notification fails silently */
            if ($this->isNotification($request)) {
                return null;
            }
            return $this->createErrorResponse(
                            JsonRpc::ERR_INVALID_METHOD, JsonRpc::getErrorMessage(JsonRpc:: ERR_INVALID_METHOD), $request[JsonRpc::REQUEST_KEY_ID], $request[JsonRpc::REQUEST_KEY_METHOD]
            );
        }

        /* Try to call given method */
        try {
            $result = $this->execute(
                    $request[JsonRpc:: REQUEST_KEY_METHOD], Arr::get($request, JsonRpc::REQUEST_KEY_PARAMS, []));
        } catch (Exception $ex) {

            // exception re-throw
            // Kohana_Exception::handler($ex);

            /* Notification fails silently */
            if ($this->isNotification($request)) {
                return null;
            }

            /* Error thrown with CODE eqals to ERR_INVALID_PARAMS */
            if ($ex->getCode() === JsonRpc::ERR_INVALID_PARAMS) {
                return $this->createErrorResponse(
                    JsonRpc ::ERR_INVALID_PARAMS,
                    JsonRpc::getErrorMessage(JsonRpc:: ERR_INVALID_PARAMS),
                    $request[JsonRpc::REQUEST_KEY_ID],
                    implode(', ', [
                        $ex->getFile(),
                        $ex->getLine(),
                        $ex->getMessage()
                    ])
                );
            }

            /* All other errors */
            return $this->createErrorResponse(
                JsonRpc ::ERR_SERVER_GENERAL,
                JsonRpc::getErrorMessage(JsonRpc::ERR_SERVER_GENERAL),
                $request[JsonRpc::REQUEST_KEY_ID],
                implode(', ', [
                    $ex->getFile(),
                    $ex->getLine(),
                    $ex->getMessage()
                ])
            );
        }

        /* Notification succeeds silently */
        if ($this->isNotification($request)) {
            return null;
        }

        return $this->createSuccessResponse($result, $request[JsonRpc::REQUEST_KEY_ID]);
    }

    /**
     * Takes an ARRAY input, and runs RPC according to JSON-RPC-2 methodology.
     * If ARRAY contains single NOTIFICATION request or BATCH made of NOTIFICATIONS requests
     * then NULL will be returned from this function. (NOTIFICATION request succeed or fails silently).
     * Otherwise a single Response Structure (array) will be returned for a single request.
     * Or a list of Response Structures (array-array) for all NON-NOTIFICATION request within BATCH.
     * Returned value is JSON-ENCODED
     *
     * @param array
     * @return array | null
     * */
    public function dispatch(array $request) {

        /**
         *  Is single request
         */
        if (Arr::is_assoc($request)) {
            return $this->processRequest($request);
        }

        /**
         *  Is batch
         */
        $response = array();
        foreach ($request as $_request) {
            $_response = $this->processRequest($_request);
            if (!is_null($_response)) {
                $response[] = $_response;
            }
        }
        return empty($response) ? null : $response;
    }

}
