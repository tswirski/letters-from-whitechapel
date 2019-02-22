var Avatar = (function($){

    /** @var string */
    var _defaultAvatarPath;

    /**
     * Ustawia �cie�k� domy�lnego avatara
     * @param {string}
     * @returns {undefined}
     */
    var setDefaultAvatarPath = function(defaultAvatarPath){
        _defaultAvatarPath = defaultAvatarPath;
    }

    /**
     * Zwraca �cie�k� domy�lnego avatara
     * @returns {string}
     */
    var getDefaultAvatarPath = function(){
        return _defaultAvatarPath;
    }

    /**
     * Uaktualnia URL obrazk�w dla danego u�ytkownika (zdefiniowanego przez userId)
     * @param {int} userId
     * @param {string} url
     */
    var updateUrlForUserById = function(userId, url){
        $('.avatar[data-user-id="' + userId + '"]').each(function(){
           if($(this).is('img')){
               $(this).attr('src', url);
           } else {
               $(this).css('background-image', 'url(' + url + ')');
           }
        });
    };

    /**
     * Wywo�uje metod� rozg�aszaj�c� uaktualnienie avatara przez u�ytkownika.
     */
    var announceAvatarUpdate = function(){
        JsonRpc.notify('account.announceAvatarUpdate');
    };

    return {
        setDefaultAvatarPath: setDefaultAvatarPath,
        getDefaultAvatarPath: getDefaultAvatarPath,
        updateUrlForUserById: updateUrlForUserById,
        announceAvatarUpdate: announceAvatarUpdate
    }
})(jQuery);