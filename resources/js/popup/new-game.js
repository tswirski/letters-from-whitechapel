var popupNewGame = (function ($) {
    var PAGE = '.popup[data-popup="new-game"]';
    var SELECTOR_PASSWORD_INPUT = 'input[name="password"]';
    var SELECTOR_LABEL_NO_PASSWORD = 'label[for="usingPasswordNo"]';
    var SELECTOR_LABEL = 'label';
    var CLASS_SELECTED_LABEL = 'selected';

    /**
     * Returns jQuery object selected relatively to page selector.
     * @param {string} selector
     * @returns {*|jQuery}
     */
    var getBySelector = function (selector) {
        return $(PAGE).find(selector);
    };

    var getFormData = function(){
        return getBySelector('form').getFormData();
    };

    var submitForm = function(){
        JsonRpc.request('dashboard-game.create', getFormData());
    };

    var initContent = function(){
        /** Disable Form Submission */
        getBySelector('form').on('submit', function(e){
            e.preventDefault();
        });

        /** Label Click Action */
        getBySelector('label').on('click', function(){
            $('label').removeClass(CLASS_SELECTED_LABEL);
            $(this).addClass(CLASS_SELECTED_LABEL);

            if($(this).attr('for') === "usingPasswordYes"){
                getBySelector(SELECTOR_PASSWORD_INPUT).val(null).slideDown().focus();
            } else {
                getBySelector(SELECTOR_PASSWORD_INPUT).val(null).slideUp().blur();
            }
        });

        //getBySelector('input[type="password"]').on('blur', function(e){
        //    if($(this).val().length === 0){
        //        getBySelector(SELECTOR_LABEL_NO_PASSWORD).click();
        //    }
        //});

        // ARROW UP and ARROW DOWN actions
        //$(PAGE).closest('.qtip').on('keydown', function(e){
        //    if(e.which === KeyCode.ARROW_DOWN || e.which === KeyCode.ARROW_UP){
        //        getBySelector(SELECTOR_LABEL).filter(':not(".' + CLASS_SELECTED_LABEL + '")').trigger('click');
        //    };
        //});
    };

    var init = function(){
        Template.render('popup-new-game', {}, function(html){
            $.popupManager.dialog(html, {
                onShow: initContent,
                okHandler: submitForm,
                okButtonText: "CREATE GAME"
            });
        });
    };

    return {
        init : init,
        getFormData : getFormData
    };
})(jQuery);
