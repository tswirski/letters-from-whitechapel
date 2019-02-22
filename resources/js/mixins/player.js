var Player = (function($){
    var CLASS = 'player';
    var SELECTOR = '.' + CLASS;
    var CLASS_IS_FRIEND = 'friend';
    var CLASS_MY_SELF = 'me';

    var ATTR_NICKNAME = 'data-nickname';
    var ATTR_USER_ID = 'data-user-id';

    var player = function($player){
        /**
         * Retunrs player userId
         * @returns {int}
         */
        this.getUserId = function(){
            return $player.attr(ATTR_USER_ID);
        };

        /**
         * Return related jQuery object
         * @return object
         */
        this.getJQueryObject = function(){
            return $player;
        };

        /**
         * Removes player object from DOM
         */
        this.remove = function(){
            Template.call(function(){
                $player.remove();
            });
        };

        /**
         * Returns TRUE if player is our friend, FALSE otherwsie
         * @returns boolean
         */
        this.isFriend = function(){
            return $player.hasClass(CLASS_IS_FRIEND);
        };

        /**
         * Returns TRUE if object point to our account, FALSE otherwise
         * @returns bollean
         */
        this.isMe = function(){
            return $player.hasClass(CLASS_MY_SELF);
        };

        /**
         * Sets FRIEND flag
         */
        this.setFriendTrue = function(){
          Template.call(function(){
             $player.addClass(CLASS_IS_FRIEND);
          });
        };

        /**
         * Unset friend flag
         */
        this.setFriendFalse = function(){
            Template.call(function(){
                $player.removeClass(CLASS_IS_FRIEND);
            });
        };
    };

    /**
     * Wyszukije obiekt użytkownika w elemencie DOM lub kolekcji
     * @param {object} $object
     * @param {int} nickname
     * @returns object
     */
    var findIn = function($object, userId){
        var selector = userId !== undefined ? SELECTOR + '[data-user-id="' + userId + '"]' : SELECTOR;
        var $player = $object.find(selector);

        if($player.length > 1) {
            throw "More then $player found in given DOM object";
        }

        if($player.length === 0){
            throw "None $player elements found in given DOM object";
        }

        return (new player($player));
    };

    /**
     * Tworzy obiekt gracza na podstawie obiektu jQuery
     * @param object $player
     * @returns object
     */
    var instance = function($player){
      return (new player($player));
    };

    /**
     * Renderuje obiekt gracza
     * @param int userId
     * @param string nickname
     * @param string avatarUrl
     * @param boolean isMyAccount
     * @param boolean isMyFriend
     * @param callable onSuccessCallback
     */
    var render = function(userId, nickname, avatarUrl, isMyAccount, isMyFriend, onSuccessCallback){
        if(onSuccessCallback === undefined){
            onSuccessCallback = function(){};
        }
        Template.render('player', {
            nickname: nickname,
            avatarUrl: avatarUrl,
            userId: userId,
            isMyAccount: isMyAccount,
            isMyFriend: isMyFriend
        }, onSuccessCallback);
    };

    /**
     * Usuwa wszystkie instancje gracza z danego obiektu DOM
     * @param $object
     * @param userId
     */
    var removeFrom = function($object, userId){
        $object.find(getSelector(userId)).remove();
    };

    /**
     * Zwraca selektor dla gracza o podanym userId
     * @param int userId
     * @returns {string}
     */
    var getSelector = function(userId){
        return userId !== undefined ? SELECTOR + '[data-user-id="' + userId + '"]' : SELECTOR;
    };

    return {
        CLASS : CLASS,
        SELECTOR : SELECTOR,
        getSelector: getSelector,
        //CLASS_IS_FRIEND: CLASS_IS_FRIEND,
        //CLASS_MY_SELF: CLASS_MY_SELF,
        //DATA_USER_ID:  DATA_USER_ID,
        //DATA_NICKNAME: DATA_NICKNAME,
        removeFrom: removeFrom,
        instance: instance,
        findIn: findIn,
        render : render
    };

})(jQuery);