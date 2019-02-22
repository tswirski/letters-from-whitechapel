<!DOCTYPE html>
<html>
    <head>
        <meta name="google-site-verification" content="d2Jp1lNtiEGHq33bhBAvQ5KelZhiaoclXWmdr22xU6c" />
        <!-- GENERAL JAVASCRIPT LIBRARIES -->
        <?= HTML::script('resources/js/lib/common/keycode.js'); ?>
        <?= HTML::script('resources/js/lib/common/es5-shim.min.js'); ?>
        <?= HTML::script('resources/js/lib/common/underscore/underscore-min.js'); ?>
        <?= HTML::script('resources/js/lib/common/fancywebsocket.js'); ?>
        <?= HTML::script('resources/js/lib/spinning-loader.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/jquery.1.11.1.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/jquery-ui-1.11.4.custom/jquery-ui.min.js'); ?>
        <?= HTML::script('resources/js/lib/hammer.min.js'); ?>
        <?= HTML::script('resources/js/lib/hammer.time.min.js'); ?>
        <?= HTML::script('resources/js/lib/device.js'); ?>
        <?= HTML::script('resources/js/lib/howler.core.js'); ?>
        <?= HTML::script('resources/js/lib/sounds.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/jsonrpc/jquery.jsonrpc.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/jsonrpc/jquery.jsonrpc.http.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/qtip/jquery.qtip.min.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/jquery.common.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/jquery.active-tab.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/jquery.popup.manager.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/fadenav/jquery.fadenav.js'); ?>
        <?= HTML::script('resources/js/common.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/jquery.template.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/jquery.transit.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/jquery.interval.js'); ?>

        <?= HTML::script('resources/js/lib/jquery/jquery.fadenav.manager.js'); ?>
        <?= HTML::script('resources/js/lib/common/fancywebsocket.server.js'); ?>
        <?= HTML::script('resources/js/page.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/perfect-scrollbar/js/min/perfect-scrollbar.jquery.min.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/jquery.scrollTo.min.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/jquery.scale-manager.js'); ?>
        <?= HTML::script('resources/js/lib/jquery/jquery.movement-manager.js'); ?>


        <!-- INTERACTION -->
        <?= HTML::script('resources/js/mixins/dashboard-player.js'); ?>
        <?= HTML::script('resources/js/mixins/dashboard-chat.js'); ?>
        <?= HTML::script('resources/js/mixins/board-chat.js'); ?>
        <?= HTML::script('resources/js/mixins/avatar.js'); ?>
        <?= HTML::script('resources/js/mixins/player.js'); ?>

        <?= HTML::script('resources/js/page/dashboard-game.js'); ?>
        <?= HTML::script('resources/js/page/dashboard-general.js'); ?>
        <?= HTML::script('resources/js/page/welcome.js'); ?>
        <?= HTML::script('resources/js/page/board.js'); ?>

        <?= HTML::script('resources/js/popup/account.js'); ?>
        <?= HTML::script('resources/js/popup/new-game.js'); ?>
        <?= HTML::script('resources/js/popup/game-password.js'); ?>
        <?= HTML::script('resources/js/popup/player-details.js'); ?>
        <?= HTML::script('resources/js/popup/player-kick-ban.js'); ?>

        <!-- JSON RPC ROUTING -->
        <?= HTML::script('resources/js/jsonrpc-routes.js'); ?>

        <!-- STYLES -->
        <?= HTML::style('resources/css/production.css'); ?>
        <?= HTML::style('resources/fonts/font-awesome-4.5.0/css/font-awesome.min.css'); ?>
        <?= HTML::style('resources/js/lib/jquery/qtip/jquery.qtip.min.css'); ?>
        <?= HTML::style('resources/js/lib/jquery/qtip/jquery.qtip.custom.css'); ?>
        <?= HTML::style('resources/js/lib/jquery/perfect-scrollbar/css/perfect-scrollbar.min.css'); ?>
        <meta name="HandheldFriendly" content="true" />

        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

        <!-- PREVENT PREMATURE TRANSITIONS -->
        <style>
            .noTransition{
                transition: none !important;
            }
        </style>

        <script>
            $(window).load(function () {
                /** PREVENT PREMATURE TRANSITIONS */
                $('body').removeClass('preload');

                /** INIT MENU */
                $.fadeNavManager.init();
                $.fadeNavManager.showGuestMenu();
            });

            /** INIT LIBRARIES */
            JsonHttpRpc.setUrl('<?= Route::link('Jsonrpc'); ?>');
            Template.setUrl('<?= Route::link('Template', 'get'); ?>');
            Avatar.setDefaultAvatarPath('<?=DAO::factory('User', null)->getDefaultAvatarPath();?>');
        </script>

    </head>

    <body class="noTransition" data-count="<?=$count?>">
        <!-- The menu container -->
        <div id="fadeNavMenu">
            <ul>
                <!-- OPTIONS GOES HERE -->
            </ul>
        </div>

        <div class="popoverNotificationsBox"></div>

        <div id="content">
            <?= $content; ?>
        </div>
    </body>
</html>