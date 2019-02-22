var dashboardChat = (function($){
    var SELECTOR_CHAT_BOX = '#dashboardChatBox';
    var SELECTOR_CHAT_INPUT = '#dashboardChatInput';
    var SELECTOR_CHAT_MESSAGES_BOX = '#dashboardChatMessagesBox';

    /**
     * Returns jQuery object selected relatively to page selector.
     * @param {string} selector
     * @returns {*|jQuery}
     */
    var getBySelector = function(selector){
        return $(SELECTOR_CHAT_BOX).find(selector);
    };

    /** Scroll to message, update scrollbar plugin */
    var scrollToMessage = function($messageBox){
        getBySelector(SELECTOR_CHAT_MESSAGES_BOX).scrollTo($messageBox);
        getBySelector(SELECTOR_CHAT_MESSAGES_BOX).perfectScrollbar('update');
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
            },
            scrollToMessage
        );
        
        if( ! $.isActiveTab()){
            Sounds.soundMessage();
        }
    };

    /**
     * Append NOTIFICATION to chat
     * @param {string} nickname
     * @param {string} avatarUrl
     * @param {string} message
     */
    var addChatNotification = function(nickname, avatarUrl, message, userId){
        Template.renderAppend(
            getBySelector(SELECTOR_CHAT_MESSAGES_BOX),
            'chat.message.notification',
            {
                nickname: nickname,
                avatarUrl: avatarUrl,
                message: message,
                userId: userId
            },
            scrollToMessage
        );

        if( ! $.isActiveTab()){
            Sounds.soundMessage();
        }
    };
    
    /**
     * Append OWN message to chat
     * @param {string} message
     */
    var addMyChatMessage = function(message){
        Template.renderAppend(
            getBySelector(SELECTOR_CHAT_MESSAGES_BOX),
            'chat.message.self',
            {
                message: message
            },
            scrollToMessage
        );
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
    var init = function(room){
        getBySelector(SELECTOR_CHAT_MESSAGES_BOX).perfectScrollbar({suppressScrollX: true});
        getBySelector(SELECTOR_CHAT_INPUT).perfectScrollbar({suppressScrollX: true});

        getBySelector(SELECTOR_CHAT_INPUT).on('keydown', function(e){
            if(e.which === 13) {
                var inputText = $(this).val().trim();
                if(inputText != ''){
                    sendChatMessage(inputText, 'chat.message', room);
                }
                e.preventDefault();
                $(this).val(null);
            }
            $(this).perfectScrollbar('update');
        });
    };

    return {
        init : init,
        addChatMessage : addChatMessage,
        addMyChatMessage : addMyChatMessage,
        addChatNotification: addChatNotification
    };
})(jQuery);