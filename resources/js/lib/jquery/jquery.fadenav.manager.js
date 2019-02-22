(function ($) {

    var OPTION_READ_MANUAL = ['read-manual', "Read Player's Handbook"];
    var OPTION_CHANGE_ACCOUNT_SETTINGS = ['change-settings', "Change Account Settings"];
    var OPTION_REPORT_A_BUG = ['report-a-bug', "Report a Bug"];
    var OPTION_FIND_US_ON_FACEBOOK = ['find-us-on-facebook', "Find Us on Facebook"];
    var OPTION_LOGOUT = ['logout', "Log Out"];
    var OPTION_QUIT_GAME = ['quit-game', "Quit the Game"];

    var initFadeMenu = function () {
        $(document).ready(function () {
            $.fadeNav.init();
        });
    };

    var initOptionHandlers = function () {
        $('#fadeNavMenu').on('click', 'li', function () {
            var action = $(this).data('action');
            _.delay(function () {
                switch (action) {
                    case OPTION_READ_MANUAL[0]:

                        var $object = $('<object>');
                        $object.css({
                            width: '790px',
                            height: '640px'
                        });
                        $object.attr('type', "application/pdf");
                        $object.attr('data', 'https://images-cdn.fantasyflightgames.com/ffg_content/letters-from-whitechapel/LFH_rules_letter_EN%20low%20res.pdf');
                        $object.text("PDF CAN NOT BE LOADED");
                        $.popupManager.alert($object);

                        break;
                    case OPTION_CHANGE_ACCOUNT_SETTINGS[0]:
                        JsonRpc.notify('account.popup');
                        break;
                    case OPTION_REPORT_A_BUG[0]:
                        break;
                    case OPTION_FIND_US_ON_FACEBOOK[0]:
                        break;
                    case OPTION_LOGOUT[0]:
                        JsonRpc.request('access.logout');
                        break;
                    case OPTION_QUIT_GAME[0]:
                        JsonRpc.request('board.quit');
                        break;
                }
            }, 410);
        });
    };

    var menuSetOptions = function (options) {
        $.fadeNav.clear();
        _.each(options, function (option) {
            $.fadeNav.add(option[1], option[0]);
        });
    };

    var showGuestMenu = function () {
        menuSetOptions([
            OPTION_READ_MANUAL
                    //           OPTION_REPORT_A_BUG,
                    //           OPTION_FIND_US_ON_FACEBOOK
        ]);
    };

    var showUserMenu = function () {
        menuSetOptions([
            OPTION_READ_MANUAL,
            OPTION_CHANGE_ACCOUNT_SETTINGS,
            //         OPTION_REPORT_A_BUG,
            //         OPTION_FIND_US_ON_FACEBOOK,
            OPTION_LOGOUT
        ]);
    };

    var showUserInGameMenu = function () {
        menuSetOptions([
            OPTION_QUIT_GAME,
            OPTION_READ_MANUAL,
            //       OPTION_CHANGE_ACCOUNT_SETTINGS,
            //       OPTION_REPORT_A_BUG,
            //       OPTION_FIND_US_ON_FACEBOOK,
            OPTION_LOGOUT
        ]);
    };

    /**
     * Inicjalizacja modulu.
     * @returns {undefined}
     */
    var init = function () {
        initFadeMenu();
        initOptionHandlers();
    };

    $.fadeNavManager = {
        init: init,
        showGuestMenu: showGuestMenu,
        showUserMenu: showUserMenu,
        showUserInGameMenu: showUserInGameMenu,
    };
})(jQuery);

