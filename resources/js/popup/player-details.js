/**
 * Created by lama on 2016-02-02.
 */

var popupPlayerDetails = (function($){
    var PAGE = '.popup[data-popup="player-details"]';
    var SELECTOR_ADD_FRIEND_BUTTON = '.addToFriends';
    var SELECTOR_REMOVE_FRIEND_BUTTON = '.removeFromFriends';

    var ATTR_POPUP_USER_ID = 'data-user-id';
    var ATTR_POPUP_NICKNAME = 'data-nickname';
    var TRUE = 'true';
    var FALSE = 'false';

    /**
     * Returns jQuery object selected relatively to page selector.
     * @param {string} selector
     * @returns {*|jQuery}
     */
    var getBySelector = function (selector) {
        return $(PAGE).find(selector);
    };

    var initContent = function(){
        getBySelector(SELECTOR_ADD_FRIEND_BUTTON).on('click', function(){
            var userId = $(PAGE).attr(ATTR_POPUP_USER_ID);
            var $friendButtonBox = $(this).parent();

            JsonRpc.request('player.linkFriends', { friendId: userId }, {
                onSuccess: function(){
                    $friendButtonBox.attr('data-is-friend', TRUE);
                    $(Player.getSelector(userId)).each(function(){
                        Player.instance($(this)).setFriendTrue();
                    });
                }
            });
        });

        getBySelector(SELECTOR_REMOVE_FRIEND_BUTTON).on('click', function(){
            var userId = $(PAGE).attr(ATTR_POPUP_USER_ID);
            var $friendButtonBox = $(this).parent();

            JsonRpc.request('player.unlinkFriends', { friendId: userId }, {
                onSuccess: function(){
                    $friendButtonBox.attr('data-is-friend', FALSE);
                    $(Player.getSelector(userId)).each(function(){
                        Player.instance($(this)).setFriendFalse();
                    });
                }
            });
        });
    };

    var init = function(userId){
        JsonRpc.request('player.details', { userId: userId }, {
            onSuccess: function(memberDetails){
                Template.render('popup-player-details', memberDetails, function($memberDetailsPopup){
                    $.popupManager.alert($memberDetailsPopup, {
                        onShow: initContent
                    });
                });
            }
        });
    };

    return {
      init : init
    };

})(jQuery);