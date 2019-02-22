var FancyWebSocket = function (url)
{
    var callbacks = {};
    //var conn;
    var _canDisconnect = false;
    this.canDisconnect = function(){
      _canDisconnect = true;
    };

    var bind = function (event_name, callback) {
        callbacks[event_name] = callbacks[event_name] || [];
        callbacks[event_name].push(callback);
    };

    var dispatch = function (event_name, message) {
        /** Skip one disconnect event. */
        if(event_name === EVENT_ONCLOSE && _canDisconnect === true){
            _canDisconnect = false;
            return;
        }

        var chain = callbacks[event_name];
        if (typeof chain == 'undefined')
            return; // no callbacks for this event
        for (var i = 0; i < chain.length; i++) {
            chain[i](message)
        }
    };

    var EVENT_ONOPEN = 'onOpen';
    var EVENT_ONCLOSE = 'onClose';
    var EVENT_ONERROR = 'onError';
    var EVENT_ONMESSAGE = 'onMessage';

    this.onOpen = function (callback) {
        bind(EVENT_ONOPEN, callback);
    };

    this.onMessage = function (callback) {
        bind(EVENT_ONMESSAGE, callback);
    };

    this.onClose = function (callback) {
        bind(EVENT_ONCLOSE, callback);
    };

    this.onError = function (callback) {
        bind(EVENT_ONERROR, callback);
    };

    this.send = function (data) {
        this.conn.send(data);
        return this;
    };

    //this.getSocket = function () {
    //    return this.conn;
    //};

    this.connect = function (urlExt) {

        var wsUrl = urlExt ? url + urlExt : url;

        if (typeof (MozWebSocket) == 'function')
            this.conn = new MozWebSocket(wsUrl);
        else
            this.conn = new WebSocket(wsUrl);

        /**
         * dispatch to the right handlers
         */
        this.conn.onmessage = function (evt) {
            dispatch(EVENT_ONMESSAGE, evt.data);
        };
        this.conn.onclose = function () {
            dispatch(EVENT_ONCLOSE, null)
        }
        this.conn.onopen = function () {
            dispatch(EVENT_ONOPEN, null)
        }
        this.conn.onerror = function () {
            dispatch(EVENT_ONERROR, null)
        }
    };

    this.disconnect = function () {
        this.conn.close();
    };
};
