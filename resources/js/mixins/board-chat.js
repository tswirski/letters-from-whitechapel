var boardChat = (function($){
    var SELECTOR_CHAT_BOX = '.boardGameChat';
    var SELECTOR_CHAT_INPUT = '#boardGameChatInput';
    var SELECTOR_CHAT_MESSAGES_BOX = '.boardGameChatMessages';

    /**
     * Returns jQuery object selected relatively to page selector.
     * @param {string} selector
     * @returns {*|jQuery}
     */
    var getBySelector = function(selector){
        return $(SELECTOR_CHAT_BOX).find(selector);
    };
    
    /**
     * Append message to chat
     * @param {string} nickname
     * @param {string} avatarUrl
     * @param {string} message
     */
    var addChatMessage = function(nickname, avatarUrl, message, userId){
        Template.renderAppend(
            getBySelector(SELECTOR_CHAT_MESSAGES_BOX),
            'chat.message',
            {
                nickname: nickname,
                avatarUrl: avatarUrl,
                message: message,
                userId: userId
            }, function($message){
                setTimeout(function(){
                    $message.fadeOut(1500);
                }, 5500);
            }
        );

        if( ! $.isActiveTab()){
            Sounds.soundMessage();
        }
    };

    /**
     * Append OWN message to chat
     * @param {string} message
     */
    var addMyChatMessage = function(nickname, avatarUrl, message, userId){
        addChatMessage(nickname, avatarUrl, message, userId)
        // Template.renderAppend(
        //     getBySelector(SELECTOR_CHAT_MESSAGES_BOX),
        //     'chat.message.self',
        //     {
        //         message: message
        //     },
        //     scrollToMessage
        // );
    };

    /**
     * Send message to serwer with websocket
     * @param {string} message
     */
    var sendChatMessage = _.debounce(function(message, rpcMethod, room){
        var data = {
            message: message,
            room: room
        };

        JsonRpc.request(rpcMethod, data);
    }, 300, true);


    /**
     * Init Chat and bind all events to chat jQuery/DOM object.
     * @param {string} room
     */
    var init = function(){
        getBySelector(SELECTOR_CHAT_INPUT).on('keydown', function(e){
            if(e.which === 13) {
                var inputText = $(this).val().trim();
                if(inputText != ''){
                    sendChatMessage(inputText, 'chat.message', 'board');
                }
                e.preventDefault();
                $(this).val(null);
            }
        });
    };

    return {
        init : init,
        addChatMessage : addChatMessage,
        addMyChatMessage : addMyChatMessage
    };
})(jQuery);