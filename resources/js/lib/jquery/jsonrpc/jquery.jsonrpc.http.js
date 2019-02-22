/**
 * HTTP support for JsonRpc library.
 */

var JsonHttpRpc = (function() {

  /** Lets stick to this */
  var that = this;

  /** @var {string} URL of JSON RPC endpoint*/
  var _url = null;

  /**
   * Set URL of JSON RPC endpoint
   * @param {string}
   */
  this.setUrl = function (url) {
    _url = url;
  };

  /**
   * Get URL for JSON RPC endpoint
   * @returns {string}
   */
  var getUrl = function () {
    if (!_url || _url.length === 0 || typeof _url !== "string") {
      throw "Invalid URL (not a string, or empty)";
    }
    return _url;
  };

  /**
   * Sends http request, dispatch json response
   * @param {string | object} requestJson or FormData object
   * @param (int) request ID (optional)
   * @param (callable) ajaxErrorHandler
   * @return xhr
   */
  var send = function (requestJson, ajaxErrorHandler, requestId) {
    return $.ajax({
      url: getUrl(),
      method: 'post',
      data: requestJson,
      success: function (responseJson) {

        console.log("SERVER RESPONSE JSON => ");
        console.log(responseJson);

        if (responseJson) {
          /** Handle onSuccess and onError callbacks */
          JsonRpc.dispatchResponse(responseJson);

          /** Json RPC FEEDBACK call */
          var responseObject = JSON.parse(responseJson);
          if (responseObject.result) {
            JsonRpc.dispatch(responseObject.result);
          }
        }
      },
      contentType: false,
      processData: false,
      dataType: "text",
      error: function () {

        if(requestId !== undefined){
          JsonRpc.stopById(requestId);
        }

        if($.isFunction(ajaxErrorHandler)){
          ajaxErrorHandler();
        }
      }
    });
  };

  /**
   * Creates a REQUEST structure
   * @param {string} method
   * @param {object | array} params
   * @param {object} options as accepted by JsonRpc.request
   * @param {object | callable} config-object or onError callable
   * @returns {this}
   */
  var request = function (method, params, options, ajaxErrorHandler) {
    var uniqueId = JsonRpc.getUniqueId();

    if (JsonRpc.isBatchMode()) {
      JsonRpc.request(method, params, options, uniqueId);
    } else {
      var requestJson = JsonRpc.getRequestJson(method, params, options, uniqueId);
      send(requestJson, ajaxErrorHandler, uniqueId);
    }

    return this;
  };

  /**
   * Make a NOTIFICATION call.
   * @param {string} method to call
   * @param {object | array} parameters to pass to called method.
   * @param {callable} onError callable
   * @return {this}
   */
  var notify = function (method, params, ajaxErrorHandler) {
    if (JsonRpc.isBatchMode()) {
      JsonRpc.notify(method, params);
    } else {
      var notifyJson = JsonRpc.getNotifyJson(method, params);
      send(notifyJson, ajaxErrorHandler);
    }

    return this;
  };

  var batchBegin = function(){
    JsonRpc.batchBegin();
    return this;
  };

  var batchEndSend = function(ajaxErrorHandler){
    var batchJson = JsonRpc.batchEndJson();
    send(batchJson, ajaxErrorHandler);
    return this;
  };

  return {
    send : send,
    setUrl: setUrl,
    getUrl: getUrl,
    notify: notify,
    request: request,
    batchBegin: batchBegin,
    batchEndSend: batchEndSend
  };
})();