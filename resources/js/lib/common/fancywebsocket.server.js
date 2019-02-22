/** ********************************************
 * SERVER
 ***********************************************/
var Server = new FancyWebSocket('ws://' + document.domain  + ':9300');

/** Set JSONRPC Connection */
JsonRpc.setConnection(function (payload) {
    console.debug === true && console.log(" -> " + payload);
    Server.send(payload);
});

/**
 * Create the LOGIN rule
 * @param {int} user_id
 * @param {string} code
 * @returns {undefined}
 */
Server.login = function (userId, wsToken, overwrite) {
    var urlExt = ({
        userId: userId,
        wsToken: wsToken,
        overwrite: overwrite ? 1 : 0
    }).toQueryString();
    Server.connect(urlExt);
};

/**
 * ON OPEN
 */
Server.onOpen(function () {
    console.log("CONNECTED");
});

/**
 * ON CLOSE
 */
Server.onClose(function (data) {
    $.popupManager.alert("You were disconnected", {
       onClose: function(){
           location.reload();
       }
    });
    console.log("DISCONNECTED");
});

/**
 * ON ERROR
 */
Server.onError(function (data) {
    console.log("ERROR");
});

/**
 * ON MESSAGE (support everything that isn't (JSON RPC | PUBSUB) here)
 */
Server.onMessage(function (payload) {
    console.debug === true && console.log(" <- " + payload);
    JsonRpc.dispatch(payload);
});