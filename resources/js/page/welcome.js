/**
 * Welcome Page
 * -> Sign In
 * -> Password Recovery
 * -> Sign Up
 */
var pageWelcome = (function () {
    var PAGE = '.page[data-page="welcome"]';

    /**
     * Returns jQuery object selected relatively to page selector.
     * @param {string} selector
     * @returns {*|jQuery}
     */
    var getBySelector = function(selector){
        return $(PAGE).find(selector);
    };

    var hideErrorFor = function (inputName) {
        getBySelector('input[name=' + inputName + ']').parent().find('.error').hide({
            direction: "down",
            effect: "drop",
            duration: 150
        });
    };

    var showErrors = function (errors) {
        _.each(errors, function (value, key) {
            var $errorBox = getBySelector('.error[for="input-' + key + '"]');
            $errorBox.html(value);
            $errorBox.show({
                direction: "up",
                effect: "drop",
                duration: 150,
                complete: function () {
                }
            });
        });
    };

    var switchToSignupForm = function () {
        var transitionDuration = 200;
        var $formAnimatedBox = getBySelector(".inputGroup");
        var $submitButtonBox = getBySelector('.submitButtonBox');

        /** Submit Button Animation */
        $submitButtonBox.hide({
            direction: "left",
            effect: "drop",
            duration: transitionDuration,
            complete: function () {
                $submitButtonBox.show({
                    direction: "right",
                    effect: "drop",
                    duration: transitionDuration,
                })
            }
        });

        /** Form Content Animation */
        $formAnimatedBox.hide({
            direction: "right",
            effect: "drop",
            duration: transitionDuration,
            complete: function () {
                getBySelector('.error').html(null).hide(0);
                getBySelector('#input-nickname').val(null);
                getBySelector('#input-password').val(null);
                $formAnimatedBox.show({
                    direction: "left",
                    effect: "drop",
                    duration: transitionDuration,
                });
            }
        });
    };

    var clickSwitchToSigninForm = function () {
        getBySelector('input[type="radio"][name="action"][value="signin"]').trigger('click');
    };

    var switchToSigninForm = function () {
        var transitionDuration = 200;
        var $formAnimatedBox = getBySelector(".inputGroup");
        var $submitButtonBox = getBySelector('.submitButtonBox');

        $submitButtonBox.hide({
            direction: "left",
            effect: "drop",
            duration: transitionDuration,
            complete: function () {
                $submitButtonBox.show({
                    direction: "right",
                    effect: "drop",
                    duration: transitionDuration,
                })
            }
        });


        $formAnimatedBox.hide({
            direction: "right",
            effect: "drop",
            duration: transitionDuration,
            complete: function () {
                getBySelector('#input-password').val(null);
                getBySelector('#input-nickname').val(null);
                getBySelector('.error').html(null).hide(0);

                $formAnimatedBox.show({
                    direction: "left",
                    effect: "drop",
                    duration: transitionDuration,
                });
            }
        });
    };

    var init = function(){

        // page already loaded
        if($(PAGE).length == 1){
            initContent();
            return;
        }

        // load page
        Template.render('page-welcome', [], function($pageContent){
            Page.load($pageContent, initContent);
        });
    };


    var initContent = function () {
        /** SIGNIN or SIGNUP */
        getBySelector('.buttonGroup input').on('click', function (event) {
            if ($(this).val() === 'signin') {
                switchToSigninForm();
            } else {
                switchToSignupForm();
            }
            var $label = $(this).closest('label');
            $label.siblings('label').removeClass('active');
            $label.addClass('active');
        });


        /** ERROR CANCELING */
        getBySelector('.inputBox input').on('focus keydown click', function (event) {
            $(this).parent().find('.error').hide({
                direction: "down",
                effect: "drop",
                duration: 150,
                 complete: function () {
                }
            });

        });

        /** FORM SUBMISSION */
        var jsonRpcSubmitFormDebounced = _.debounce(function ($that) {
            var action = getBySelector('input[name=action]:checked').val();

            var data = {};
			data.nickname = getBySelector('input[name=nickname]').val();
			data.password = getBySelector('input[name=password]').val();
            /** Put prefix */
            action = 'access.' + action;
            JsonHttpRpc.request(action, data);
        }, 500, true);

        getBySelector('form').on('submit', function (event) {
            event.preventDefault();
            jsonRpcSubmitFormDebounced($(this));
        });

      };

    var reconnectDialog = function(message, userId, wsToken){
        $.popupManager.dialog(message, {
            okHandler: function () {
                Server.login(userId, wsToken, true);
            }
        });
    };

    return {
        init: init,
        reconnectDialog: reconnectDialog,
        showErrors: showErrors,
        clickSwitchToSigninForm : clickSwitchToSigninForm
    };

})();