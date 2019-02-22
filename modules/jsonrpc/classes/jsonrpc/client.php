<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * A JSON-RPC 2.0 client helper.
 * WE STRONGLY RECOMMEND using NOTIFICATIONS rather than REQUESTS
 * because each time request is made the related onSuccess/onError callback is
 * stored and bound with request ID. In the case where cliend doesn't respond for the request
 * or respond with general error (Which doesn't have ID attached) the callbacks will be keept forever.
 * (There is no timeout mechanism because using timeout may leed to other kind of errors like runing
 * onError sequence just before getting success response).
 */
class JsonRpc_Client extends Abstract_Singleton {

    /** CALLBACKS (CLIENT) */
    const CALLBACK_SUCCESS_KEY = 'success';
    const CALLBACK_ERROR_KEY = 'error';

    /**
     * Returns unique ID to use in JSON-RPC request.
     * @returns {int}
     */
    protected $_uniqueRequestId = 0;

    protected function getUniqueId() {
        return ++$this->_uniqueRequestId;
    }

    /**
     * Process the response from remote location, triggers success or error callbacks.
     * @param array $response
     */
    public function dispatch(array $response) {
        if (Arr::is_assoc($response)) {
            $this->processResponse($response);
        } else {
            foreach ($response as $_r) {
                $this->processResponse($_r);
            }
        }
    }

    /**
     * Process single response
     * @returns bool, TRUE if processed, FALSE otherwise
     */
    protected function processResponse(array $response) {
        $id = Arr::get($response, JsonRpc::RESPONSE_KEY_ID);
        $jsonrpc = Arr::get($response, JsonRpc::RESPONSE_KEY_JSONRPC);
        $result = Arr::get($response, JsonRpc::RESPONSE_KEY_RESULT);

        if ($jsonrpc !== JsonRpc::JSONRPC2 || $id === NULL) {
            return false;
        }

        if ($result !== NULL) {
            if (isset($this->_callbacks[$id][self::CALLBACK_SUCCESS_KEY])) {
                $callback = $this->_callbacks[$id][self::CALLBACK_SUCCESS_KEY];
                $callback($result);
            }
            /* Remove callbacks */
            unset($this->_callbacks[$id]);
            return true;
        }

        $error = Arr::get($response, JsonRpc::RESPONSE_KEY_ERROR);
        if ($error !== NULL) {
            if (isset($this->_callbacks[$id][self::CALLBACK_ERROR_KEY])) {
                $callback = $this->_callbacks[$id][self::CALLBACK_ERROR_KEY];
                $errorCode = Arr::get($error, JsonRpc::RESPONSE_ERROR_KEY_CODE);
                $errorMessage = Arr::get($error, JsonRpc::RESPONSE_ERROR_KEY_MESSAGE);
                $errorData = Arr::get($error, JsonRpc::RESPONSE_ERROR_KEY_DATA);
                $callback($errorCode, $errorMessage, $errorData);
            }
            /* Remove callbacks */
            unset($this->_callbacks[$id]);
            return true;
        }
        return false;
    }

    /**
     * Send a NOTIFICATION
     * @param int | array $connectionID
     * @param string $method
     * @param array $params
     */
    public function notify($connectionID, $method, array $params = null) {
        $notification = $this->getNotificationObject($method, $params);
        return JsonRpc::send($connectionID, $notification);
    }

    /**
     * Creates a notification-call structure
     * @param string $method
     * @param array $params
     * @return array
     */
    public function getNotificationObject($method, $params = null) {
        $notification = array(
            JsonRpc::REQUEST_KEY_METHOD => $method,
            JsonRpc::REQUEST_KEY_JSONRPC => JsonRpc::JSONRPC2,
        );
        if ($params) {
            $notification[JsonRpc::REQUEST_KEY_PARAMS] = $params;
        }
        return $notification;
    }

    /**
     * Send a REQUEST
     * @param int | array $connectionID
     * @param string $method
     * @param callable $successCallback
     * Success callback should be a callable (lambda function) which takes one parameter:
     *      {mixed} $result
     * @param callable $errorCallback
     * Error callback should be a callable (lambda function) which takes 3 parameters:
     *      {int} error code,
     *      {string} error message,
     *      (mixed) extra data
     * @param array $params
     */
    protected $_callbacks = array();

    public function request($connectionID, $method, $params = null, $successCallback = null, $errorCallback = null) {
        $request = $this->getRequestObject($method, $params, $successCallback, $errorCallback);
        JsonRpc::send($connectionID, $request);
    }

    /**
     * Creates an request-call structure
     *  @param string $method
     *  @param array $params
     *  @param callable $successCallback
     *  @param callable $errorCallback
     *  @returns array
     */
    public function getRequestObject($method, $params = null, $successCallback = null, $errorCallback = null) {
        $id = $this->getUniqueId();
        $request = array(
            JsonRpc::REQUEST_KEY_METHOD => $method,
            JsonRpc::REQUEST_KEY_JSONRPC => JsonRpc::JSONRPC2,
            JsonRpc::REQUEST_KEY_ID => $id
        );

        if ($params) {
            if (!is_array($params)) {
                JsonRpc::throwException("Params should be given as array");
            }

            $request[JsonRpc::REQUEST_KEY_PARAMS] = $params;
        }

        if (is_callable($successCallback)) {
            $this->_callbacks[$id][self::CALLBACK_SUCCESS_KEY] = $successCallback;
        }

        if (is_callable($errorCallback)) {
            $this->_callbacks[$id][self::CALLBACK_ERROR_KEY] = $errorCallback;
        }
        return $request;
    }

    /**
     * Create Batch request
     * @return \Websocket_Client_Batch
     */
    public function batch() {
        return new JsonRpc_Client_Batch;
    }

}
