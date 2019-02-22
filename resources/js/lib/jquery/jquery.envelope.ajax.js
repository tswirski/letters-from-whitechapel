/**
 * HTTP Ajax Extension for Envelope Module
 */

var Ajax = (function() {
    var lock = false;

    return {
        /**
         * Creates AJAX call and passes result to Envelope to proceed.
         *
         * @param {array} options.
         *      url : {string} endpoint URL address,
         *      data : {object | array} (optional) payload,
         *      method : {string} 'post' or 'get'.
         *          Default method is set to 'get' when no data available and to 'post' for data payload.
         *      before : {function} to call before response evaluation if request suceed.
         *      success : {function} to call after response evaluation when http code was either 200 (ok), 204 (no-content).
         *      error : {function} to call in the case of timeout, http-error or unparsable response.
         *      timeout : {int} a number of mili-secs (default is 10000ms = 10 sec.)
         *      that : {object} : extra param passed to all callback functions.
         *          Common case is to pass a jQuery object responsible for triggering ajax call.
         *      sync : (optional) {bool} default: false
         *  Be aware that due to cross-browser and cross-platform compatibility, simpleness and clearness of code
         *  only one ahah call is allowed at the time. If an ahah call is in progress other ahah request will be discarded
         *  outputing the console info 'paw request halted';
         */
        request: function(options) {
            var useLock = !('sync' in options) || options.sync !== false;

            /* Test against ajax - sync - lock */
            if (useLock && Ajax.lock) {
                console.log('Ajax.paw request halted [other paw request is in progress]');
                return false;
            }

            /* Test against required URL param */
            if ((!'url' in options) || (!options.url)) {
                console.log('Ajax.paw request halted [no url given]');
                return false;
            }

            if (!('timeout' in options) || !options.timeout) {
                options.timeout = 10000;
            }


            if (!('method' in options) || !options.method) {
                if (options.data) {
                    options.method = Ajax.METHOD_POST;
                }
                else {
                    options.method = Ajax.METHOD_GET;
                }
            }

            /* Rise ajax lock */
            if (useLock) {
                Ajax.lock = true;
            }

            $.ajax({
                success: function(response) {
                    if ('before' in options && options.before) {
                        if (options.before instanceof Function) {
                            options.before(options.that);
                        } else {
                            try {
                                window[options.before](options.that);
                            } catch (error) {
                                console.log('Ajax.paw unsupported before-success callback ' + options.before);
                            }
                        }
                    }

                    try {
                        Ajax.processPaw(response);
                    } catch (error) {
                        console.log('Ajax.paw request error : ' + error);

                        if ('error' in options && options.error) {
                            if (options.error instanceof Function) {
                                options.error(options.that);
                            } else {
                                try {
                                    window[options.error](options.that);
                                } catch (error) {
                                    console.log('Ajax.paw unsupported error callback ' + options.error);
                                }
                            }
                        }

                        /* Remove ajax lock */
                        if (useLock) {
                            Ajax.lock = false;
                        }
                        return false;
                    }

                    if ('success' in options && options.success) {
                        if (options.success instanceof Function) {
                            options.success(options.that);
                        } else {
                            try {
                                window[options.success](options.that);
                            } catch (error) {
                                console.log('Ajax.paw unsupported success callback ' + options.success);
                            }
                        }
                    }

                    /* Remove ajax lock */
                    if (useLock) {
                        Ajax.lock = false;
                    }

                },
                error: function(jqXHR, textStatus, errorThrown)
                {
                    console.log('Unparsable Ajax.paw response:');
                    console.log(textStatus);
                    console.log(errorThrown);

                    if ('error' in options && options.error) {
                        if (options.error instanceof Function) {
                            options.error(options.that);
                        } else {
                            try {
                                window[options.error](options.that);
                            } catch (error) {
                                console.log('Ajax.paw unsupported error callback ' + options.error);
                            }
                        }
                    }

                    /* Remove ajax lock */
                    if (useLock) {
                        Ajax.lock = false;
                    }

                },
                url: options.url,
                dataType: 'json',
                cache: false,
                type: options.method,
                timeout: options.timeout,
                data: options.data,
                processData: true
            });
        }
    };
})();
