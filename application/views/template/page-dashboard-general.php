<div class="page" data-page="dashboard-general" data-show-games="false">

    <!-- PLACEMENT -->
    <div id="dashboardTable">
        <div id="dashboardTableCell">

            <div id="dashboardBox">
                <!-- NAWIGACJA -->
                <div id="dashboardNavigationBox">
                    <?=Form::button('recentGames', 'Recent Games', [
                        'type' => 'button',
                        'tabindex' => 0,
                        'onkeydown' => 'return false;'
                    ]);?>
                    <?=Form::button('createNewGame', 'Create New Game', [
                        'type' => 'button',
                        'tabindex' => 0,
                        'onkeydown' => 'return false;'
                    ]);?>
                </div>

                <!-- CHAT -->
                <?=View::factory('template/dashboard.chat.mixin');?>

                <!-- PLAYERS -->
                <div id="playersList"></div>

                <!-- GAMES -->
                <div id="gamesBox">

                    <div class="gameListHeader">
                        <span>Host</span>
                        <span>Game</span>
                        <span>Players</span>
                    </div>

                    <div id="gameList"></div>
                </div>
                <!-- /DASHBOARDBOX -->
            </div>
            <!-- /PLACEMENT -->
        </div>
    </div>
</div>
