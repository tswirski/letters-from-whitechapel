/**
 * Created by lama on 2016-07-23.
 */
var Sounds = (function($){

    var _soundMessage = new Howl({
        html5: true,
        src:['resources/sounds/sound.message.mp3']
    });

    var _soundPopup = new Howl({
        html5: true,
        src:['resources/sounds/sound.popup.mp3']
    });

    return {
        soundMessage : function(){ _soundMessage.play();},
        soundPopup : function(){ _soundPopup.play(); }
    };
})(jQuery);