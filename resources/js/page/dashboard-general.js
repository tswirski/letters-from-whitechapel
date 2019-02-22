var pageDashboardGeneral = (function () {
    var PAGE = '.page[data-page="dashboard-general"]';
    var ATTR_PAGE_SHOW_GAMES = "data-show-games";
    var TRUE = "true";
    var FALSE = "false";

    var SELECTOR_PLAYERS_LIST = '#playersList';
    var SELECTOR_NEW_GAME_BUTTON = 'button[name="createNewGame"]';
    var SELECTOR_GAMELIST_BUTTON = 'button[name="recentGames"]';

    var SELECTOR_GAMELIST = '#gameList';
    var SELECTOR_GAME = '.game';
    var ATTR_GAME_ID = 'data-game-id';
    var ATTR_GAME_USING_PASSWORD = 'data-using-password';

    /**
     * Returns jQuery object selected relatively to page selector.
     * @param {string} selector
     * @returns {*|jQuery}
     */
    var getBySelector = function(selector){
      return $(PAGE).find(selector);
    };


    /**
     * Add player to chat member list
     * @param {string} nickname
     * @param {string} avatarUrl
     * @param {int} userId
     * @param {boolean} isMyAccount
     * @param {boolean} isMyFriend
     * @return {undefined}
     */
    var _addPlayer = function(nickname, avatarUrl, userId, isMyAccount, isMyFriend, method){
        Player.render(userId, nickname, avatarUrl, isMyAccount, isMyFriend,
            function($player, data){
                $player.on('click', function(){
                    dashboardPlayer.showDetails($player);
                });

                $player.on('mouseleave', function(){
                    dashboardPlayer.hideDetails($player);
                });

                getBySelector(SELECTOR_PLAYERS_LIST)[method]($player);
                getBySelector(SELECTOR_PLAYERS_LIST).perfectScrollbar('update');
            }
        );
    };

    var addPlayer = function(nickname, avatarUrl, userId, isMyAccount, isMyFriend){
        _addPlayer(nickname, avatarUrl, userId, isMyAccount, isMyFriend, 'prepend');
    };


    /**
     * Remove player from chat member list by userId
     * @param {int} userId
     */
    var removePlayer = function(userId){
        Player.findIn(getBySelector(SELECTOR_PLAYERS_LIST), userId).remove();
    };

    /**
     * Add game to listed games
     * @param string gameId
     * @param boolean usingPassword
     */
    var addGame = function(gameId, usingPassword, nickname, avatarUrl, playerCount, randomJackHideout ){
        Template.render('dashboard.game', {
            gameId: gameId,
            usingPassword: usingPassword,
            nickname: nickname,
            avatarUrl: avatarUrl,
            playerCount: playerCount,
            randomJackHideout : randomJackHideout
        }, function($game){
            getBySelector(SELECTOR_GAMELIST).prepend($game);
            $game.on('click', function(){
               JsonRpc.request('dashboard-game.join', {
                  gameId: gameId
               });
            });
        });
    };

    /**
     * Get game object
     * @param {string} gameId
     * @returns {object}
     */
    var getGameById = function(gameId){
      return getBySelector(SELECTOR_GAMELIST).find(SELECTOR_GAME).filter('[' + ATTR_GAME_ID + '="' + gameId +'"]');
    };

    /**
     * Removes game object from DOM
     * @param {string} gameId
     * @returns {undefined}
     */
    var removeGame = function(gameId){
      Template.call(function(){
          getGameById(gameId).remove();
      });
    };

    /**
     * Update player count for game
     * @param {string} gameId
     * @param {int} playerCount
     */
    var updateGame = function(gameId, playerCount){
        Template.call(function(){
           getGameById(gameId).find('.gamePlayerCount').text(playerCount);
        });
    };

    /**
     * Initialize page content
     */
    var initContent = function(html){
        getBySelector(SELECTOR_PLAYERS_LIST).perfectScrollbar({suppressScrollX: true});

        /** New Game Button */
        getBySelector(SELECTOR_NEW_GAME_BUTTON).on('click', function(){
            popupNewGame.init();
        });

        getBySelector(SELECTOR_GAMELIST_BUTTON).on('click', function(){
            $(PAGE).toggleAttr(ATTR_PAGE_SHOW_GAMES, FALSE, TRUE);
        });

        dashboardChat.init('general');
    };

    /**
     * Render page
     */
    var init = function(){
        $.fadeNavManager.showUserMenu();
        Template.render('page-dashboard-general', {}, function($pageContent){
            Page.load($pageContent);
            initContent();
        });
    };



    return {
        init: init,
        addPlayer : addPlayer,
        removePlayer: removePlayer,
        addGame: addGame,
        removeGame: removeGame,
        updateGame: updateGame
    };
})();