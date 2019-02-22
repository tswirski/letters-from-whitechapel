/**
 * jQuery.fadeNav.
 * @requires:  underscore.js, jquery.js
 */
(function($) {
    var $fadeNavHamburger;
    var $fadeNavMenu;
    var $body;
    var _bodyOverflow;
    var TRANSITION_DURATION = 400;

    /**
     * Initialize
     * @returns {undefined}
     */
    var init = _.once(function() {
        /** <body> handler */
        $body = $('body');

        /** Save <body> original 'overflow' value */
        _bodyOverflow = $body.css('overflow');

        /** Select menu DOM object */
        $fadeNavMenu = $('div#fadeNavMenu');
        $fadeNavMenu.on('click', function() {
            hideNav();
        });

        /** vertical-align: middle */
        var $fadeNavMenuOptions = $fadeNavMenu.find('ul').first();
        var $table = $('<div style="width: 100%; height: 100%; display: table; table-layout: fixed;">');
        var $cell = $('<div style="display: table-cell; vertical-align: middle;">');
        $cell.append($fadeNavMenuOptions.clone());
        $table.append($cell);
        $fadeNavMenuOptions.replaceWith($table);

        /** Prevent mobile safari scrolling the whole body when nav is open */
        if (navigator.userAgent.match(/(iPad|iPhone|iPod)/g)) {
            $fadeNavMenu.children().css({
                'height': '110%',
                'transform': 'translateY(-5%)'
            });
        }

        /** Append Menu-Hamburger-Icon to page */
        $fadeNavHamburger = $('<div id="fadeNavHamburger">');
        $fadeNavHamburger.append('<div>');
        $fadeNavHamburger.on('click', toggleNav);
        $body.prepend($fadeNavHamburger);
    });

    /**
     * Show Menu
     * @returns {undefined}
     */
    var showNav = function() {
        $fadeNavMenu.show();
        _.defer(function() {
            $().add($fadeNavMenu)
                    .add($fadeNavHamburger)
                    .addClass('active');
        });
        $body.css('overflow', 'hidden');
    };

    /**
     * Hide Menu
     * @returns {undefined}
     */
    var hideNav = function() {
        _.delay(function() {
            $fadeNavMenu.hide();
        }, TRANSITION_DURATION);
        $().add($fadeNavMenu)
                .add($fadeNavHamburger)
                .removeClass('active');
        $body.css('overflow', _bodyOverflow);
    };

    /**
     * Toggle menu visibility.
     * @returns {undefined}
     */
    var toggleNav = function() {
        if (isMenuVisible()) {
            hideNav();
        } else {
            showNav();
        }
    };

    /**
     * Returns TRUE if fadeNavMenu is visible. False otherwise.
     * @returns {bool}
     */
    var isMenuVisible = function() {
        return $fadeNavMenu.hasClass('active');
    };

    /**
     * Remove All Options from Menu
     * @returns {undefined}
     */
    var clear = function() {
        $fadeNavMenu.find('li').remove();
    };

    /**
     * Apply html as menu option.
     * @param {string} content
     * @returns {undefined}
     */
    var addOption = function(html, action) {
        $fadeNavMenu.find('ul').append($('<li data-action="' + action + '">').append(html));
    };

    $.fadeNav = {
        init: init,
        isVisible: isMenuVisible,
        toggle: toggleNav,
        show: showNav,
        hide: hideNav,
        add: function(html, action) {
            addOption(html, action);
            return this;
        },
        clear: clear
    };
}(jQuery));
