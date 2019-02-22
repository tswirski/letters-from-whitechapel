(function ($) {
    /**
     * Tell if string is HTML or not.
     * @param {string} input
     * @returns {bool}
     */
    var isHtml = function (input) {
        var entity = $.parseHTML(input);
        /* Invalid HTML - unparseable */
        if (entity.length === 0) {
            return false;
        }

        /* HTML with multiple nodes */
        if (entity.length > 1) {
            return true;
        }

        /* Tell if root HTML node or plain text */
        return (entity[0].outerHTML !== undefined);
    };

    /**
     * Tell if string is URL
     * @param {string} input
     * @returns {RegExp}
     */
    var isUrl = function (input) {
        return (/^http/i).test(input);
    }

    /** Default Button Texts */
    var DEFAULT_OK_BUTTON_TEXT = "OK";
    var DEFAULT_CANCEL_BUTTON_TEXT = "CANCEL";
    var DEFAULT_CLOSE_BUTTON_TEXT = "CLOSE";

    /**
     * Proxy is responsible for detecting if input is URL or AJAX CONFIGURATION OBJECT and making AJAX
     * whenever required.
     * Input can be either:
     * {string} Url or {object} Configuration for Ajax call.
     * * ( 'success' and 'error' callbacks are overwritten)
     * * ( ajax endpoint should replay with string (plain text or html))
     * Html / Text {string} or jQuery {object}.
     */
    var proxy = function (input, qTipPopupHandler, qTipPopupOptions) {
        if (isUrl(input) || $.isPlainObject(input)) {
            return _proxyAjax(input, qTipPopupHandler, qTipPopupOptions)
        }

        qTipPopupHandler(input, qTipPopupOptions);
    };

    /**
     * Log Error Message
     * @param {string}
     * @return {undefined}
     */
    var _proxyLogError = function (message) {
        console.log(message);
    };

    /**
     * Read .proxy() description
     * @param {string | object} URL or CONFIGURATION object as accepted by jQuery.ajax();
     * @param {function} qTipPopupHandler
     * @param {object} qTipPopupOptions
     * @return {undefined}
     */
    var _proxyAjax = function (input, qTipPopupHandler, qTipPopupOptions) {
        var options = $.isPlainObject(input) ? input : {url: input};
        $.extend(options, {
            success: function (data) {
                qTipPopupHandler(data, qTipPopupOptions);
            },
            error: _proxyLogError
        });

        $.ajax(options);
    }

    /**
     * Render ALERT popup with qTip.
     * @param {string | object} payload can be either:
     * Text, Html, Url or jQuery Ajax configuration object.
     * For plain TEXT content is rendered inside .qTipContentText container.
     * For HTML content is rendered inside .qTipContent container.
     * @param {array} options are used to set:
     *  {callable} onRender,
     *  {callable} onShow,
     *  {callable} onClose,
     *  {string} closeButtonText
     * @returns {undefined}
     */
    var popupAlert = function (payload, options) {
        proxy(payload, _alert, options);
        return true;
    };

    /**
     * @param {$object | string} jQuery object or string html/text
     * @param (object) _options
     * @returns {unresolved}
     */
    var _alert = function (payload, _options) {
        $('input').blur();

        var options = {};
        $.extend(options, _options);

        var $content, $contentContainer = $("<div>");
        if (payload && (payload.jquery || isHtml(payload))) {
            $content = $('<div class="qTipContent">').append(payload);
        } else {
            $content = $('<div class="qTipContentText">').append($("<span>").text(payload));
        }
        $contentContainer.append($content);
        var $buttonContainer = $('<div class="qTipContentButtons">');
        $contentContainer.append($buttonContainer);
        var $closeButton = $("<button>").addClass("qTipCloseButton").text(options.closeButtonText || DEFAULT_CLOSE_BUTTON_TEXT);
        $buttonContainer.append($closeButton);
        var $popup = $("<div />").qtip({
            content: {
                text: $contentContainer,
                title: options.title,
                button: $('<div>').addClass('qTipPopupCloseX').append('<div>')
            },
            position: {
                my: "center",
                at: "center",
                target: $(window)
            },
            show: {
                solo: '.qtip',
                ready: true,
                modal: {
                    on: true,
                    blur: false
                }
            },
            hide: false,
            events: {
                render: function (event, api) {
                    $closeButton.click(function (e) {
                        api.hide(e);
                    });
                    if (options.onRender) {
                        options.onRender(event, api);
                    }
                },
                show: function (event, api) {
                    if (options.onShow) {
                        options.onShow(event, api);
                    }
                },
                hide: function (event, api) {
                    if (options.onClose) {
                        options.onClose(event, api);
                    }
                    api.destroy();
                },
            },
            style: {
                classes: "qTipPopup"
            }
        });
        return $popup;
    };

    /**
     * Render DIALOG popup with qTip.
     * @param {string | object} payload can be either:
     * Text, Html, Url or jQuery Ajax configuration object.
     * For plain TEXT content is rendered inside .qTipContentText container.
     * For HTML content is rendered inside .qTipContent container.
     * @param {array} options are used to set:
     *  {callable} onRender,
     *  {callable} onShow,
     *  {callable} onClose,
     *  {callable} okHandler
     *  {callable} cancelHandler
     *  {string} okButtonText
     *  {string} cancelButtonText
     * @returns {undefined}
     */
    var popupDialog = function (payload, options) {
        proxy(payload, _dialog, options);
    };

    /**
     * @param {object | string} jQuery object
     * @param (object) _options
     * @returns {unresolved}
     */
    var _dialog = function (payload, _options) {
        var options = {};
        $.extend(options, _options);

        var $content, $contentContainer = $("<div>");
        if (payload && (payload.jquery || isHtml(payload))) {
            $content = $('<div class="qTipContent">').append(payload);
        } else {
            $content = $('<div class="qTipContentText">').append($("<span>").text(payload));
        }
        $contentContainer.append($content);
        var $buttonContainer = $('<div class="qTipContentButtons">');
        $contentContainer.append($buttonContainer);
        var $okButton = $("<button>").addClass("qTipOkButton").text(options.okButtonText || DEFAULT_OK_BUTTON_TEXT);
        var $cancelButton = $("<button>").addClass("qTipCloseButton").text(options.cancelButtonText || DEFAULT_CANCEL_BUTTON_TEXT);
        $buttonContainer.append($cancelButton);
        $buttonContainer.append('<span class="qTipButtonSeparator">');
        $buttonContainer.append($okButton);

        var $popup = $("<div />").qtip({
            content: {
                text: $contentContainer,
                title: options.title,
                button: $('<div>').addClass('qTipPopupCloseX').append('<div>')
            },
            position: {
                my: "center",
                at: "center",
                target: $(window)
            },
            show: {
                solo: '.qtip',
                ready: true,
                modal: {
                    on: true,
                    blur: false
                }
            },
            hide: false,
            events: {
                render: function (event, api) {

                    $okButton.on('click', function (e) {
                        if (options.okHandler) {
                            if (options.okHandler(event, api) !== false) {
                                api.hide(e);
                            }
                        } else {
                            api.hide(e);
                        }
                    });

                    $cancelButton.on('click', function (e) {
                        if (options.cancelHandler) {
                            if (options.cancelHandler(event, api) !== false) {
                                api.hide(e);
                            }
                        }
                        api.hide(e);
                    });


                    if (options.onRender) {
                        options.onRender(event, api);
                    }
                },
                show: function (event, api) {
                    if (options.onShow) {
                        options.onShow(event, api);
                    }

                    var $this = $(this);
                    $this.attr('tabindex', 1);
                    _.defer(function(){
                        $this.trigger('focus');
                    });

                    $(this).on('keydown', function(e){
                       if(e.which === KeyCode.ENTER){
                           $okButton.trigger('click');
                       }
                    });
                },
                hide: function (event, api) {
                    if (options.onClose) {
                        options.onClose(event, api);
                    }
                    api.destroy();
                },
            },
            style: {
                classes: "qTipPopup"
            }
        });
        return $popup;
    };

    var hide = function(){
        $('.qtip').qtip('hide');
    };

    var note = function(message){
        var $note = $('<div class="popoverNotification">');
        $note.text(message);
        $('.popoverNotificationsBox').append($note);
        _.delay(function(){
            $note.fadeOut(3000)
        }, 5000);
    };

    $.popupManager = {
        alert: popupAlert,
        dialog: popupDialog,
        hide: hide,
        note: note
    };
})(jQuery);