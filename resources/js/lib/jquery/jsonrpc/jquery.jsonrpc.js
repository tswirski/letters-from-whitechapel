/**
 * JsonRpc
 * @type Function|@new;JsonRpcSingleton
 */

var JsonRpc = (function () {

    var JsonRpcSingleton = function () {

        /**
         * Convert object to JSON
         * @param {object}
         * @returns {String}
         */
        var toJson = function (obj) {
            return JSON.stringify(obj);
        }

        /********************************************
         * GET FUNCTION ARGUMENT LIST
         ********************************************/
        var STRIP_COMMENTS = /((\/\/.*$)|(\/\*[\s\S]*?\*\/))/mg;
        var ARGUMENT_NAMES = /([^\s,]+)/g;
        function getParamNames(func) {
            var fnStr = func.toString().replace(STRIP_COMMENTS, '');
            var result = fnStr.slice(fnStr.indexOf('(') + 1, fnStr.indexOf(')')).match(ARGUMENT_NAMES);
            if (result === null)
                result = [];
            return result;
        }

        /********************************************
         *  COMMON COMPONENTS
         *******************************************/
        /* JSON RPC REQUEST STRUCTURE */
        var REQUEST_KEY_METHOD = 'method';
        var REQUEST_KEY_PARAMS = 'params';
        var REQUEST_KEY_JSONRPC = 'jsonrpc';
        var REQUEST_KEY_ID = 'id';

        /* JSON RPC RESPONSE STRUCTURE */
        var RESPONSE_KEY_ID = 'id';
        var RESPONSE_KEY_RESULT = 'result';
        var RESPONSE_KEY_ERROR = 'error';
        var RESPONSE_KEY_JSONRPC = 'jsonrpc';

        var RESPONSE_ERROR_KEY_CODE = 'code';
        var RESPONSE_ERROR_KEY_MESSAGE = 'message';
        var RESPONSE_ERROR_KEY_DATA = 'data';

        /* JSON RPC ERROR CODES*/
        var ERR_INVALID_JSON = -32700;
        var ERR_INVALID_REQUEST = -32600;
        var ERR_INVALID_METHOD = -32601;
        var ERR_INVALID_PARAMS = -32602;
        var ERR_JSONRPC_INTERNAL = -32603;
        var ERR_SERVER_GENERAL = -32000;
        var JSONRPC2 = '2.0';

        /* JSON RPC ERROR MESSAGES */
        var _jsonRpcErrors = new Array();
        _jsonRpcErrors[ERR_INVALID_JSON] = 'Invalid json';
        _jsonRpcErrors[ERR_INVALID_REQUEST] = 'Invalid request structure';
        _jsonRpcErrors[ERR_INVALID_METHOD] = 'Method not found';
        _jsonRpcErrors[ERR_INVALID_PARAMS] = 'Invalid params';
        _jsonRpcErrors[ERR_JSONRPC_INTERNAL] = 'Internal error';
        _jsonRpcErrors[ERR_SERVER_GENERAL] = 'Server error';

        /**
         * Takes function pointer and data structure.
         * Data object is converted to array (as used by .apply()), non-object data remains unchanged.
         * @param {callable} callable
         * @param {object | array} data
         * @returns {array}
         */
        this.parseNamedParameters = function (callable, data) {
            if (data === null || typeof data !== 'object'){
                return data;
            }
            var arguments = getParamNames(callable);
            var _data = [];
            for (var i = 0; i < arguments.length; i++) {
                _data.push(data[arguments[i]]);
            }
            return _data;
        };

        /** THE CONNECTION CALLABLE
         * Handler to the function responsible for parsing array (or object) to JSON and sending it
         * to remote location */
        var _connection = null;

        /** Lets stick to this */
        var that = this;

        /**
         * Set the CONNECTION CALLABLE for REQUEST calls.
         * @param {callable} function taking one STRING param and transmit it as-it-is to remote location:
         * @returns {undefined}
         */
        this.setConnection = function (connection) {
            if (typeof connection !== 'function') {
                throw "Connection should be a CALLABLE (function) which takes ONE {STRING} param";
            }
            _connection = connection;
        };

        /**
         * Get JSON-RPC ERROR MESSAGE by the errorCode
         * @param int errorCode
         * @return string erroMessage
         */
        var _getErrorMessage = function (errorCode) {
            var message = _jsonRpcErrors[errorCode];
            return message ? message : 'Unknown error (' + errorCode + ')';
        }

        /**
         * LOG anyting for DEBUGGING purposes
         * @param {string}
         */
        var _log = function (log) {
            if ('console' in window) {
                console.log('[JSONRPC] ' + log);
            }
        };

        /**
         * Replace basic log function with your own.
         * Log function should take one parameter.
         * @param {type} callable
         * @returns {undefined}
         */
        this.setLogCallable = function (callable) {
            if (typeof callable === 'function') {
                _log = callable;
            }
        };

        /**
         * Send data to remote location
         * @param {array}
         * @returns {undefined}
         */
        var _send = function (data) {
            if (!_connection || typeof _connection !== 'function') {
                throw "Missing CONNECTION callable";
            }
            _connection(toJson(data));
        };



        /********************************************
         *  SERVER COMPONENTS
         ******************************************* /

         /** @var Registered functions */
        var _functions = new Array();

        /**
         * Register a function, so it can be called by the jsonrpc request (by parsing request).
         * @param {string} function name
         * @param {callable | array} function or array as [that {object}, methodName]
         *
         * @param (object) method subject
         * @return {bool}
         */
        this.register = function (functionName, callable) {
            if (_functions[functionName]) {
                _log("Can not register function '" + functionName + "' multiple times");
            }
            _functions[functionName] = callable;
        };

        /**
         * Register ALL public methods of given object
         * @param {object} object
         * @param (string) optionalPrefix
         * @returns {undefined}
         */
        this.registerObject = function (object, optionalPrefix) {
            if (!$.isPlainObject(object)) {
                _log("Not an object");
            }
            var prefix = optionalPrefix ? optionalPrefix : object.constructor.name;
            for (var method in object) {
                register(prefix + '.' + method, [object, method]);
            }
        };

        /**
         * Registered functionName list
         * @returns {Array}
         */
        this.getRegisteredNames = function () {
            return Object.keys(_functions);
        };

        /**
         * Returns TRUE if function with given name Exists (is registered)
         * FALSE otherwise.
         * @param {string} function name
         * @returns {bool}
         */
        this.exists = function (functionName) {
            return (functionName in _functions);
        };

        /**
         * Executes a function
         * @param {type} json
         * @returns {mixed} result
         */
        this.execute = function (functionName, data) {
            /** is function registered with JsonRpc ? */
            if (_functions[functionName] === undefined) {
                throw ("Unregistered function: '" + functionName + "'")
            }
            var callable = _functions[functionName];
            /** Default namespace */
            var namespace = window;

            /** Callable should be function or array */
            if (!$.isArray(callable) && !$.isFunction(callable)) {
                throw "Invalid callable format. Function or [{object}, {string} methodName] expected.";
            }

            if ($.isArray(callable)) {
                if (callable.length != 2) {
                    throw "Invalid callable format. Expected array as [{object}, {methodName}]";
                }
                namespace = callable[0];
                callable = callable[1];
            }

            if (!$.isFunction(callable)) {
                callable = namespace[callable];
            }
            /**
             * For named parameters data {object} is converted to data {array}
             * where missing values are replaced with [undefined].
             */
            data = that.parseNamedParameters(callable, data);
            return callable.apply(namespace, data);
        };



        /**
         * Creates an success-result-object
         * @param mixed result - a result returned from the function
         * @param int id
         * @return array
         */
        var _createSuccessResponse = function (result, id) {
            var response = {};
            response[RESPONSE_KEY_ID] = id;
            response[RESPONSE_KEY_RESULT] = result;
            response[RESPONSE_KEY_JSONRPC] = JSONRPC2;
            return response;
        }

        /**
         * Creates an error-resul-object
         * @param int id (required)
         * @param int errorCode (required)
         * @apram string errorMessage (required)
         * @param mixed data (optional)
         * @return array
         */
        var _createErrorResponse = function (errorCode, errorMessage, id, data) {
            var errorPayload = {};
            errorPayload[RESPONSE_ERROR_KEY_CODE] = errorCode;
            errorPayload[RESPONSE_ERROR_KEY_MESSAGE] = errorMessage;
            if (data) {
                errorPayload[RESPONSE_ERROR_KEY_DATA] = data;
            }

            var response = {};
            response[RESPONSE_KEY_ID] = id ? id : null;
            response[RESPONSE_KEY_JSONRPC] = JSONRPC2;
            response[RESPONSE_KEY_ERROR] = errorPayload;

            return response;
        };

        /**
         * The request is NOTIFICATION if there is no ID element
         * @return boolean
         */
        var _isNotification = function (request) {
            return !(REQUEST_KEY_ID in request);
        }

        /**
         * Checks if the Request Structure matches JSON RPC 2.0 specification
         * @param {object} request
         * @returns {bool | string}
         */
        var _isValidJsonRpc2Request = function (request) {
            /* missing JSONRPC version */
            if (request[REQUEST_KEY_JSONRPC] !== JSONRPC2) {
                return "Missing JSONRPC key, or value not equal '2.0'";
            }

            /* PARAMS may be omitted, but if present they have to be an array | object  */
            if (REQUEST_KEY_PARAMS in request
                && (typeof request[REQUEST_KEY_PARAMS] !== 'object' || request[REQUEST_KEY_PARAMS] === null)) {
                return "Wrong PARAMS value, array expected";
            }

            /* Check if method exists, and if its an string not-starting-with "rpc." */
            if (!request[REQUEST_KEY_METHOD] || request[REQUEST_KEY_METHOD].indexOf('rpc.') === 0) {
                return "Missing METHOD key, empty METHOD name or invalid name prefix";
            }
            return true;
        }

        /**
         * Process a single Request Structure and returns either
         * Success Response Structure (array), Error Response Structure (array) or null (for Notifications)
         * @param {array} request
         * @return {null | array}
         */
        var _processRequest = function (request) {
            /* Request has invalid structure */
            var requestValidationOutput = _isValidJsonRpc2Request(request);
            if (requestValidationOutput !== true) {

                /* Log an error */
                _log('Request Processing Error: ' + requestValidationOutput);

                return _createErrorResponse(
                        ERR_INVALID_REQUEST,
                        _getErrorMessage(ERR_INVALID_REQUEST),
                        null,
                        requestValidationOutput
                        );
            }

            /* Method doesn't exist */
            if (!that.exists(request[REQUEST_KEY_METHOD])) {

                /* Log an error */
                _log('Request Processing Error: ' + _getErrorMessage(ERR_INVALID_METHOD) + ', "' + request[REQUEST_KEY_METHOD] + '"');

                /* Notification fails silently */
                if (_isNotification(request)) {
                    return null;
                }

                return _createErrorResponse(
                        ERR_INVALID_METHOD,
                        _getErrorMessage(ERR_INVALID_METHOD),
                        request[REQUEST_KEY_ID],
                        request[REQUEST_KEY_METHOD]
                        );
            }

            /* Try to call given method */
            try {
                var result = that.execute(
                        request[REQUEST_KEY_METHOD],
                        request[REQUEST_KEY_PARAMS]
                        );

                /* Notification succeeds silently */
                if (_isNotification(request)) {
                    return null;
                }

                /* Return the success response */
                return _createSuccessResponse(result, request[REQUEST_KEY_ID]);

            } catch (ex) {

                /* Error thrown with CODE eqals to ERR_INVALID_PARAMS */
                if (ex === ERR_INVALID_PARAMS) {
                    /* Log an error */
                    _log('Request Processing Error: ' + _getErrorMessage(ERR_INVALID_PARAMS));

                    /* Notification fails silently */
                    if (_isNotification(request)) {
                        return null;
                    }

                    return _createErrorResponse(
                            ERR_INVALID_PARAMS,
                            _getErrorMessage(ERR_INVALID_PARAMS),
                            request[REQUEST_KEY_ID],
                            'Expected different parameter set'
                            );
                }


                /** ALL OTHER ERRORS
                 * ****************** */

                /* Log an error */
                _log('Request Processing Error: ' + _getErrorMessage(ERR_SERVER_GENERAL) + ', "' + ex + '"');

                /* Notification fails silently */
                if (_isNotification(request)) {
                    return null;
                }

                return _createErrorResponse(
                        ERR_SERVER_GENERAL,
                        _getErrorMessage(ERR_SERVER_GENERAL),
                        request[REQUEST_KEY_ID],
                        ex
                        );
            }

            // this should never happend
            _log("Reached unexpected end of function while processing the request.");
            return null;
        }

        /**
         * Executes the JSON RPC request (or batch), return response
         * @param {object | array} request
         * @returns {array | null}
         * @uses jQuery
         */
        var _dispatchRequest = function (request) {
            if ($.isPlainObject(request)) {
                return  _processRequest(request);
            } else if ($.isArray(request)) {
                var response = new Array();
                for (var i = 0; i < request.length; i++) {
                    var single_response = _processRequest(request[i]);
                    if (single_response !== null) {
                        response.push(single_response);
                    }
                }
                if (response.length === 0) {
                    return null;
                }
                return response;
            }
            return null;
        }




        /********************************************
         *  CLIENT COMPONENTS
         ******************************************* /

         /**
         * A map of callbacks.
         * Each entry has key equal to request ID and value is a map containing keys
         *  'onSuccess', 'onError' pointing to valid callback
         */
        var _callbacks = {};

        this.getCallbacks = function(){
          return _callbacks;
        };

        /**
         * A timers used to clock the onTimeout and onDelay.
         * A map with keys equal to request ID and value is a map containing keys:
         * 'onTimeout' and 'onDelay' pointing to the timer handler if set.
         */
        var _timers = {};


        /* ID Starting point, this value will be incremented to make request unique */
        var _id = 1;

        /**
         * Default options for REQUEST call.
         *      delay :
         *          Amount of milliseconds after which the onDelay will be called.
         *          Calling onDelay doesn't interrupt request execution
         *      onDelay:
         *          The callable
         *      timeout:
         *          Amount of milliseconds after which the onTimeout will be called.
         *          If onTimeout is no a valid callable then it fallsback to onError passing NONE parameters.
         *          (usually onError takes 3 params)
         *      onTimeout:
         *          The callable | null
         *      onError:
         *          The callable - error handler taking 3 params:
         *              {int} errorCode
         *              {string} errorMessage
         *              (mixed) data
         *      onSuccess:
         *          The callable - success handler taking 1 param
         *              {string} result
         */
        var _requestDefaultOptions = {
            delay: 0, // omit
            timeout: 0, // omit
            onDelay: function () {
            },
            onTimeout: null,
            onError: function (errorCode, errorMessage, data) {
            },
            onSuccess: function (result) {
            },
        };

        /**
         * Get a current ID to use in request.
         * @returns {int};
         */
        var _getUniqueId = function () {
            return _id++;
        };

        /** Get Unique Id - INTERFACE
         * @returns {int}
         */
        this.getUniqueId = function () {
            return _getUniqueId();
        };

        /**
         * Clears the 'callback' queue and timers.
         */
        this.stop = function () {
            /** Cancel all timers */
            _timers.forEach(function (element, index, array) {
                if (element.onTimeout) {
                    clearTimeout(element.onTimeout);
                }

                if (element.onDelay) {
                    clearTimeout(element.onDelay);
                }
            });

            /** Clear timers and callbacks */
            _timers = {};
            _callbacks = {};
        };

        /**
         * Removes one request from queue.
         * @param {mixed} id
         * @returns {undefined}
         */
        _stopById = function (id) {
            delete _callbacks[id];
            if (_timers[id] && _timers[id].onTimeout) {
                clearTimeout(_timers[id].onTimeout);
            }
            if (_timers[id] && _timers[id].onDelay) {
                clearTimeout(_timers[id].onDelay);
            }
            delete _timers[id];
        };


        /**
         * For Json HTTP RPC
         * @param response
         */
        this.stopById = function (id) {
            return _stopById(id);
        };

        /**
         * Stop All requests and trigger onError handler for each.
         * @returns {undefined}
         */
        this.failAll = function () {
            _.each(_callbacks, function (callbacks) {
                callbacks.onError();
            });
            stop();
        };

        /**
         * Trigger onError handler for request by ID.
         * @param {int | string} request id
         * @returns {undefined}
         */
        this.stopFailById = function (id) {
            if (_.has(_callbacks, id)) {
                _callbacks[id].onError();
            }
            stopById(id);
        };

        /**
         * Get Raw Request Object
         * @param {string} method
         * @param {object | array} params
         * @param {object} requestUserOptions
         * @param {mixed} explicitID (will be auto generated if not given)
         * @returns {object}
         */
        this.getRequestObject = function (method, params, requestUserOptions, explicitID) {
            return _request(method, params, requestUserOptions, explicitID);
        }

        /**
         * Get Request Json
         * @param {string} method
         * @param {object | array} params
         * @param {object} requestUserOptions
         * @param {mixed} explicitID (will be auto generated if not given)
         * @returns {string}
         */
        this.getRequestJson = function (method, params, requestUserOptions, explicitID) {
            return toJson(that.getRequestObject(method, params, requestUserOptions, explicitID));
        }

        /**
         * Creates a REQUEST structure
         * @param {string} method
         * @param {object | array} params
         * @param {object} requestUserOptions
         * @param {mixed} explicitID (will be auto generated if not given)
         * @returns {object}
         */
        var _request = function (method, params, requestUserOptions, explicitID) {
            if (!method) {
                throw "Method can not be empty";
            }

            var uniqueId = explicitID ? explicitID : _getUniqueId();

            var request = {
                jsonrpc: '2.0',
                id: uniqueId,
                method: method
            };

            if (params) {
                if (typeof params !== "object"){
                    throw "Expected a structured param value: List (Array) or Map (Object)";
                }
                request.params = params;
            }

            var options = new Object;
            $.extend(options, that.requestDefaultOptions, requestUserOptions)

            var callbacks = new Object;
            _callbacks[uniqueId] = callbacks;

            var timers = new Object;
            _timers[uniqueId] = timers;

            callbacks.onSuccess = (typeof options.onSuccess === 'function')
                    ? options.onSuccess
                    : function (result) {
                    }
            ;

            callbacks.onError = (typeof options.onError === 'function')
                    ? options.onError
                    : function (errorCode, errorMessage, result) {
                    }
            ;

            /** Start onDelay timer */
            if (options.delay && typeof options.onDelay === 'function') {
                timers.onDelay = setTimeout(function () {
                    options.onDelay();
                }, options.delay
                        );
            }

            /** Start onTimeout timer */
            if (options.timeout) {
                timers.onTimeout = setTimeout(function () {

                    /** Clears onDelay - we do not need that anymore */
                    if (timers.onDelay) {
                        clearTimeout(timers.onDelay);
                    }
                    /** Fires onTimeot or onError */
                    if (typeof options.onTimeout === 'function') {
                        options.onTimeout();
                    } else {
                        options.onError();
                    }

                    delete _timers[uniqueId];
                    delete _callbacks[uniqueId];
                }, options.timeout
                );
            }
            return request;
        };

        /**
         * Make a REQUEST call.
         * You can pass custom options.
         * For details see this.requestDefaultOptions
         * @param {string} method
         * @param {array|object} params
         * @param {object} options
         * @param {mixed} explicitID (will be auto generated if not given)
         * @return {this}
         */
        this.request = function (method, params, requestUserOptions, explicitID) {
            var request = _request(method, params, requestUserOptions, explicitID);
            if (_isBatchMode()) {
                _batchQueue.push(request);
            } else {
                _send(request);
            }
            return this;
        };


        /**
         * Creates a NOTIFICATION structure.
         * @param {string} method
         * @param {object | array} params
         * @returns {object}
         */
        var _notify = function (method, params) {
            if (!method) {
                throw "Method can not be empty";
            }

            var request = {
                jsonrpc: '2.0',
                method: method
            };

            if (params) {
                if (typeof params !== 'object') {
                    throw "Expected a structured param value: List (Array) or Map (Object)";
                }
                request.params = params;
            }

            return request;
        };

        /**
         * Get Raw Notify Object
         * @param {string} method
         * @param {object | array} params
         * @returns {object}
         */
        this.getNotifyObject = function (method, params) {
            return _notify(method, params);
        }

        /**
         * Get Notify Json
         * @param {string} method
         * @param {object | array} params
         * @returns {string}
         */
        this.getNotifyJson = function (method, params) {
            return toJson(that.getNotifyObject(method, params));
        }

        /**
         * Make a NOTIFICATION call.
         * @param {string} method to call
         * @param {object | array} parameters to pass to called method.
         * @return {this}
         */
        this.notify = function (method, params) {
            var request = _notify(method, params);
            if (_isBatchMode()) {
                _batchQueue.push(request);
            } else {
                _send(request);
            }
            return this;
        };


        /** Batch enabling flag */
        var _batchModeFlag = false;
        /** Queued Requests (if bach mode); */
        var _batchQueue = [];

        /**
         * Checks if the BATCH mode is ON.
         * @returns {Boolean}
         */
        var _isBatchMode = function () {
            return _batchModeFlag;
        };

        this.isBatchMode = _isBatchMode;

        /**
         * Starts a BATCH request
         * @returns {this}
         */
        this.batchBegin = function () {
            _batchModeFlag = true; //enable batch mode
            return this;
        };

        /**
         * Ends BATCH request and returns batch request object
         * @returns {object}
         */
        this.batchEndAsObject = function () {
            var batchQueue = _batchQueue;
            _batchQueue = [];   //empty queue
            _batchModeFlag = false; // disable batch mode
            return batchQueue;
        }


        /**
         * Ends BATCH request and returns batch request JSON
         * @returns {string}
         */
        this.batchEndAsJson = function () {
            return toJson(that.batchEndAsObject());
        }

        /**
         * Ends BATCH request and makes a request to remote location
         * @returns {undefined}
         */
        this.batchEndSend = function () {
            _send(that.batchEndGet()); //send queue
        };

        /**
         * Process single response
         * @returns bool, TRUE if processed, FALSE otherwise
         */
        var _processResponse = function (response) {
            var id = response[RESPONSE_KEY_ID]

            if (response[RESPONSE_KEY_JSONRPC] !== JSONRPC2 || !id) {
                return false;
            }

            if (response[RESPONSE_KEY_RESULT] !== undefined) {
                if (_callbacks[id] && _callbacks[id].onSuccess) {
                    _callbacks[id].onSuccess(response[RESPONSE_KEY_RESULT]);
                }
                /* Remove callbacks */
                _stopById(id);
                return true;
            }

            if (response[RESPONSE_KEY_ERROR] !== undefined) {
                if (_callbacks[id] && _callbacks[id].onError) {
                    var error = response[RESPONSE_KEY_ERROR];
                    var errorCode = error[RESPONSE_ERROR_KEY_CODE];
                    var errorMessage = error[RESPONSE_ERROR_KEY_MESSAGE];
                    var errorData = error[RESPONSE_ERROR_KEY_DATA];
                    _callbacks[id].onError(errorCode, errorMessage, errorData);
                }
                /* Remove callbacks */
                _stopById(id);
                return true;
            }
            return false;
        };

        /**
         * Takes JSON RPC response (or batch), and execute the SUCCESS or ERROR callback.
         * @param {object | array} response
         * @returns {undefined}
         * @uses jQuery
         */
        var _dispatchResponse = function (response) {
            if ($.isPlainObject(response)) {
                _processResponse(response);
            } else if ($.isArray(response)) {
                for (var single_response in response) {
                    _processResponse(single_response);
                }
            }
        }

        /**
         * For Json HTTP RPC
         * @param response
         */
        this.dispatchResponse = function(response){

            /* Check for valid JSON */
            try {
                if (typeof response !== 'object') {
                    response = $.parseJSON(response);
                }
            } catch (exception) {
                _log(exception);
                return;
            }

            _dispatchResponse(response);
        };


        /**
         * This function should be called each time new data arrives.
         * The data is verified and then passed to SERVER or CLIENT.
         * Any data different then {null} returned from this function should be send back to
         * remote location (usually an Response Object or Response Batch).
         * @param {string | object} json string or parsed object
         * @return {null | array | object}
         */
        this.dispatch = function (json) {
            var result = _dispatch(json);

            if (!result) {
                return null;
            }

            return result;
        }


        /**
         * The CORE of dispatch function. Takes JSON, converts it to array, checks if the request is a REQUEST or RESPONSE
         * and pass it to dispatchRequest or dispatchResponse for further processing.
         * @param {string | object} json
         * @returns {array | null} response
         * @uses jQuery
         */
        var _dispatch = function (json) {
            var rpcArr;

            /* Check for valid JSON */
            try {
                if (typeof json === 'object') {
                    rpcArr = json;
                } else {
                    rpcArr = $.parseJSON(json);
                }
            } catch (exception) {
                return _createErrorResponse(
                        ERR_INVALID_JSON,
                        _getErrorMessage(ERR_INVALID_JSON),
                        null,
                        exception
                        );
            }

            if ($.isArray(rpcArr)) {
                /* Empty BATCH call */
                if (!rpcArr.length) {
                    return _createErrorResponse(
                            ERR_INVALID_REQUEST,
                            _getErrorMessage(ERR_INVALID_REQUEST),
                            null,
                            'Empty BATCH request'
                            );
                }

                if (rpcArr[0][RESPONSE_KEY_RESULT] !== undefined || rpcArr[0][RESPONSE_KEY_ERROR] !== undefined) {
                    _dispatchResponse(rpcArr);
                    return null;
                } else {
                    return _dispatchRequest(rpcArr);
                }
            }

            if ($.isPlainObject(rpcArr)) {
                if (rpcArr[RESPONSE_KEY_RESULT] !== undefined || rpcArr[RESPONSE_KEY_ERROR] !== undefined) {
                    _dispatchResponse(rpcArr);
                    return null;
                } else {
                    return _dispatchRequest(rpcArr);
                }
            }
        };
    };
    /**
     * RETURN THE INSTANCE
     */
    return new JsonRpcSingleton();
})();