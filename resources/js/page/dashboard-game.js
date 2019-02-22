var pageDashboardGame = (function($){
    var PAGE = '.page[data-page="dashboard-game"]';
//    var ATTR_PAGE_ADMIN = "data-admin";
    var ATTR_PAGE_ADMIN_OPERATING = 'data-admin-operating';

    var SELECTOR_QUIT_GAME_BUTTON = 'button[name="quitGame"]';
    var SELECTOR_ADMIN_BUTTON = 'button[name="adminMode"]';
    var SELECTOR_START_GAME_BUTTON = 'button[name="startGame"]';

    var SELECTOR_SLOT_GENERAL = '#gamePlayers';
    var SELECTOR_ROLE = '.gameRole';
    var SELECTOR_ROLE_SLOT = '.gameRoleSlot';

    var ATTR_ROLE_TAKEN = "data-taken";
    var ATTR_ROLE = "data-role";
    var ATTR_ROLE_OWN = "data-own";
    var TRUE = 'true';
    var FALSE = 'false';

    /**
     * Players collection, key {int} userId, value {object} $jQueryObject
     * @type {collection}
     */
    var $players = {};

    /**
     * Returns jQuery object selected relatively to page selector.
     * @param {string} selector
     * @returns {*|jQuery}
     */
    var getBySelector = function(selector){
        return $(PAGE).find(selector);
    };

    /**
     * Returns role jQuery-dom object for given role
     * @param string role
     * @returns {*|jQuery}
     */
    var getRoleBox = function(role){
        return getBySelector(SELECTOR_ROLE + '[data-role="' + role + '"]');
    };

    /**
     * Returns role slot object for role given by selector string
     * @param {string} roleSelector
     * @returns {object}
     */

    var getRoleSlot = function(role){
        return getRoleBox(role).find(SELECTOR_ROLE_SLOT);
    };

    /**
     * Load page content
     * @param templateData
     */
    var init = function(templateData){
        Template.render('page-dashboard-game', templateData, function($pageContent){
            Page.load($pageContent, initContent);
        });
    };

    /**
     * Returns true if player has admin mode enabled, false otherwise.
     * Mind that only host can enter admin mode.
     * @returns {boolean}
     */
    var isAdminMode = function(){
        return $(PAGE).attr(ATTR_PAGE_ADMIN_OPERATING) === TRUE ;
    };

    /**
     * Loads $player object to cache
     * @param {string} nickname
     * @param {string} avatarUrl
     * @param {int} userId
     * @param {boolean} isMyAccount
     * @param {boolean} isMyFriend
     * @return undefined
     */
    var addPlayer = function(userId, nickname, avatarUrl, isMyAccount, isMyFriend){
        Player.render(userId, nickname, avatarUrl, isMyAccount, isMyFriend, function($player){
            $players[userId] = $player;
            addPlayerToGeneralSlot(userId);
        });
    };

    /**
     * Remove player object from cache and remove all player instances from board.
     * @param int userId
     */
    var removePlayer = function(userId){
        Template.call(function(){
            delete($players[userId]);
            Player.removeFrom($(PAGE), userId);
        });
    };


    /**
     * Get clone of player jQuery object.
     * @param {int} userId
     * @returns {$object}
     */
    var getPlayerClone = function(userId){
        return $players[userId].clone();
    };

    /**
     * Removes player from DOM role box.
     * @param {string} role
     * @param {int} userId
     */
    var removePlayerFromRoleSlot = function(role, userId){
        Template.call(function(){
            var $slot = getRoleSlot(role);
            Player.findIn($slot, userId).remove();
            $slot.closest(SELECTOR_ROLE).attr(ATTR_ROLE_TAKEN, FALSE).attr(ATTR_ROLE_OWN, FALSE);
        });
    };

    /**
     * Add player to role player slot.
     * @param {string} role
     * @param {int} userId
     */
    var addPlayerToRoleSlot = function(role, userId){
        Template.call(function(){
            var $player = getPlayerClone(userId);
            var $slot = getRoleSlot(role);
            $slot.prepend($player);
            $slot.closest(SELECTOR_ROLE)
                .attr(ATTR_ROLE_TAKEN, TRUE)
                .attr(ATTR_ROLE_OWN, (Player.instance($player).isMe() ? TRUE : FALSE));
            initPlayerForRoleSlot($player);
        });
    };

    /**
     * Add player to general slot
     * @param int userId
     */
    var addPlayerToGeneralSlot = function(userId){
        Template.call(function(){
            var $player = getPlayerClone(userId);
            var $generalSlot = getBySelector(SELECTOR_SLOT_GENERAL);
            $generalSlot.prepend($player);
            $generalSlot.perfectScrollbar('update');
            initPlayerForGeneralSlot($player);
        });
    };

    /**
     * Animates error for given role
     * @param string role
     */
    var animateRoleError = function(role){
        getRoleBox(role).addClass('error').effect('pulsate', 300, function(){
            $(this).removeClass('error').show();
        });
    };

    /**
     * Init player object to use with ROLE Slot
     * @param {$object} $player
     * @return undefined
     */
    var initPlayerForRoleSlot = function($player){

    };

    /**
     * Init player object for use with GENERAL Slot
     * @param {$object} $player
     */
    var initPlayerForGeneralSlot = function($player){
        $player.on('click', function(){
            if(isAdminMode() && !Player.instance($player).isMe()){
               popupPlayerKickBan.init(Player.instance($player).getUserId());
               return;
            }
            dashboardPlayer.showDetails($player);
        });

        $player.on('mouseleave', function(){
            dashboardPlayer.hideDetails($player);
        });
    };

    /**
     * Init Page base intaractions
     */
    var initContent = function(){
        getBySelector(SELECTOR_QUIT_GAME_BUTTON).on('click', function(){
            JsonRpc.request('dashboard-game.quit');
        });

        getBySelector(SELECTOR_ADMIN_BUTTON).on('click', function(){
            $(PAGE).toggleAttr(ATTR_PAGE_ADMIN_OPERATING, TRUE, FALSE);
        });

        getBySelector(SELECTOR_START_GAME_BUTTON).on('click', function(){
           JsonRpc.request('dashboard-game.startGame');
        });

        getBySelector(SELECTOR_ROLE).on('click',function(){
            var $role = $(this);
            var role = $role.attr(ATTR_ROLE);

            if(isAdminMode()
                && $role.attr(ATTR_ROLE_OWN) === FALSE
                && $role.attr(ATTR_ROLE_TAKEN) === TRUE){
                JsonRpc.request('dashboard-game.openSlot', {
                    role:role
                });
                return;
            }

            JsonRpc.request('dashboard-game.claimToggleSlot', {
                role:role
            });
        });

        dashboardChat.init('game');
        getBySelector(SELECTOR_SLOT_GENERAL).perfectScrollbar('update');
    };


    return {
        addPlayer: addPlayer,
        //addPlayerToGeneralSlot: addPlayerToGeneralSlot,
        addPlayerToRoleSlot: addPlayerToRoleSlot,
        removePlayer: removePlayer,
        removePlayerFromRoleSlot: removePlayerFromRoleSlot,
        animateRoleError: animateRoleError,
        init: init,
        players : $players
    };
})(jQuery);