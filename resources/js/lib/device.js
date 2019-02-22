var Device = (function($){

    var _isTouchScreen = false;
    $(document).one('touchstart', function(){
       _isTouchScreen = true;
    });

    var isTouchScreen = function(){
        return _isTouchScreen;
    };

    return {
        isTouchScreen : isTouchScreen
    };

})(jQuery);