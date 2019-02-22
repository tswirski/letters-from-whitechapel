var dashboardPlayer = (function($){
    /** Class added to $player to allow alter-style */
    var ATTR_PLAYER_DETAILS = 'data-details-visible';
    var TRUE = 'true';
    var FALSE = 'false';

    /** Classes of $playerDetails box */
    var SELECTOR_DETAILS_BOX = '.dashboardPlayerDetails';
    var SELECTOR_DETAILS_FRIEND_ADD_BUTTON = '.playerDetailsFriendAddButton';
    var SELECTOR_DETAILS_FRIEND_REMOVE_BUTTON = '.playerDetailsFriendRemoveButton';
    var SELECTOR_DETAILS_MORE_BUTTON = '.playerDetailsMoreButton';

    /**
     * Zwraca true jeśli gracz posiada wyświetlone szczegóy
     * @param $player
     */
    var isWithDetails = function($player){
        return $player.attr(ATTR_PLAYER_DETAILS) === TRUE;
    };

    /**
     * Show (ADD) player details box to player box (if not yet present).
     * @param $player
     */
    var showDetails = function($player){
        /** Player already has details shown */
        if(isWithDetails($player)){
            return;
        }

        JsonRpc.request('player.details', { userId : Player.instance($player).getUserId() }, {
            onSuccess: function(data){
                /** Hide all other player details */
                hideDetailsEverybody();

                Template.render('dashboard.player.details', data, function($details){
                    /** Add player details html to player box */
                    $player.attr(ATTR_PLAYER_DETAILS, TRUE);
                    $player.append($details);

                    /** Click on document to hide details */
                    $(document).one('click', function(){
                       hideDetails($player);
                    });

                    /** Bind ADD FRIEND button */
                    $details.find(SELECTOR_DETAILS_FRIEND_ADD_BUTTON).on('click', function() {
                        JsonRpc.request('player.linkFriends', { friendId: Player.instance($player).getUserId() }, {
                            onSuccess: function(){
                                Player.instance($player).setFriendTrue();
                            }
                        });
                    });

                    /** Bind REMOVE FRIEND button */
                    $details.find(SELECTOR_DETAILS_FRIEND_REMOVE_BUTTON).on('click', function() {
                        JsonRpc.request('player.unlinkFriends', { friendId: Player.instance($player).getUserId() }, {
                            onSuccess: function(){
                                Player.instance($player).setFriendFalse();
                            }
                        });
                    });

                    /** Bind MORE DETAILS button */
                    $details.find(SELECTOR_DETAILS_MORE_BUTTON).on('click', function() {
                        popupPlayerDetails.init(Player.instance($player).getUserId());
                    });

                    /** Show Details Box*/
                    $details.fadeIn(500);
                });
        }});
    };

    /**
     * Hide (REMOVE) player details for each player that has one.
     */
    var hideDetailsEverybody = function(){
        $(Player.SELECTOR).filter('[' + ATTR_PLAYER_DETAILS + ' = "' + TRUE +'"]').each(function(){
            hideDetails($(this));
        });
    };

    /**
     * Hide (REMOVE) player details box from player box (if available).
     * @param $player
     */
    var hideDetails = function($player){
        Template.call(function() {
            $player.attr(ATTR_PLAYER_DETAILS, FALSE);
            $player.find(SELECTOR_DETAILS_BOX).remove();
        });
    };

    return {
        showDetails : showDetails,
        hideDetails : hideDetails,
        hideDetailsEverybody : hideDetailsEverybody
    };

})(jQuery);