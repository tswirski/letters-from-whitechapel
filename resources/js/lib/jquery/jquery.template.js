
var Template = (function($){

    var DATAKEY_POST_RENDER_CALLBACK = 'postRenderCallback';
    var DATAKEY_DATA = 'data';
    var DATAKEY_TEMPLATE_NAME = 'templateName';
    var DATAKEY_ACTION = 'action';
    var DATAKEY_TARGET = 'target';

    /** @var {array} data queued for rendering as i => {
     *  templateName : {string},
     *  data: {object},
     *  postRenderCallback: (callable),
     *  target: {object | string}
     * }
     */
    var _queue = [];

    /** @var {object} as templateName => true for pending requests */
    var _pendingConnections = {};

    /** @var {object} callable template functions as templateName => callable */
    var _underscoreTemplates = {};

    /** @var {string} URL of HTTP endpoint*/
    var _url = null;

    /**
     * Push Queue
     * @param {string | object} target
     * @param {string} action - 'append', 'prepend', 'html', 'replaceWith' as defined in jQuery.
     * @param {string} templateName
     * @param {object} $data
     * @param (callable) postRenderCallback
     * @return {undefined}
     */
    var pushQueue = function(target, action, templateName, data, postRenderCallback){
        var queuedObject = {};
        queuedObject[DATAKEY_TARGET] = target;
        queuedObject[DATAKEY_ACTION] = action;
        queuedObject[DATAKEY_TEMPLATE_NAME] = templateName;
        queuedObject[DATAKEY_DATA] = data;
        queuedObject[DATAKEY_POST_RENDER_CALLBACK] = postRenderCallback;
        _queue.push(queuedObject);
    };

    /**
     * Shift Render Queue, return first element in queue.
     * @returns {object}
     */
    var shiftQueue = function(){
        return _queue.shift();
    };

    /**
     * Returns TRUE if Render Queue is empty,
     * FALSE otherwise.
     * @returns {bool}
     */
    var isQueueEmpty = function(){
        return _queue.length === 0;
    };

    /**
     * Returns first element from Render Queue or underined
     * @returns {object | undefined}
     */
    var getQueueObjectAt0 = function(){
        return _queue[0];
    };


    /**
     * Set Pending Connection status for templateName
     * @param {string} templateName
     * @return {undefined}
     */
    var setPendingConnection = function(templateName){
        _pendingConnections[templateName] = true;
    };

    /**
     * Returns TRUE if template download has started but not yet ended.
     * @param {string} templateName
     * @return {bool}
     */
    var isPendingConnection = function(templateName){
        return _pendingConnections[templateName] !== undefined;
    };

    /**
     * Removes Pending Connection status from template.
     * @param {string} templateName
     * @return {undefined}
     */
    var unsetPendingConnection = function(templateName){
        delete(_pendingConnections[templateName]);
    };

    /**
     * Set URL of HTTP endpoint
     * @param {string}
     */
    var setUrl = function (url) {
        _url = url;
    };

    /**
     * Get URL for HTTP endpoint
     * @returns {string}
     */
    var getUrl = function () {
        if (!_url || _url.length === 0 || typeof _url !== "string") {
            throw "Invalid URL (not a string, or empty)";
        }
        return _url;
    };

    /**
     * Tells if template with given name is registered or not.
     * @param {string} templateName
     * @returns {boolean}
     */
    var isRegisteredTemplate = function(templateName){
        return _underscoreTemplates[templateName] !== undefined;
    };

    /**
     * Register new template callable
     * @param {string} templateName
     * @param {string} html
     * @return {undefined}
     */
    var registerTemplate = function(templateName, html){
        _underscoreTemplates[templateName] = _.template(html);
    };

    /**
     * Returns registered template callable.
     * @param templateName
     * @returns {callable}
     */
    var getTemplate = function(templateName){
        return _underscoreTemplates[templateName];
    };

    /**
     * Tells if element is jQuery object or not.
     * @param {mixed] $object
     * @returns {boolean}
     */
    var isJQueryObject = function($object){
        if (typeof $object !== 'object'){
            return false;
        }
        return ($object instanceof jQuery);
    };

    /**
     * Render object from queue
     * @param {object} queuedObject
     * @return {boolean} TRUE on succes, FALSE otherwise
     */
    var renderQueuedObject = function(queuedObject){
        var templateName = queuedObject[DATAKEY_TEMPLATE_NAME];
        var postRenderCallback = queuedObject[DATAKEY_POST_RENDER_CALLBACK];

        /** Process queued callbacks (null-template postRenderCallbacks) */
        if(templateName === null){
            if(postRenderCallback && $.isFunction(postRenderCallback)){
                postRenderCallback();
            }
            return true;
        }

        /** If template is not registered (most likely still waiting for http ajax response) */
        if ( ! isRegisteredTemplate(templateName)){
            return false;
        }

        var data = queuedObject[DATAKEY_DATA];
        var html = getTemplate(templateName)(data);

        try {
            var $html = $(html);
        } catch (e){
            console.log(e);
            throw "Template error, not valid html (flat-text or empty)";
        }

        /** Take action */
        var target = queuedObject[DATAKEY_TARGET];
        if(target !== null) {
            var $target = isJQueryObject(target) ? target : $(target);
            var action = queuedObject[DATAKEY_ACTION];
            $target[action]($html);
        }

        if(postRenderCallback && $.isFunction(postRenderCallback)){
            postRenderCallback($html, data);
        }
        return true;
    };

    /**
     * Render first object from queue, return TRUE on success FALSE on failure.
     * @return {bool}
     */
    var renderFirstObjectFromQueue = function(){
        var queuedObject = getQueueObjectAt0();
        if(queuedObject === undefined){
            return false;
        }

        try{
            var renderStatus = renderQueuedObject(queuedObject);
            if(renderStatus === true){
                shiftQueue();
            }
            return renderStatus;
        } catch(e){
            /** if something goes wrong - skip this queuedObject and proceed with rendering rest of the queue */
            console.log(e);
            shiftQueue();
            return true;
        }
    };

    /**
     * QUEUE RENDER LOCK SUPPORT
     * @type {boolean}
     */
    var queueRenderLock = false;
    var setQueueRenderLock = function(){
        queueRenderLock = true;
    }
    var unsetQueueLock = function(){
        queueRenderLock = false;
    }
    var isQueueRenderLock = function(){
        return queueRenderLock;
    }

    /**
     * Function is calling _renderQueueObject
     * @return {boolean}
     */
    var renderQueue = function(){
        if(isQueueRenderLock()){
            return false;
        }

        setQueueRenderLock();
        while(renderFirstObjectFromQueue());
        unsetQueueLock();
        return true;
    };

    /**
     * Sends http request, and creates new _template function.
     * Renders queue on success.
     * @return {undefined}
     */

    var httpGetTemplate = function (templateName, ajaxSuccessHandler){
        if(isPendingConnection(templateName)){
            return false;
        }

        setPendingConnection(templateName);
        return $.ajax({
            url: getUrl(),
            method: 'get',
            data: {template: templateName},
            success: function (html) {
                registerTemplate(templateName, html);
                unsetPendingConnection(templateName);
                ajaxSuccessHandler();
            },
            contentType: false,
            //processData: false,
            dataType: "text",
            error: function () {
                unsetPendingConnection(templateName);
                throw "Template acquire failed";
            }
        });
    };


    /**
     * @param {string | object} target
     * @param {string} action
     * @param {string} templateName
     * @param {object} data
     * @param (callable) postRenderCallback
     * @return {undefined}
     */
    var _render = function(target, action, templateName, data, postRenderCallback){
        /** If template has no extra data we can set postRenderCallback in the place of data */
        if(postRenderCallback === undefined && $.isFunction(data)){
            postRenderCallback = data;
            data = undefined;
        }

        pushQueue(target, action, templateName, data, postRenderCallback);

        if(isRegisteredTemplate(templateName) || templateName === null){
            return renderQueue();
        }

        return httpGetTemplate(templateName, renderQueue);
    };

    /**
     * Load template if not yet loaded.
     * Call onLoaded.
     * @param {string} templateName
     * @param {callable} onLoaded
     */
    var load = function(templateName, onLoaded){
        if(!$.isFunction(onLoaded)){
            onLoaded = function(){}
        }

        if(isRegisteredTemplate(templateName)){
            return onLoaded();
        }

        return httpGetTemplate(templateName, onLoaded);
    };

    /** SHORTCUT FUNCTIONS */
    var renderHtml = function(target, templateName, data, postRenderCallback){
        return _render(target, 'html', templateName, data, postRenderCallback);
    };
    var renderPrepend = function(target, templateName, data, postRenderCallback){
        return _render(target, 'prepend', templateName, data, postRenderCallback);
    };
    var renderAppend = function(target, templateName, data, postRenderCallback){
        return _render(target, 'append', templateName, data, postRenderCallback);
    };
    var renderAfter = function(target, templateName, data, postRenderCallback){
        return _render(target, 'after', templateName, data, postRenderCallback);
    };
    var renderBefore = function(target, templateName, data, postRenderCallback){
        return _render(target, 'before', templateName, data, postRenderCallback);
    };
    var renderReplace = function(target, templateName, data, postRenderCallback){
        return _render(target, 'replaceWith', templateName, data, postRenderCallback);
    };
    var render = function(templateName, data, postRenderCallback){
        return _render(null, null, templateName, data, postRenderCallback);
    };

    /**
     * Place callback in queue
     * @param callable
     */
    var call = function(callable){
       return _render(null, null, null, null, callable);
    };


    return {
        setUrl : setUrl,
        render : render,
        renderHtml: renderHtml,
        renderPrepend: renderPrepend,
        renderAppend: renderAppend,
        renderBefore: renderBefore,
        renderAfter: renderAfter,
        renderReplace: renderReplace,
        load: load,
        call: call,
        getQueue: function(){ return _queue; }
    };

})(jQuery);