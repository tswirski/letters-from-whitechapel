var pageBoard = (function($){
    var PAGE = '.page[data-page="board"]';
    var FALSE = 'false';
    var TRUE = 'true';

    /**
     * BOARD
     */
    var SELECTOR_BOARD = '.board';
    var BOARD_NATIVE_WIDTH = 2100;
    var BOARD_NATIVE_HEIGHT = 1406;
    var ATTR_BOARD_IMAGE = 'data-image';

    // HIDEOUT
    var SELECTOR_HIDEOUT = '.hideout';
    var CLASS_HIDEOUT_CLUE_TOKEN = 'clue';
    var CLASS_HIDEOUT_MURDER_TOKEN = 'murder';
    var CLASS_HIDEOUT_SELECTABLE = 'selectable';
    var CLASS_HIDEOUT_HOVER = 'hover';
    var ATTR_HIDEOUT_ID = 'data-id';
    var COLOR_HIDEOUT_HOVER = [255, 255, 255];

    // HIDEOUT WOMAN TOKEN
    var CLASS_HIDEOUT_WRETCHED_MARKED_TOKEN = "wretched-marked";
    var CLASS_HIDEOUT_WRETCHED_BLANK_TOKEN = "wretched-blank";
    var TOKEN_WRETCHED_BLANK = 'blank';
    var TOKEN_WRETCHED_MARKED = 'marked';
    
    // JUNCTION
    var SELECTOR_JUNCTION = '.junction';
    var CLASS_JUNCTION_SELECTABLE = 'selectable';
    var CLASS_JUNCTION_HOVER = 'hover';
    var ATTR_JUNCTION_ID = 'data-id';
    var COLOR_JUNCTION_HOVER = [0, 0, 0];

    // JUNCTION POLICE PAWN
    var CLASS_JUNCTION_POLICE_PAWN = 'police-pawn';
    var ATTR_POLICE_PAWN_TYPE = 'data-pawn';

    /**
     * GUI
     */
    // MINI - MAP
    var SELECTOR_MAP_MARKER = '.mapMarker';
    var SELECTOR_MAP_BOX = '.mapMarkerBox';

    // ROLES
    var SELECTOR_ROLE_BOX = '.boardRole';
    var SELECTOR_ROLE_PLAYER_SLOT = '.playerSlot';
    var SELECTOR_ROLE_PLAYER_NICKNAME = '.playerNickname';
    var ATTR_ROLE_CHIEF_OF_INVESTIGATION = 'data-chief-of-investigation';
    var ATTR_ROLE_ACTIVE = 'data-active';
    var CLASS_ROLE_HIGHLIGHT_DIM = 'highlightDim';

    // LOG
    var SELECTOR_LOG_WIDGET_BOX = '.boardLog';
    var SELECTOR_HISTORY_LOG_BOX = '.boardLogHistory';
    var SELECTOR_RECENT_LOG_BOX = '.boardLogRecent';
    var SELECTOR_LOG_ENTRY = '.boardLogEntry';
    var SELECTOR_LOG_CLOSE_BUTTON = '.boardLogHistoryClose';

    // DAY COUNTER
    var SELECTOR_DAY_COUNTER = '.dayCounterBox';
    var SELECTOR_DAY_COUNTER_DISPLAY = '.dayCounterDisplay span';
    var SELECTOR_DAY_COUNTER_WHITE_PAWN_2 = '.whitePawn[data-pawn="2"]';

    // READINESS
    var SELECTOR_READINESS_BOX = '.policeOfficerReadiness';
    var SELECTOR_READINESS_BUTTON = SELECTOR_READINESS_BOX + ' .button';
    var SELECTOR_READINESS_ROLE_MARKER = '.readinessMarker';

    // ROLES
    var ROLE_JACK = 'jack';

    /**
     * Check if event is supported according to device type
     * @param event
     * @returns boolean
     */
    var isSupportedEvent = function(event){
        if(Device.isTouchScreen()) {
            return _.indexOf(['touchstart', 'touchmove', 'touchend'], event.type) !== -1;
        }
        return true;
    };

    /**
     * Returns jQuery object selected relatively to page selector.
     * @param {string} selector
     * @returns {*|jQuery}
     */
    var getBySelector = function(selector){
        return $(PAGE).find(selector);
    };

    /*****************
     * BOARD
     ****************/

    /**
     * JUNCTIONS
     */

    /**
     * Returns jQuery junction object by ID
     * @param int id
     * @returns {*|jQuery}
     */
    var getJunctionById = function(id){
        return getBySelector(SELECTOR_JUNCTION).filterAttr([[ATTR_JUNCTION_ID, id]]);
    };

    /**
     * Returns jquery junction object collection by IDs
     * @param array IDs (strings or integers - both are supported)
     * @returns array
     */
    var getJunctionsByIDs = function(IDs){
        IDs = _.map(IDs, function(val){ return parseInt(val); });

        return getBySelector(SELECTOR_JUNCTION).filter(function(){
            return _.contains(IDs, parseInt($(this).attr(ATTR_JUNCTION_ID)));
        });
    };

    /**
     * @param object $junction
     * @returns int
     */
    var getJunctionId = function($junction){
        return $junction.attr(ATTR_JUNCTION_ID);
    };

    /**
     * Returns TRUE if junction is selectable, false otherwise
     * @param object $junction
     * @returns boolean
     */
    var isJunctionSelectable = function($junction){
        return $junction.hasClass(CLASS_JUNCTION_SELECTABLE);
    };

    var disableJunctionHoverForIDs = function(junctionIDs){
      _.each(junctionIDs, disableJunctionHover);
    };

    /**
     * Disable Junction hover animation by id
     * @param int $junctionId
     */
    var disableJunctionHover = function(junctionId){
        Template.call(function(){
            var $junction = getJunctionById(junctionId);
            $junction
                .removeClass(CLASS_JUNCTION_SELECTABLE)
                .off('click');

            if ($junction.hasClass(CLASS_JUNCTION_HOVER)) {
                hideHoverAnimation($junction);
            }
        });
    };

    /**
     * Disables junction hover animation for all junctions out there.
     */
    var disableAllJunctionHover = function() {
        Template.call(function(){
            getBySelector(SELECTOR_JUNCTION).each(function () {
                var $junction = $(this);
                $junction
                    .removeClass(CLASS_JUNCTION_SELECTABLE)
                    .off('click');

                if ($junction.hasClass(CLASS_JUNCTION_HOVER)) {
                    hideHoverAnimation($junction);
                }
            });
        });
    };

    /**
     * Enable junction hover animation for junctions by given IDs.
     * (Junctions will be placed on board but will remain transparent -
     * however they will be hoverable)
     */
    var enableJunctionHoverForIDs = function(IDs){
        Template.call(function(){
            var $junctions = getJunctionsByIDs(IDs);
            $junctions.addClass(CLASS_JUNCTION_SELECTABLE);

            var $junction = $junctions.filter('.' + CLASS_JUNCTION_HOVER);
            if($junction.length > 0){
                showHoverAnimation($junction, COLOR_JUNCTION_HOVER);
            }
        });
    };

    /**
     * HIDEOUTS
     */

    /**
     * Returns jQuery hideout object by ID
     * @param int id
     * @returns {*}
     */
    var getHideoutById = function(id){
        return getBySelector(SELECTOR_HIDEOUT).filterAttr([[ATTR_JUNCTION_ID, id]]);
    };

    /**
     * @param object $hideout
     * @returns int
     */
    var getHideoutId = function($hideout)  {
        return $hideout.attr(ATTR_HIDEOUT_ID)
    };


    /**
     * Returns TRUE if hideout is selectable, false otherwise
     * @param object $hideout
     * @returns boolean
     */
    var isHideoutSelectable = function($hideout){
        return $hideout.hasClass(CLASS_HIDEOUT_SELECTABLE);
    };

    /**
     * Returns jquery hideout object collection by IDs
     * @param array IDs (strings or integers - both are supported)
     * @returns array
     */
    var getHideoutsByIDs = function(IDs){
        IDs = _.map(IDs, function(val){ return parseInt(val); });

        return getBySelector(SELECTOR_HIDEOUT).filter(function(){
          return _.contains(IDs, parseInt($(this).attr(ATTR_HIDEOUT_ID)));
        });
    };

    /**
     * Disables hideout hover animation for all hideouts out there.
     */
    var disableAllHideoutHover = function() {
        Template.call(function(){
            getBySelector(SELECTOR_HIDEOUT).each(function () {
                var $hideout = $(this);
                $hideout
                    .removeClass(CLASS_HIDEOUT_SELECTABLE)
                    .off('click');

                if ($hideout.hasClass(CLASS_HIDEOUT_HOVER)) {
                    hideHoverAnimation($hideout);
                }
            });
        });
    };

    /**
     * disable hideout hover for given Id
     * @param hideoutId
     */
    var disableHideoutHoverById = function(hideoutId){
        Template.call(function () {
            getHideoutById(hideoutId)
                .removeClass(CLASS_HIDEOUT_SELECTABLE)
                .off('click');
        });
    };

    /**
     * Enable hideout hover animation Show hideouts by given IDs.
     * (Hideouts will be placed on board but will remain transparent -
     * however they will be hoverable)
     */
    var enableHideoutHoverForIDs = function(IDs){
        Template.call(function() {
            var $hideouts = getHideoutsByIDs(IDs);
            $hideouts.addClass(CLASS_HIDEOUT_SELECTABLE);

            var $hideout = $hideouts.filter('.' + CLASS_HIDEOUT_HOVER);
            if ($hideout.length > 0) {
                showHoverAnimation($hideout, COLOR_HIDEOUT_HOVER);
            }
        });
    };

    /**
     * Tag hideout as murder scene
     * @param int hideoutId
     */
    var putMurderSceneToken = function(hideoutId){
        Template.call(function(){
            getHideoutById(hideoutId).addClass(CLASS_HIDEOUT_MURDER_TOKEN);
        });
    };

    /**
     * Tag hideout as clue
     * @param int hideoutId
     */
    var putClueToken = function(hideoutId){
        Template.call(function(){
            getHideoutById(hideoutId).addClass(CLASS_HIDEOUT_CLUE_TOKEN);
        });
    };

    /**
     * Remove clues from board
     */
    var removeClueTokens = function(){
        Template.call(function(){
            getBySelector(SELECTOR_HIDEOUT).removeClass(CLASS_HIDEOUT_CLUE_TOKEN);
        });
    };

    /**
     * Animate police action
     * @param int hideoutId
     * @param string action
     */
    var policeActionAnimation = function(hideoutId, action){
        Template.call(function () {
            var $actionAnimation = $('<div>');
            $actionAnimation.addClass('action').attr('data-action', action);
            getHideoutById(hideoutId).find('.animation').append($actionAnimation);
            $actionAnimation.fadeOut(3000, function(){
               $(this).remove();
            });
        });
    };

    /** Jack map parker */
    var $jackMarker = $([]);

    var removeJackMarker = function(){
        $jackMarker.remove();
    };

    /**
     * Place or move Jack Marker
     * @param hideoutId
     */
    var putJackMarker = function(hideoutId){
        removeJackMarker();
        $jackMarker = $('<img/>').addClass('marker').attr('src', location.href + 'resources/images/animation-rotate.gif');
        getHideoutById(hideoutId).find('.animation').append($jackMarker);
    };


    /**
     * Put Wretched Token
     * @param int id
     * @param string type
     */
    var putWretchedToken = function(hideoutId, tokenType){
        Template.call(function() {
            if(tokenType === TOKEN_WRETCHED_BLANK) {
                getHideoutById(hideoutId).addClass(CLASS_HIDEOUT_WRETCHED_BLANK_TOKEN);
            } else if (tokenType === TOKEN_WRETCHED_MARKED){
                getHideoutById(hideoutId).addClass(CLASS_HIDEOUT_WRETCHED_MARKED_TOKEN);
            }
        });
    };

    /**
     * Remove single wretched token from hideout
     * @param object $hideout
     */
    var removeWretchedToken = function(hideoutId, tokenType){
        Template.call(function() {
            if(tokenType === TOKEN_WRETCHED_BLANK) {
                getHideoutById(hideoutId).removeClass(CLASS_HIDEOUT_WRETCHED_BLANK_TOKEN);
            } else if (tokenType === TOKEN_WRETCHED_MARKED){
                getHideoutById(hideoutId).removeClass(CLASS_HIDEOUT_WRETCHED_MARKED_TOKEN);
            }
        });
     };

    /**
     * Remove All Wretched Tokens
     */
    var removeWretchedTokens = function(tokenType){
        Template.call(function() {
            if(tokenType === TOKEN_WRETCHED_BLANK) {
                getBySelector(SELECTOR_HIDEOUT).removeClass(CLASS_HIDEOUT_WRETCHED_BLANK_TOKEN);
            } else if (tokenType === TOKEN_WRETCHED_MARKED){
                getBySelector(SELECTOR_HIDEOUT).removeClass(CLASS_HIDEOUT_WRETCHED_MARKED_TOKEN);
            }
        });
    };

    /**
     * Move Woman Token
     * @param int fromHideoutId
     * @param int toHideoutId
     */
    var moveWomanToken = function(fromHideoutId, toHideoutId){
        Template.call(function() {
            removeWretchedToken(fromHideoutId, TOKEN_WRETCHED_MARKED);
            putWretchedToken(toHideoutId, TOKEN_WRETCHED_MARKED);
        });
    };


    /**
     * Place woman tokens
     * @param array junctionIDs
     */
    var putWomenTokens = function(hideoutIDs){
      _.each(hideoutIDs, function (hideoutId) {
         putWretchedToken(hideoutId, TOKEN_WRETCHED_MARKED); 
      });
    };



    /**
     * POLICE OFFICER PAWNS
     */

    /**
     * Puts pawn on junction given by ID.
     * @param int junctionId
     * @param string role
     */
    var putPolicePawn = function(junctionId, pawnType){
        Template.call(function() {
            getJunctionById(junctionId)
                .addClass(CLASS_JUNCTION_POLICE_PAWN)
                .attr(ATTR_POLICE_PAWN_TYPE, pawnType);
        });
    };

    /**
     * Put Police Pawns
     * @param pawns as role => junctionId
     */
    var putPolicePawns = function(pawns){
        _.each(pawns, function(junctionId, role){
           putPolicePawn(junctionId, role);
        });
    };

    /**
     * Remove Police Pawns from board
     */
    var removePolicePawns = function(){
        Template.call(function() {
            getBySelector(SELECTOR_JUNCTION)
                .removeClass(CLASS_JUNCTION_POLICE_PAWN)
                .attr(ATTR_POLICE_PAWN_TYPE, null);
        });
    };


    /**
     * Remove Police Pawn from given junctionId
     * @param int junctionId
     */
    var removePolicePawn = function(junctionId){
        Template.call(function() {
            getJunctionById(junctionId)
                .removeClass(CLASS_JUNCTION_POLICE_PAWN)
                .attr(ATTR_POLICE_PAWN_TYPE, null);
        });
    };

    /**
     * Put Police Pawn Markers
     * @param junctionIDs
     */
    var putPolicePawnPlaceHolders = function(junctionIDs){
        _.each(junctionIDs, function(junctionId){
            putPolicePawn(junctionId, 'placeholder');
        });
    };

    /** Police Active Pawn map marker */
    var $policePawnMarker = $([]);

    var removePolicePawnMarker = function(){
        $policePawnMarker.remove();
    };

    /**
     * Place or move Police Pawn Marker
     * @param junctionId
     */
    var putPolicePawnMarker = function(junctionId){
        removePolicePawnMarker();
        $policePawnMarker = $('<img/>').addClass('marker').attr('src', location.href + 'resources/images/animation-rotate.gif');
        getJunctionById(junctionId).find('.animation').append($policePawnMarker);
    };

    /**
     * GUI - PLAYER ROLES
     */

    /**
     * Returns $roleBox jQuery object for given role
     * @param {string} role
     * @returns {*|jQuery}
     */
    var getRoleBox = function(role){
        return getBySelector(SELECTOR_ROLE_BOX + '[data-role="' + role + '"]');
    };

    /**
     * Returns $playerSlot jQuery object for given role
     * @param {string} role
     * @returns {*|jQuery}
     */
    var getPlayerSlotByRole = function(role){
        return getRoleBox(role).find(SELECTOR_ROLE_PLAYER_SLOT);
    };

    /**
     * Set Role Ready
     * @param string roles
     */
    var setRolesReadinessMarkers = function(roles){
        Template.call(function(){
           _.each(roles, function(role){
               getRoleBox(role).find(SELECTOR_READINESS_ROLE_MARKER).fadeIn(500);
           });
        });
    };

    /**
     * Remove Roles Readiness Markers (all roles)
     */
    var removeRolesReadinessMarkers = function(){
        Template.call(function(){
            _.delay(function(){
                getBySelector(SELECTOR_READINESS_ROLE_MARKER).fadeOut(500);
            }, 500);
        });
    };

    var showReadinessConfirmBox = function(){
        Template.call(function(){
            getBySelector('.policeOfficerReadiness').fadeIn(500);
        });
    };

    var hideReadinessConfirmBox = function(){
        Template.call(function(){
            getBySelector('.policeOfficerReadiness').fadeOut(500);
        });
    };

    /**
     * Switch Chief Of Investigation to given role.
     * @param string chiefOfInvestigation
     */
    var switchChiefOfInvestigationByRole = function(role){
        Template.call(function(){
            getBySelector(SELECTOR_ROLE_BOX).attr(ATTR_ROLE_CHIEF_OF_INVESTIGATION, FALSE);
            getRoleBox(role).attr(ATTR_ROLE_CHIEF_OF_INVESTIGATION, TRUE);
        });
    };

    /**
     * Switch active role
     * @param string role - activeRole
     */
    var switchActiveRole = function(role){
        Template.call(function() {
            getBySelector(SELECTOR_ROLE_BOX).attr(ATTR_ROLE_ACTIVE, FALSE);
            getRoleBox(role).attr(ATTR_ROLE_ACTIVE, TRUE);
        });
    };

    /**
     * Set Active Roles (multiple)
     * @param array roles - activeRoles
     */
    var setActiveRoles = function(roles){
        Template.call(function() {
            getBySelector(SELECTOR_ROLE_BOX).attr(ATTR_ROLE_ACTIVE, FALSE);
            _.each(roles, function(role){
                getRoleBox(role).attr(ATTR_ROLE_ACTIVE, TRUE);
            });
        });
    };


    /**
     * Set player data for role's widget
     * @param data
     */
    var setRoles = function(players){
        /** Render player to socket */
        _.each(players, function(playerData, role){
            Template.renderHtml(getPlayerSlotByRole(role), 'player', playerData);
        });
    };

    /**
     * Calculates scale factor for board to fit the screen.
     * @param float baseWidth
     * @param float baseHeight
     * @param float maxWidth
     * @param float maxHeight
     * @returns {float}
     */
    var calcFitFactor = function(baseWidth, baseHeight, maxWidth, maxHeight){
        return Math.min( maxWidth / baseWidth, maxHeight / baseHeight);
    };

    var _updateMap = function(offsetLeft, offsetTop, width, height, parentWidth, parentHeight, method){
        var leftPercent = - offsetLeft / width * 100;
        var topPercent = - offsetTop / height * 100;

        var widthPercent = parentWidth / width * 100;
        var heightPercent = parentHeight / height * 100;

        // left limiter
        if(leftPercent < 0){
            widthPercent += leftPercent;
            leftPercent = 0;
        }

        // right limiter
        if(widthPercent + leftPercent > 100){
            var widthDiff = widthPercent + leftPercent - 100;
            widthPercent -= widthDiff;
        }

        // top limiter
        if(topPercent < 0){
            heightPercent += topPercent;
            topPercent = 0;
        }

        //bottom limiter
        if(heightPercent + topPercent > 100){
            var heightDiff = heightPercent + topPercent - 100;
            heightPercent -= heightDiff;
        }

        getBySelector(SELECTOR_MAP_MARKER)
            .finish()
            [method]({
                left: leftPercent + '%',
                top: topPercent + '%',
                width: widthPercent + '%',
                height: heightPercent + '%'
            });
    };

    /**
     * Update map marker according to given params.
     * @param float leftPercent
     * @param float topPercent
     * @param float widthPercent
     * @param float heightPercent
     */
    var updateMap = function(offsetLeft, offsetTop, width, height, parentWidth, parentHeight){
        _updateMap(offsetLeft, offsetTop, width, height, parentWidth, parentHeight, 'css');
    };

    /**
     * Update map marker according to given params.
     * @param float leftPercent
     * @param float topPercent
     * @param float widthPercent
     * @param float heightPercent
     */
    var updateMapAnimate = function(offsetLeft, offsetTop, width, height, parentWidth, parentHeight){
        _updateMap(offsetLeft, offsetTop, width, height, parentWidth, parentHeight, 'animate');
    };


    /**
     * LOADING SPINNER
     */

    /**
     * show loading spinner
     */
    var _loadingSpinner, showLoadingSpinner = function(){
        _loadingSpinner = new Spinner({
            id: 'spinner',
            radius: 90,
            sides: 3,
            depth: 9,
            colors: {
                stroke: '#8b0000',
                base: null,
                child: '#FFFFFF'
            },
            alwaysForward: true, // When false the spring will reverse normally.
            restAt: 0.5, // A number from 0.1 to 0.9 || null for full rotation
            renderBase: false
        });

        var springSystem = new rebound.SpringSystem();
        var loadingSpring = springSystem.createSpring(16, 5);
        _loadingSpinner.init(loadingSpring, true);
    };

    /**
     * hide loading spinner
     */
    var hideLoadingSpinner = function(){
        if(_loadingSpinner){
            _loadingSpinner.setComplete();
        }
        _loadingSpinner = null;
    };

    /**
     * INIT FUNCTIONS
     */

    /**
     * init board background image
     */
    var initBoardImage = function(onLoaded){
        var $board = getBySelector(SELECTOR_BOARD);
        var boardImageSrc = $board.attr(ATTR_BOARD_IMAGE);

        var boardImage = new Image();
        boardImage.onload = function(){
            hideLoadingSpinner();
            onLoaded();

            $board
                .css('background-image', 'url(' + boardImageSrc + ')')
                .fadeIn(300);
        };

        boardImage.onerror = function(){
            hideLoadingSpinner();
        };

        boardImage.src = boardImageSrc;
    };

    /**
     * Adjust board size along with junctions and hideouts
     * @param int hideoutSize
     * @param int junctionSize
     */
    var adjustBoardSize = function(hideoutSize, junctionSize){
        var fitToScreenFactor = calcFitFactor(BOARD_NATIVE_WIDTH, BOARD_NATIVE_HEIGHT, $(window).width(), $(window).height());

        /**
         * BOARD
         */

        /** Fit board to screen */
        getBySelector(SELECTOR_BOARD)
            .width(BOARD_NATIVE_WIDTH * fitToScreenFactor)
            .height(BOARD_NATIVE_HEIGHT * fitToScreenFactor);

        /**
         * HIDEOUTS
         */

        /** Clear hideout inline styles */
        getBySelector(SELECTOR_HIDEOUT).css({
            width: '',
            height: '',
            marginTop: '',
            marginLeft: '',
            fontSize: '',
            paddingTop: ''
        });

        var hideoutSize = parseFloat(getBySelector(SELECTOR_HIDEOUT).css('height'));
        var hideoutNewSize = hideoutSize * fitToScreenFactor;
        var hideoutMargin = (hideoutSize - hideoutNewSize) / 2;

        getBySelector(SELECTOR_HIDEOUT).each(function() {
            var $hideout = $(this);

            $hideout.css({
                width: hideoutNewSize + 'px',
                height: hideoutNewSize + 'px',
                marginTop: '+=' + hideoutMargin + 'px',
                marginLeft: '+=' + hideoutMargin + 'px',
                fontSize: parseFloat($hideout.css('fontSize')) * fitToScreenFactor + 'px',
                paddingTop: hideoutNewSize / 2 + 'px'
            });
        });

        /**
         * JUNCTIONS
         */

        /** Clear junction inline styles */
        getBySelector(SELECTOR_JUNCTION).css({
            width: '',
            height: '',
            marginTop: '',
            marginLeft: ''
        });

        var junctionSize = parseFloat(getBySelector(SELECTOR_JUNCTION).css('height'));

        var junctionNewSize = junctionSize * fitToScreenFactor;
        var junctionMargin = (junctionSize - junctionNewSize) / 2;

        getBySelector(SELECTOR_JUNCTION).css({
            width: junctionNewSize + 'px',
            height: junctionNewSize + 'px',
            marginTop: '+=' + junctionMargin + 'px',
            marginLeft: '+=' + junctionMargin + 'px'
        });
    };

    /**
     * ANIMATED HOVER
     */

    /**
     * Create animation for given $canvas element
     * @param bject $canvas
     * @param array color [r, g, b]
     * @private
     */
    var _canvasAnimation = function($canvas, color){
        $canvas.attr('height', $canvas.height());
        $canvas.attr('width', $canvas.width());

        var c = $canvas.get(0),
            ctx = c.getContext('2d'),
            pi = Math.PI,
            xCenter = $canvas.width()/2,
            yCenter = $canvas.height()/2,
            radius = $canvas.width()/3,
            startSize = radius/3,
            num=5,
            posX=[],
            posY=[],
            angle,
            size,
            i;

        var interval = setInterval(function() {
            if(! $canvas.is(':visible')){
                clearInterval(interval);
            }

            num++;
            ctx.clearRect ( 0 , 0 , xCenter*2 , yCenter*2 );
            for (i=0; i<9; i++){
                ctx.beginPath();
                ctx.fillStyle = 'rgba('+ color.join(',') +','+.1*i+')';
                if (posX.length==i){
                    angle = pi*i*.25;
                    posX[i] = xCenter + radius * Math.cos(angle);
                    posY[i] = yCenter + radius * Math.sin(angle);
                }
                ctx.arc(
                    posX[(i+num)%8],
                    posY[(i+num)%8],
                    startSize/9*i,
                    0, pi*2, 1);
                ctx.fill();
            }
        }, 110);
    };

    /**
     * Append animated canvas to $subject to indicate selection.
     * @param object $subject
     */

    var showHoverAnimation = function($subject, color){
        if($subject.find('canvas').length > 0){
            return false;
        }

        var $canvas = $('<canvas>');
        $subject.append($canvas);
        _canvasAnimation($canvas, color);
    };

    /**
     * Remove animated canvas from $subject
     * @param object $subject
     */
    var hideHoverAnimation = function($subject){
        $subject.find('canvas').remove();
    };

    /**
     * DAY COUNTER
     */

    /**
     * Sets day counter to given day
     * @param int day
     * @returns boolean
     */
    var setDay = function(day){
        if( ! _.contains([1,2,3,4], day)){
            return false;
        }

        Template.call(function(){
            var $dayDisplay = getBySelector(SELECTOR_DAY_COUNTER).find(SELECTOR_DAY_COUNTER_DISPLAY);
            var $whitePawn = getBySelector(SELECTOR_DAY_COUNTER).find(SELECTOR_DAY_COUNTER_WHITE_PAWN_2);

            $dayDisplay.hide(0, function(){
                $dayDisplay.html(day).fadeIn(200);
            });

            switch(day){
                case 3:
                    $whitePawn.transit({opacity: 1}, 200);
                    break;
                default:
                    $whitePawn.transit({opacity: .1}, 200);
                    break;
            }
        });

        return true;
    };

    /**
     * Set special move counter value
     * @param string moveType
     * @param int value
     */
    var setAvailableSpecialMoves = function(moveType, value) {
        Template.call(function(){
            getBySelector(['.', moveType, 'Counter'].join(''))
                .animate({color: 'transparent'}, 300,
                function () {
                    $(this).text(value);
                    $(this).animate({color: '#fff'});
                });
            });
    };


    /**
     * GAME LOG
     */

    /**
     * Add message to game log
     * @param string time
     * @param string message
     */
    var addLogMessage = function(time, message){
        Interval.call(function() {

            Template.render('board.log.entry', {
                time: time,
                message: message
            }, function (newLogEntryHtml) {
                var $recentLogBox = getBySelector(SELECTOR_RECENT_LOG_BOX);
                var $historyLogBox = getBySelector(SELECTOR_HISTORY_LOG_BOX);
                var $pastLogEntry = $recentLogBox.find(SELECTOR_LOG_ENTRY);
                var $newLogEntry = $(newLogEntryHtml);

                if ($pastLogEntry.length === 0) {
                    $recentLogBox.append($newLogEntry);
                    $newLogEntry.show(0);
                    return;
                }

                $pastLogEntry.fadeOut(200, function(){
                    $historyLogBox.append($pastLogEntry.detach().show(0));
                    $historyLogBox.scrollTo($pastLogEntry);
                    $historyLogBox.perfectScrollbar('update');
                    $recentLogBox.append($newLogEntry);
                    $newLogEntry.fadeIn(200);
                });

            });
        }, 2000, 'boardLogMessage');
    };

    /**
     * JACK KILL OR WAIT POPUP
     */

    var openKillOrWaitPopup = function(){
        Template.call(function(){
            getBySelector('.jackDecideToKillPopup').fadeIn(300, function () {
                $(this).find('.button').on('click', function(){
                    JsonRpc.request('board.killOrWait', {
                        action: $(this).attr('data-action')
                    });

                    closeKillOrWaitPopup();
                });
            });
        });
    };
    
    var closeKillOrWaitPopup = function(){
        Template.call(function(){
            getBySelector('.jackDecideToKillPopup').fadeOut(300, function () {
                $(this).find('.button').off('click');
            });
        });
    };

    /**
     * JACK ENTER HIDEOUT POPUP
     */
    
    var openEnterHideoutPopup = function(){
        Template.call(function(){
            getBySelector('.jackDecideToEnterHideoutPopup').fadeIn(300, function () {
                $(this).find('.button').on('click', function(){
                    JsonRpc.request('board.enterHideout', {
                        action: $(this).attr('data-action')
                    });

                    closeEnterHideoutPopup();
                });
            });
        });
    };

    var closeEnterHideoutPopup = function(){
        Template.call(function(){
            getBySelector('.jackDecideToEnterHideoutPopup').fadeOut(300, function () {
                $(this).find('.button').off('click');
            });
        });
    };


    /**
     * JACK MOVES TRACK
     */

    var SELECTOR_JACK_MOVE_COUNTER_ELEMENT = '.jackMovesOrdination span';
    var ATTR_JACK_MOVE_COUNTER_ELEMENT_ID = 'data-id';
    var ATTR_JACK_MOVE_COUNTER_ELEMENT_ENABLED = 'data-enabled';
    var ATTR_JACK_MOVE_COUNTER_ELEMENT_SELECTED = 'data-selected';
    var ATTR_JACK_MOVE_COUNTER_ELEMENT_HIDDEN = 'data-hidden';
    var SELECTOR_JACK_MOVE_TOKEN_BOX = '.jackMoveTokens';
    var SELECTOR_JACK_TRACK_DISPLAY = '.jackTrackDisplay';

    /**
     * Removes all Jack move tokens, restore
     * default move track options
     */
    var clearMoveTrack = function(){
        Template.call(function(){
            Interval.call(function(){
                getBySelector(SELECTOR_JACK_MOVE_TOKEN_BOX).find('span').fadeOut(500, function(){
                    $(this).remove();
                });

                getBySelector(SELECTOR_JACK_TRACK_DISPLAY).empty();
            }, 500, 'boardJackMoves');

            Interval.call(function(){
                getBySelector(SELECTOR_JACK_MOVE_COUNTER_ELEMENT)
                    .attr(ATTR_JACK_MOVE_COUNTER_ELEMENT_SELECTED, FALSE)
                    .attr(ATTR_JACK_MOVE_COUNTER_ELEMENT_HIDDEN, FALSE)
                    .attr(ATTR_JACK_MOVE_COUNTER_ELEMENT_ENABLED, FALSE);
            }, 300, 'boardJackMoves');
        });
    };

    /**
     * Set move track available moves to:
     * @param int number
     */
    var setAvailableMovesTo = function(value){
        Template.call(function(){
            Interval.call(function(){
                getBySelector(SELECTOR_JACK_MOVE_COUNTER_ELEMENT)
                    .slice(0, value)
                    .attr(ATTR_JACK_MOVE_COUNTER_ELEMENT_ENABLED, TRUE);
            }, 300, 'boardJackMoves');
        });
    };

    /**
     * Highlight current move, hide past move ordinals.
     * @param int value
     */
    var setCurrentMoveTo = function(value){
        Template.call(function(){
            Interval.call(function(){

                getBySelector(SELECTOR_JACK_MOVE_COUNTER_ELEMENT)
                    .slice(0, value - 1)
                    .attr(ATTR_JACK_MOVE_COUNTER_ELEMENT_HIDDEN, TRUE);

                getBySelector(SELECTOR_JACK_MOVE_COUNTER_ELEMENT)
                    .slice(value - 1, value)
                    .attr(ATTR_JACK_MOVE_COUNTER_ELEMENT_SELECTED, TRUE);

            }, 300, 'boardJackMoves');
        });
    };

    /**
     * Put Jack Move Token
     * @param string tokenType
     */
    var putMoveToken = function(tokenType){
        Template.call(function(){
            Interval.call(function() {
                var $moveToken = $('<span data-token="' + tokenType + '">').css('display', 'none');
                getBySelector(SELECTOR_JACK_MOVE_TOKEN_BOX).append($moveToken);
                $moveToken.fadeIn(500);
            }, 500, 'boardJackMoves');
        });
    };

    /**
     * Jack Add element to Track Display
     * @param array hideoutIDs
     */
    var addJackTrackElement = function(hideoutIDs) {
        Template.call(function () {
            Interval.call(function () {
                _.each(hideoutIDs, function (hideoutId) {
                    Interval.call(function () {
                        var $trackElement = $('<span>' + hideoutId + '</span>').css('display', 'none');
                        getBySelector(SELECTOR_JACK_TRACK_DISPLAY).append($trackElement);
                        $trackElement.fadeIn(500);
                    }, 500, 'boardJackMoves');
                });
            });
        });
    };

    /**
     * BOARD ACTION MENU
     */
        var SELECTOR_BOARD_MENU_PLACEMENT = '.boardActionMenuPlacement';
        var SELECTOR_BOARD_MENU_CLICK_POINT = '.boardActionMenuClickPoint';
        var SELECTOR_BOARD_MENU_BOX = '.boardActionMenuBox';

    /**
     *
     * @param event to get pageX, pageY coordinates from.
     * @param handler function, value returned from this function will be placed
     * as menu content. handler is also responsible for binding content handlers.
     */
    var openActionMenu = function(event, handler){
        var $menuPlacement = getBySelector(SELECTOR_BOARD_MENU_PLACEMENT);
        $menuPlacement.show();

        var $menuBox = getBySelector(SELECTOR_BOARD_MENU_BOX);
        $menuBox.html(handler());

        var xAlign = $menuBox.width() + event.pageX >  $(window).width() ? 'left' : 'right';
        var yAlign = $menuBox.height() + event.pageY > $(window).height() ? 'top' : 'bottom';

        var $clickPoint =  getBySelector(SELECTOR_BOARD_MENU_CLICK_POINT);
        $clickPoint.css({
            top: event.pageY,
            left: event.pageX
        }).fadeIn(300);

        $menuBox
            .css(xAlign, 0)
            .css(yAlign, 0);

        $menuPlacement.one('click', function(e){
            $clickPoint.fadeOut(300, function(){
                $menuPlacement.hide();
            });
            e.stopPropagation();
        });
    };

    var createMenuOption = function(id, name){
      return $('<div>')
          .addClass('boardActionMenuOption')
          .append($('<div>').addClass('optionSubjectId').text(id))
          .append($('<div>').addClass('optionName').text(name));
    };


    /**
     * Jack Chose Hideout menu
     */
    var _setActionMenuForHideoutSelection = function($hideout, e){
        openActionMenu(e, function () {
            var $hideHere = createMenuOption(getHideoutId($hideout), 'Use as Hideout');
            $hideHere.on('click', function () {
                JsonRpc.request('board.setJackHideout', {
                    hideoutId: getHideoutId($hideout)
                });
            });

            return $hideHere;
        });
    };

    var setActionMenuForHideoutSelection = function(){
        Template.call(function() {
            getBySelector(SELECTOR_HIDEOUT).on('click', function (e) {
                var $hideout = $(this);

                if (!isHideoutSelectable($hideout)) {
                    return;
                }

                _setActionMenuForHideoutSelection($hideout, e);
            });
        });
    };

    /**
     * Jack places wretched tokens menu
     */
    var _setActionMenuForWretchedTokens = function($hideout, e){
        JsonRpc.request('board.getWretchedPutTokenMenuData', { hideoutId: getHideoutId($hideout) }, {
            onSuccess: function(data){
                openActionMenu(e, function () {
                    var $menuOptions = $([]);

                    _.each(data, function(element){
                        var $menuOption = createMenuOption(element.id, element.description);
                        $menuOption.on('click', function () {
                            JsonRpc.request('board.setWretchedToken', {
                                hideoutId: element.id,
                                tokenType: element.action
                            });
                        });
                        $menuOptions = $menuOptions.add($menuOption);
                    });

                    return $menuOptions;
                });
            }
        });
    };

    var setActionMenuForWretchedTokens = function(){
        Template.call(function() {
            getBySelector(SELECTOR_HIDEOUT).on('click', function (e) {
                var $hideout = $(this);

                if (!isHideoutSelectable($hideout)) {
                    return;
                }

                _setActionMenuForWretchedTokens($hideout, e);
            });
        });
    };


    /**
     * Chief of investigation move WOMAN TOKEEN menu
     */
    var _setActionMenuForWomanTokenMove = function($hideout, e){
        JsonRpc.request('board.getWomanMovesMenuData', { hideoutId: getHideoutId($hideout) }, {
            onSuccess: function(data){
                openActionMenu(e, function () {
                    var $menuOptions = $([]);
                    _.each(data, function(element){
                        var $menuOption = createMenuOption(element.toHideoutId, element.description);
                        $menuOption.on('click', function () {
                            JsonRpc.request('board.moveWoman', {
                                fromHideoutId: element.fromHideoutId,
                                toHideoutId: element.toHideoutId
                            });
                        });
                        $menuOptions = $menuOptions.add($menuOption);
                    });

                    return $menuOptions;
                });
            }
        });
    };

    /**
     *
     * @param hideoutIDs
     */
    var setActionMenuForWomanTokenMove = function(hideoutIDs){
        Template.call(function() {
            enableHideoutHoverForIDs(hideoutIDs);
            _.each(hideoutIDs, function(hideoutId){
                getHideoutById(hideoutId).on('click', function (e) {
                    _setActionMenuForWomanTokenMove($(this), e);
                });
            });
        });
    };

    /**
     * Chief of Police re-allocating Police Pawns
     */
    var _setActionMenuForPolicePawnAllocation = function($junction, e){
        JsonRpc.request('board.getPolicePutPawnMenuData', { junctionId: getJunctionId($junction) }, {
            onSuccess: function(data){
                openActionMenu(e, function () {
                    var $menuOptions = $([]);

                    _.each(data, function(element){
                        var $menuOption = createMenuOption(element.id, element.description);
                        $menuOption.on('click', function () {
                            JsonRpc.request('board.setPolicePawn', {
                                junctionId: element.id,
                                pawnType: element.action
                            });
                        });
                        $menuOptions = $menuOptions.add($menuOption);
                    });
        
                    return $menuOptions;
                });
            }
        });
    };

    var setActionMenuForPolicePawnAllocation = function(){
        Template.call(function() {
            getBySelector(SELECTOR_JUNCTION).on('click', function (e) {
                var $junction = $(this);

                if (!isJunctionSelectable($junction)) {
                    return;
                }

                _setActionMenuForPolicePawnAllocation($junction, e);
            });
        });
    };
    
    /**
     * Jack Reveal Police Pawn Menu
     */

    var _setActionMenuForRevealPolicePawn = function($junction, e){
        openActionMenu(e, function () {
            var $menuOption = createMenuOption(getJunctionId($junction), 'Reveal');
            $menuOption.on('click', function () {
                JsonRpc.request('board.revealPolicePawn', {
                    junctionId: getJunctionId($junction)
                });
            });

            return $menuOption;
        });
    };

    var setActionMenuForRevealPolicePawn = function(){
        Template.call(function() {
            getBySelector(SELECTOR_JUNCTION).on('click', function (e) {
                var $junction = $(this);

                if (!isJunctionSelectable($junction)) {
                    return;
                }

                _setActionMenuForRevealPolicePawn($junction, e);
            });
        });
    };

    /**
     * Jack Kills a Woman Menu
     * @param int hideoutId
     */
    var _setActionMenuForKill = function(hideoutId){
        getHideoutById(hideoutId).on('click', function(e){
            JsonRpc.request('board.getKillWretchedMenuData', { hideoutId: hideoutId }, {
                onSuccess: function(data){
                    openActionMenu(e, function () {
                        var $menuOptions = $([]);

                        _.each(data, function(description, method){
                            var $menuOption = createMenuOption(hideoutId, description);
                            $menuOption.on('click', function () {
                                JsonRpc.request('board.kill', {
                                    hideoutId: hideoutId,
                                    method: method
                                });
                            });
                            $menuOptions = $menuOptions.add($menuOption);
                        });
                        return $menuOptions;
                    });
                }
           });
        });
    };


    var setActionMenuForKill = function(hideoutIDs){
        Template.call(function() {
            enableHideoutHoverForIDs(hideoutIDs);

            _.each(hideoutIDs, function(hideoutId){
                _setActionMenuForKill(hideoutId);
            });
        });
    };

    /**
     * Jack Move
     */
     var _createJackMoveMenuOption = function(actions, hideoutId){
        getHideoutById(hideoutId).on('click', function(e){
            openActionMenu(e, function () {
                var $menuOptions = $([]);

                _.each(actions, function(method){

                    var $menuOption = createMenuOption(hideoutId, method);
                    $menuOption.on('click', function () {
                        JsonRpc.request('board.moveJack', {
                            hideoutId: hideoutId,
                            method: method
                        });
                    });
                    $menuOptions = $menuOptions.add($menuOption);
                });

                return $menuOptions;
            });
        });
    };

    var JACK_MOVE_WALK = 'walk';
    var JACK_MOVE_CARRIAGE = 'carriage';
    var JACK_MOVE_ALLEY = 'alley';

    var setActionMenuForJackMove = function(availableMoves){
        Template.call(function() {
            var jackMoveActions = [];

            _.each(availableMoves[JACK_MOVE_WALK], function(hideoutId){
                if(jackMoveActions[hideoutId] === undefined){
                    jackMoveActions[hideoutId] = [];
                }
                jackMoveActions[hideoutId].push(JACK_MOVE_WALK)
            });

            _.each(availableMoves[JACK_MOVE_CARRIAGE], function(hideoutId){
                if(jackMoveActions[hideoutId] === undefined){
                    jackMoveActions[hideoutId] = [];
                }
                jackMoveActions[hideoutId].push(JACK_MOVE_CARRIAGE);
            });

            _.each(availableMoves[JACK_MOVE_ALLEY], function(hideoutId){
                if(jackMoveActions[hideoutId] === undefined){
                    jackMoveActions[hideoutId] = [];
                }
                jackMoveActions[hideoutId].push(JACK_MOVE_ALLEY)
            });

            _.each(jackMoveActions, function(actions, hideoutId){
                _createJackMoveMenuOption(actions, hideoutId);
            });

            // Enable Hover
            enableHideoutHoverForIDs(_.keys(jackMoveActions));
        });
    };

    /**
     * Carriage Move Sub Action Menu
     */
    var setSubActionMenuForJackCarriageMove = function(toHideoutId, intermediateHideoutIDs){
        enableHideoutHoverForIDs(intermediateHideoutIDs);


        var $intermediateHideouts = getHideoutsByIDs(intermediateHideoutIDs);
        $intermediateHideouts.addClass('carriage');

        $intermediateHideouts.on('click', function(){
            $intermediateHideouts.removeClass('carriage');
            disableAllHideoutHover();
            JsonRpc.request('board.moveJack', {
                method: JACK_MOVE_CARRIAGE,
                hideoutId: [
                    getHideoutId($(this)),
                    toHideoutId
                ]
            });
        });
    };

    /**
     * Police Officer Move Menu Option
     * @param string description
     * @param int junctionId
     */
    var _createPoliceOfficerMoveMenuOption = function(description, junctionId){
        getJunctionById(junctionId).on('click', function(e){
            openActionMenu(e, function () {
                var $menuOption = createMenuOption(junctionId, description);

                $menuOption.on('click', function () {
                    JsonRpc.request('board.movePoliceOfficer', {
                        junctionId: junctionId
                    });
                });

                return $menuOption;
            });

        });
    };

    /**
     * @param int junctionId current location
     * @param array junctionIDs list of all locations police officer can move to and different from current location
     */
    var setActionMenuForPoliceOfficerMove = function(junctionId, junctionIDs){
        Template.call(function() {
            var allJunctionsIDs = _.values(junctionIDs).slice();
            allJunctionsIDs.push(junctionId);

            // Enable Hover
            enableJunctionHoverForIDs(allJunctionsIDs);

            _.each(junctionIDs, function(junctionId){
                _createPoliceOfficerMoveMenuOption('move here', junctionId);
            });

            _createPoliceOfficerMoveMenuOption('stay where you are', junctionId);
        });
    };


    /**
     * Police Officer Action Hideout Menu Option
     * @param int hideoutId
     * @param array actions
     */
    var _createPoliceOfficerActionHideoutMenuOption = function(hideoutId, actions){
        getHideoutById(hideoutId).on('click', function(e) {
            openActionMenu(e, function () {
                var $menuOptions = $([]);

                _.each(actions, function(action){
                    var $menuOption = createMenuOption(hideoutId, action);
                    $menuOption.on('click', function () {
                        JsonRpc.request('board.policeOfficerAction', {
                            hideoutId: hideoutId,
                            action: action
                        });
                    });
                    $menuOptions = $menuOptions.add($menuOption);
                });

                return $menuOptions;
            });
        });
    };

    /**
     * Police Officer Action Junction Menu Option
     * @param int hideoutId
     * @param array actions
     */
    var _createPoliceOfficerActionJunctionMenuOption = function(junctionId, junctionAction){
        getJunctionById(junctionId).on('click', function(e) {
            openActionMenu(e, function () {
                var $menuOption = createMenuOption(junctionId, junctionAction);
                $menuOption.on('click', function () {
                    JsonRpc.request('board.policeOfficerAction', {
                        hideoutId: 0,
                        action: junctionAction
                    });
                });
                return $menuOption;
            });
        });
    };

    /**
     * @param array hideoutActions,  hideoutId => [actions]
     * @param int junctionId,
     * @param string junctionAction
     */
    var setActionMenuForPoliceOfficerActions = function(hideoutActions, junctionId, junctionAction){
        Template.call(function() {
            _.each(hideoutActions, function(actions, hideoutId){
                // Enable Hover
                enableHideoutHoverForIDs([hideoutId]);
                _createPoliceOfficerActionHideoutMenuOption(hideoutId, actions);
            });

            enableJunctionHoverForIDs([junctionId]);
            _createPoliceOfficerActionJunctionMenuOption(junctionId, junctionAction);
        });
    };

    /** Jack Hideout Display */
    var setJackHideoutDisplay = function(hideoutId){
      Template.call(function(){
          getRoleBox(ROLE_JACK).attr('data-hideout-id', hideoutId);
      });
    };

    /**
     * GAME ENDS
     */

    /**
     * Render Game Overview Popup
     * @param array data
     */
    var popupGameOverview = function(data){
        Template.render('popup-game-end-overview', data, $.popupManager.alert);
    };

    /**
     * INITIALIZATIONS
     */


    /**
     * Init Board Action HotSpot Hover
     */
    var initBoardActionHotSpotHover = function(){
        /** HIDEOUTS HOVER */

        getBySelector(SELECTOR_HIDEOUT).on('mouseenter', function(){
            $(this).addClass(CLASS_HIDEOUT_HOVER);
            if($(this).hasClass(CLASS_HIDEOUT_SELECTABLE)) {
                showHoverAnimation($(this), COLOR_HIDEOUT_HOVER);
            }
        });

        getBySelector(SELECTOR_HIDEOUT).on('mouseleave', function(){
            $(this).removeClass(CLASS_HIDEOUT_HOVER);
            hideHoverAnimation($(this));
        });

        /** JUNCTIONS HOVER */

        getBySelector(SELECTOR_JUNCTION).on('mouseenter', function(){
            $(this).addClass(CLASS_JUNCTION_HOVER);
            if($(this).hasClass(CLASS_JUNCTION_SELECTABLE)) {
                showHoverAnimation($(this), COLOR_JUNCTION_HOVER);
            }
        });

        getBySelector(SELECTOR_JUNCTION).on('mouseleave', function(){
            $(this).removeClass(CLASS_JUNCTION_HOVER);
            hideHoverAnimation($(this));
        });
    };

    /**
     * Init board Zoom (scale) and movement (dragging).
     */
    var initBoardScaleMove = function() {
        var $board = getBySelector(SELECTOR_BOARD);
        /** Board zoom-in, zoom-out with mouse wheel */
        var scaleManager = new ScaleManager($board);
        /** Board dragging */
        var movementManager = new MovementManager($board);

        adjustBoardSize();

        $(window).on('resize', function(){
            adjustBoardSize();
        });

        /** SCALE */
        $board.on('mousewheel', function (e) {
            if (e.originalEvent.deltaY > 0) {
                scaleManager.zoomIn(e, updateMapAnimate);
            } else {
                scaleManager.zoomOut(e, updateMapAnimate);
            }
        });

        /** MOVE */

        /** Handle move with mouse */
        $board.on('mousedown', function (e) {
            if(isSupportedEvent(e) == false){
                return;
            }

            /** mouse left button only */
            if (e.which !== 1) {
                return;
            }

            var lastMoveEvent;
            $(this).on('mousemove', function (e) {
                if (lastMoveEvent) {
                    movementManager.moveBy(e.pageX - lastMoveEvent.pageX, e.pageY - lastMoveEvent.pageY, updateMap);
                }
                lastMoveEvent = e;
            });

            $(this).one('mouseup', function(e){
                $(this).off('mousemove');
            });
        });


        /** Handle move with touch */
        $board.on('touchstart', function (e) {
            if(isSupportedEvent(e) == false){
                return;
            }

            var lastMoveEvent;
            $(this).on('touchmove', function (e) {
                e.pageX = e.originalEvent.touches[0].pageX;
                e.pageY = e.originalEvent.touches[0].pageY;
                if (lastMoveEvent) {
                    movementManager.moveBy(e.pageX - lastMoveEvent.pageX, e.pageY - lastMoveEvent.pageY, updateMap);
                }
                lastMoveEvent = e;
            });

            $(this).one('touchend', function(e){
                $(this).off('touchmove');
            });
        });

        /** Disable context menu */
        $board.on('contextmenu', function(e){
            e.preventDefault();
        });

        /** Board zoom-in with double click */
        $board.on('dblclick', function (e) {
            scaleManager.zoomIn(e, updateMapAnimate);
        });

        /** Baord zoom-out with mouse 2 button */
        $board.on('mousedown', function (e) {
            if (e.which !== 3) {
                return;
            }
            scaleManager.zoomOut(e, updateMapAnimate);
        });

        var hammer = new Hammer( $board[0] );
        hammer.get('pinch').set({ enable: true });
        hammer.on('pinch', function(e){
            if (e.scale > 0) {
                scaleManager.zoomIn(e, updateMapAnimate);
            } else {
                scaleManager.zoomOut(e, updateMapAnimate);
            }
        });
    };

    /**
     * Init map-to-board interactions
     */
    var initMap = function(){
        var $mapBox = getBySelector(SELECTOR_MAP_BOX);

        var $board = getBySelector(SELECTOR_BOARD);
        /** Board zoom-in, zoom-out with mouse wheel */
        var scaleManager = new ScaleManager($board);
        /** Board dragging */
        var movementManager = new MovementManager($board);

        //MOUSE MOVE
        $mapBox.on('mousedown', function(e){
            if(isSupportedEvent(e) == false){
                return;
            }

            /** mouse left button only */
            if(e.which !== 1){
                return;
            }

            var xFactor = scaleManager.getWidth() / $(this).width();
            var yFactor = scaleManager.getHeight() / $(this).height();

            $(this).on('mousemove', function(e){
                var _x = (e.pageX - $mapBox.offset().left) * xFactor;
                var _y = (e.pageY - $mapBox.offset().top) * yFactor;
                movementManager.centerTo(_x, _y, updateMap);
            });

            $(this).one('mouseup', function(){
                $mapBox.off('mousemove');
            });
        });

        /** Handle move with touch */
        $mapBox.on('touchstart', function (e) {
            if(isSupportedEvent(e) == false){
                return;
            }

            var xFactor = scaleManager.getWidth() / $(this).width();
            var yFactor = scaleManager.getHeight() / $(this).height();

            $(this).on('touchmove', function (e) {
                e.pageX = e.originalEvent.touches[0].pageX;
                e.pageY = e.originalEvent.touches[0].pageY;

                var _x = (e.pageX - $mapBox.offset().left) * xFactor;
                var _y = (e.pageY - $mapBox.offset().top) * yFactor;
                movementManager.centerTo(_x, _y, updateMap);
            });

            $(this).one('touchend', function(e){
                $(this).off('touchmove');
            });
        });

        // CLICK
        $mapBox.on('click', function(e){
            var xFactor = scaleManager.getWidth() / $mapBox.width();
            var yFactor = scaleManager.getHeight() / $mapBox.height();
            var _x = (e.pageX - $mapBox.offset().left) * xFactor;
            var _y = (e.pageY - $mapBox.offset().top) * yFactor;
            movementManager.centerTo(_x, _y, updateMap);
        });
    };


    /**
     * Init Role - Player display
     * @param map of config objects as role => {}
     * @param string (role) chiefOfInvestigation
     * @param string (role) activeRole
     */
    var initRoleSlots = function(players){

        /** Render player to socket */
        setRoles(players);

        /** Show player nickname */
        getBySelector(SELECTOR_ROLE_BOX).on('mouseenter', function() {
            var $nicknameBox = $(this).find(SELECTOR_ROLE_PLAYER_NICKNAME);
            $nicknameBox.finish();
            $nicknameBox.fadeIn(350);
        });

        /** Hide player nickname */
        getBySelector(SELECTOR_ROLE_BOX).on('mouseleave', function(){
            var $nicknameBox = $(this).find(SELECTOR_ROLE_PLAYER_NICKNAME);
            $nicknameBox.finish();
            $nicknameBox.fadeOut(350);
        });

        getBySelector(SELECTOR_ROLE_BOX).on('click', function(){
            var userId = Player.findIn($(this)).getUserId();
            if(userId === undefined){
                console.log('Player not yet assigned to this role');
                return;
            }
            popupPlayerDetails.init(userId);
        });

        /** Start Blinking Slot for Active Player */
        var interval = setInterval(function(){

            if($(PAGE).length === 0){
                clearInterval(interval);
                return false;
            }

            var $activeRoleBox = getBySelector(SELECTOR_ROLE_BOX)
                .filterAttr([[ATTR_ROLE_ACTIVE, TRUE]]);

            getBySelector(SELECTOR_ROLE_BOX).not($activeRoleBox)
                .removeClass(CLASS_ROLE_HIGHLIGHT_DIM);

            try{
                if(Player.findIn($activeRoleBox).isMe()){
                    $activeRoleBox.toggleClass(CLASS_ROLE_HIGHLIGHT_DIM);
                } else {
                    $activeRoleBox.removeClass(CLASS_ROLE_HIGHLIGHT_DIM);
                }
            } catch(e){}
        }, 800);
    };

    /**
     * Init Readiness Button
     */
    var initReadinessButton = function(){
        getBySelector('.policeOfficerReadiness').on('click', function(){
            hideReadinessConfirmBox();
            JsonRpc.request('board.confirmPoliceReadiness');
        });
    };

    /**
     * Init game messages log
     */
    var initGameLog = function(){
        // jQuery objects
        var $boardLogWidget = getBySelector(SELECTOR_LOG_WIDGET_BOX);
        var $boardLogHistoryBox = getBySelector(SELECTOR_HISTORY_LOG_BOX);
        var $boardLogCloseButton = getBySelector(SELECTOR_LOG_CLOSE_BUTTON);

        // perfect scrollbar
        $boardLogHistoryBox.perfectScrollbar();

        // bind window click
        var bindLogWidgetClick = function(){
            $boardLogWidget.on('click', function(e){
                e.stopPropagation();

                if($(this).hasClass('open')){
                    $boardLogHistoryBox.fadeOut(200);
                    $boardLogCloseButton.fadeOut(200);
                    $boardLogWidget.removeClass('open');

                    return true;
                }

                $(this).addClass('open');
                $boardLogHistoryBox.slideDown(120, function(){
                    $boardLogHistoryBox.perfectScrollbar('update');
                    $boardLogCloseButton.fadeIn(200);
                });
            });
        };

        // unbind window click;
        var unbindLogWidgetClick = function(){
            $boardLogWidget.off('click');
        };

        // INITIALIZATION
        bindLogWidgetClick();

        $boardLogHistoryBox.on('click', function(e){
            e.stopPropagation();
        });

        $boardLogCloseButton.on('click', function(e){
            e.stopPropagation();
        });

        getBySelector(SELECTOR_LOG_CLOSE_BUTTON).on('click', function(){
            $boardLogHistoryBox.fadeOut(200);
            $boardLogCloseButton.fadeOut(200);
            $boardLogWidget.removeClass('open');
        });

        $boardLogWidget.find('.ps-scrollbar-y').on('mousedown', function(e) {
            unbindLogWidgetClick();
            
            $(document).one('mouseup', function(){
                bindLogWidgetClick();
            });
        });
    };


    /**
     * Init content after loading.
     */
    var initContent = function(players){
            initBoardImage(function(){
            initBoardScaleMove();
            initMap();
            initRoleSlots(players);
            initGameLog();
            initBoardActionHotSpotHover();
            boardChat.init();
            initReadinessButton();
        });
    };

    /**
     * Load page content
     * @param templateData
     */
    var init = function(players){
        showLoadingSpinner();

        $.fadeNavManager.showUserInGameMenu();
        Template.render('page-board', {}, function($pageContent){
            Page.load($pageContent, function() {
                initContent(players);
                Page.onLoad(hideLoadingSpinner);
            });
        });
    };


    return {
        init: init,
        popupGameOverview: popupGameOverview,
        
        switchChiefOfInvestigationByRole: switchChiefOfInvestigationByRole,
        switchActiveRole: switchActiveRole,
        setActiveRoles: setActiveRoles,
        updateMap: updateMap,
        getJunctionById : getJunctionById,
        getJunctionsByIDs : getJunctionsByIDs,
        disableAllJunctionHover: disableAllJunctionHover,
        disableJunctionHoverForIDs: disableJunctionHoverForIDs,
        disableJunctionHover: disableJunctionHover,
        enableJunctionHoverForIDs: enableJunctionHoverForIDs,
        setRoles: setRoles,

        getHideoutById : getHideoutById,
        getHideoutsByIDs : getHideoutsByIDs,
        disableAllHideoutHover: disableAllHideoutHover,
        enableHideoutHoverForIDs: enableHideoutHoverForIDs,
        removeClueTokens: removeClueTokens,
        putMurderSceneToken: putMurderSceneToken,
        putClueToken: putClueToken,
        putWretchedToken: putWretchedToken,
        removeWretchedToken: removeWretchedToken,
        removeWretchedTokens: removeWretchedTokens,
        moveWomanToken: moveWomanToken,
        putWomenTokens: putWomenTokens,
        setActionMenuForKill: setActionMenuForKill,

        putPolicePawn: putPolicePawn,
        putPolicePawns: putPolicePawns,
        putPolicePawnPlaceHolders: putPolicePawnPlaceHolders,
        removePolicePawns: removePolicePawns,
        removePolicePawn: removePolicePawn,
        addLogMessage: addLogMessage,
        setDay: setDay,
        setAvailableMovesTo: setAvailableMovesTo,
        setCurrentMoveTo : setCurrentMoveTo,
        putMoveToken : putMoveToken,
        addJackTrackElement: addJackTrackElement,
        clearMoveTrack: clearMoveTrack,
        setAvailableSpecialMoves: setAvailableSpecialMoves,
        setActionMenuForHideoutSelection:setActionMenuForHideoutSelection,
        setActionMenuForWretchedTokens: setActionMenuForWretchedTokens,
        setActionMenuForWomanTokenMove: setActionMenuForWomanTokenMove,
        setJackHideoutDisplay: setJackHideoutDisplay,
        debug_openActionMenu: openActionMenu,
        disableHideoutHoverById: disableHideoutHoverById,
        setActionMenuForPolicePawnAllocation: setActionMenuForPolicePawnAllocation,
        openKillOrWaitPopup: openKillOrWaitPopup,
        openEnterHideoutPopup: openEnterHideoutPopup,
        setActionMenuForRevealPolicePawn: setActionMenuForRevealPolicePawn,
        setActionMenuForJackMove: setActionMenuForJackMove,
        setSubActionMenuForJackCarriageMove: setSubActionMenuForJackCarriageMove,
        setActionMenuForPoliceOfficerMove: setActionMenuForPoliceOfficerMove,
        setActionMenuForPoliceOfficerActions: setActionMenuForPoliceOfficerActions,
        putJackMarker: putJackMarker,
        removeJackMarker: removeJackMarker,
        putPolicePawnMarker: putPolicePawnMarker,
        removePolicePawnMarker: removePolicePawnMarker,
        policeActionAnimation: policeActionAnimation,
        setRolesReadinessMarkers: setRolesReadinessMarkers,
        removeRolesReadinessMarkers: removeRolesReadinessMarkers,
        showReadinessConfirmBox: showReadinessConfirmBox,
        hideReadinessConfirmBox: hideReadinessConfirmBox
    };
})(jQuery);