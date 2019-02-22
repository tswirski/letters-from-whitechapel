var Avatar = (function($){

    /** @var string */
    var _defaultAvatarPath;

    /**
     * Ustawia œcie¿kê domyœlnego avatara
     * @param {string}
     * @returns {undefined}
     */
    var setDefaultAvatarPath = function(defaultAvatarPath){
        _defaultAvatarPath = defaultAvatarPath;
    }

    /**
     * Zwraca œcie¿kê domyœlnego avatara
     * @returns {string}
     */
    var getDefaultAvatarPath = function(){
        return _defaultAvatarPath;
    }

    /**
     * Uaktualnia URL obrazków dla danego u¿ytkownika (zdefiniowanego przez userId)
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
     * Wywo³uje metodê rozg³aszaj¹c¹ uaktualnienie avatara przez u¿ytkownika.
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