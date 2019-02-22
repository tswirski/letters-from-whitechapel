var popupGamePassword = (function($){
    var _gameId = null;
    var POPUP = '.popup[data-popup="game-password"]';
    var SELECTOR_PASSWORD_INPUT = 'input[name="password"]';

    var setGameId = function(gameId){
        _gameId = gameId;
    };

    var getGameId = function(){
      return _gameId;
    };

    /**
     * Returns jQuery object selected relatively to page selector.
     * @param {string} selector
     * @returns {*|jQuery}
     */
    var getBySelector = function (selector) {
        return $(POPUP).find(selector);
    };

    var submitForm = function(){
        JsonRpc.request('dashboard-game.joinProtected', {
            gameId: getGameId(),
            password: getBySelector(SELECTOR_PASSWORD_INPUT).val()
        });
    };

    var initContent = function() {
        /** Disable Form Submission */
        getBySelector('form').on('submit', function (e) {
            e.preventDefault();
        });

        /** Wait until password input visible */
        _.delay(function(){
            getBySelector(SELECTOR_PASSWORD_INPUT).trigger('focus');
        }, 500);
    };

    var init = function(gameId){
        setGameId(gameId);
        Template.render('popup-game-password', {gameId: gameId}, function(html){
            $.popupManager.dialog(html, {
                onShow: initContent,
                okHandler: submitForm,
                okButtonText: "CONTINUE"
            });
        });
    };

    return {
        init : init
    };
})(jQuery);