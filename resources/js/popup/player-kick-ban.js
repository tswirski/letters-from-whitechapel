/**
 * Created by lama on 2016-02-02.
 */
var popupPlayerKickBan = (function($){
    var PAGE = '.popup[data-popup="player-kick-ban"]';
    var SELECTOR_KICK_BUTTON = 'button[name="kickPlayer"]';
    var SELECTOR_BAN_BUTTON = 'button[name="banPlayer"]';


    /**
     * Returns jQuery object selected relatively to page selector.
     * @param {string} selector
     * @returns {*|jQuery}
     */
    var getBySelector = function (selector) {
        return $(PAGE).find(selector);
    };

    var initContent = function(userId, api){
        getBySelector(SELECTOR_KICK_BUTTON).on('click', function(){
            JsonRpc.request('dashboard-game.kickPlayer', {
                userId: userId
            });
            api.hide();
        });

        getBySelector(SELECTOR_BAN_BUTTON).on('click', function(){
            JsonRpc.request('dashboard-game.banPlayer', {
                userId: userId
            });
            api.hide();
        });
    };

    var init = function(userId){
        Template.render('popup-player-kick-ban', {userId: userId}, function($popupContent){
            $.popupManager.alert($popupContent, {
                onShow: function(event, api){
                    initContent(userId, api);
                }
            });
        });
    };

    return {
        init : init
    };

})(jQuery);