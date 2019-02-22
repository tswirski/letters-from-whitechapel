<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * JSON RPC 2.0 for Kohana 3.2
 */
class JsonRpc {
    /* JSON RPC REQUEST STRUCTURE */

    const REQUEST_KEY_METHOD = 'method';
    const REQUEST_KEY_PARAMS = 'params';
    const REQUEST_KEY_JSONRPC = 'jsonrpc';
    const REQUEST_KEY_ID = 'id';

    /* JSON RPC RESPONSE STRUCTURE */
    const RESPONSE_KEY_ID = 'id';
    const RESPONSE_KEY_RESULT = 'result';
    const RESPONSE_KEY_ERROR = 'error';
    const RESPONSE_KEY_JSONRPC = 'jsonrpc';
    const RESPONSE_ERROR_KEY_CODE = 'code';
    const RESPONSE_ERROR_KEY_MESSAGE = 'message';
    const RESPONSE_ERROR_KEY_DATA = 'data';

    /* JSON RPC ERROR CODES */
    const ERR_INVALID_JSON = -32700;
    const ERR_INVALID_REQUEST = -32600;
    const ERR_INVALID_METHOD = -32601;
    const ERR_INVALID_PARAMS = -32602;
    const ERR_JSONRPC_INTERNAL = -32603;
    const ERR_SERVER_GENERAL = -32000;
    const JSONRPC2 = '2.0';

    /** JSON PARSE ERROR MESSAGES */
    protected static $jsonErrors = array(
        JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
        JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
        JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
        JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
        JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    );

    /**
     * Get JSON-PARSE error by the errorCode.
     * @param int $errorCode
     * @return string
     */
    protected static function getJsonParseErrorMessage($errorCode) {
        return Arr::get(
                        self::$jsonErrors, $errorCode, "Unknown error ({$errorCode})"
        );
    }

    /* JSON RPC ERROR MESSAGES */

    protected static $errors = array(
        JsonRpc::ERR_INVALID_JSON => 'Invalid json',
        JsonRpc::ERR_INVALID_REQUEST => 'Invalid request structure',
        JsonRpc::ERR_INVALID_METHOD => 'Method not found',
        JsonRpc::ERR_INVALID_PARAMS => 'Invalid params',
        JsonRpc::ERR_JSONRPC_INTERNAL => 'Internal error',
        JsonRpc::ERR_SERVER_GENERAL => 'Server error',
    );

    /**
     * Get JSON-RPC ERROR MESSAGE by the errorCode
     * @param string $errorCode
     */
    public static function getErrorMessage($errorCode) {
        return Arr::get(
                        self::$errors, $errorCode, "Unknown error ({$errorCode})"
        );
    }

    /**
     * Reference to function responsible for making debug log.
     */
    protected static $logCallable;

    /**
     * Set the logging function. Function should take one string parameter.
     * @param callable $callable
     */
    public static function setLogCallable($callable) {
        if (is_callable($callable)) {
            self::$logCallable = $callable;
        }
    }

    /**
     * Log TEXT data using logging function (if given)
     * @param string $message
     */
    public static function log($message) {
        if (is_callable(self::$logCallable)) {
            $callback = self::$logCallable;
            $callback($message);
        }
    }

    protected static $_sendCallable;

    /**
     * Set up the function responsible for sending messages to server.
     * This function should take two parameters. First is the connection, user, socket od ID.
     * (Whatever is your way of identifying your connection) and second is the payload.
     * Function should assume that payload is arleady json_encoded well-formatted json-rpc call and
     * should send it as-it-is to user identified by first parameter.
     * @param callable $callable
     * @return boolean
     */
    public static function setSendCallable($callable) {
        if (is_callable($callable)) {
            self::$_sendCallable = $callable;
            return true;
        }
        return false;
    }

    /**
     * An internal method allowing to send requests by calling the 'sendCallable'
     * and passing two parameters, connection id and data payload.
     * @param mixed An CONNECTION id (user id or socket id or whatever).
     *
     * @param array $payload
     */
    public static function send($connectionID, array $payload)
    {
        $method = self::$_sendCallable;
        if (!is_callable($method)) {
            self::throwException("Set the Send-Handler callable first");
        }
        $payload = json_encode($payload);

        if (is_array($connectionID)) {
            /** Send to multiple recipients */
            foreach ($connectionID as $_connectionID) {
                $method($_connectionID, $payload);
            }
        } else {
            /** Send to single recipient */
            $method($connectionID, $payload);
        }
    }

    /**
     * This function should be called each time new data arrives.
     * The data is verified and then passed to SERVER or CLIENT to proceed
     * @param string $json
     * @return string | null
     */
    public static function dispatch($json) {
        $result = self::_dispatch($json);
        return empty($result) ? null : json_encode($result);
    }

    public static function _dispatch($json) {

        /* Check for valid JSON */
        $rpcArr = json_decode($json, true);

        if (is_null($rpcArr)) {
            return
                    JsonRpc::server()->createErrorResponse(
                            JsonRpc::ERR_INVALID_JSON, JsonRpc::getErrorMessage(JsonRpc::ERR_INVALID_JSON), null, JsonRpc::getJsonParseErrorMessage(json_last_error())
            );
        }

        /* Check if its an array */
        if (!is_array($rpcArr)) {
            return
                    JsonRpc::server()->createErrorResponse(
                            JsonRpc::ERR_INVALID_REQUEST, JsonRpc::getErrorMessage(JsonRpc::ERR_INVALID_REQUEST), null, 'Not an array'
            );
        }

        /* Check if its an empty array (usualy empty BATCH container) */
        if (empty($rpcArr)) {
            return
                    JsonRpc::server()->createErrorResponse(
                            JsonRpc::ERR_INVALID_REQUEST, JsonRpc::getErrorMessage(JsonRpc::ERR_INVALID_REQUEST), null, 'Empty BATCH request'
            );
        }

        if (/* If its an RESPONSE from the remote */
                isset($rpcArr[JsonRpc::RESPONSE_KEY_RESULT]) || isset($rpcArr[JsonRpc::RESPONSE_KEY_ERROR]) || isset($rpcArr[0][JsonRpc::RESPONSE_KEY_RESULT]) || isset($rpcArr[0][JsonRpc::RESPONSE_KEY_ERROR])
        ) {
            JsonRpc::client()->dispatch($rpcArr);
            return null;
        } else {
            return JsonRpc::server()->dispatch($rpcArr);
        }
    }

    /**
     * Get SERVER instance
     * @return /JsonRpc_Server
     */
    public static function server() {
        return JsonRpc_Server::instance();
    }

    /**
     * Get CLIENT instance
     * @return /JsonRpc_Client
     */
    public static function client() {
        return JsonRpc_Client::instance();
    }

    /**
     * Throw Exception
     * @param {int} code
     * @param {string} message
     */
    public static function throwException($message) {
        throw new Exception($message);
    }

    /**
     * Throw Exception
     * @param {int} code
     * @param {string} message
     */
    public static function throwRpcException($code, $message = null) {
        if ($message === null) {
            $message = self::getErrorMessage($code);
        }
        throw new Exception($message, $code);
    }

}
