<div class="page" data-page="dashboard-game" data-admin="<%=admin%>">

    <!-- PLACEMENT -->
    <div id="dashboardTable">
        <div id="dashboardTableCell">
            <div id="dashboardBox">

                <!-- DICE -->
                <% if(randomJackHideout === true){ %> <span class="gameDice"></span> <% } %>

                <!-- NAVIGATION -->
                <div id="dashboardNavigationBox">
                    <?=Form::button('quitGame', 'Quit Game', [
                        'type' => 'button',
                        'tabindex' => 0,
                        'onkeydown' => 'return false;'
                    ]);?>
                    <?=Form::button('startGame', 'Start Game', [
                        'type' => 'button',
                        'tabindex' => 0,
                        'onkeydown' => 'return false;',
                        'class' => 'disabled'
                    ]);?>
                    <?=Form::button('adminMode', 'Administrate', [
                        'type' => 'button',
                        'tabindex' => 0,
                        'onkeydown' => 'return false;'
                    ]);?>
                </div>

                <!-- GAME ROLES -->
                <div class="gameRoles">
                    <!-- JACK -->
                    <div class="gameRole" data-role="<?= Game_Role::JACK ?>" data-taken="false" data-own="false">
                        <span class="gameRoleHeader"><?= Game_Role::NAME_JACK ?></span>
                        <div class="gameRoleSlot"></div>
                    </div>
                    <!-- FREDERICK ABBERLINE -->
                    <div class="gameRole" data-role="<?= Game_Role::RED_POLICE_OFFICER ?>" data-taken="false" data-own="false">
                        <span class="gameRoleHeader"><?= Game_Role::NAME_RED_POLICE_OFFICER ?></span>
                        <div class="gameRoleSlot"></div>
                    </div>
                    <!-- GEORGE LUSK -->
                    <div class="gameRole" data-role="<?= Game_Role::YELLOW_POLICE_OFFICER ?>" data-taken="false" data-own="false">
                        <span class="gameRoleHeader"><?= Game_Role::NAME_YELLOW_POLICE_OFFICER ?></span>
                        <div class="gameRoleSlot"></div>
                    </div>
                    <!-- SIR CHARLES WARREN -->
                    <div class="gameRole" data-role="<?= Game_Role::BLUE_POLICE_OFFICER ?>" data-taken="false" data-own="false">
                        <span class="gameRoleHeader"><?= Game_Role::NAME_BLUE_POLICE_OFFICER ?></span>
                        <div class="gameRoleSlot"></div>
                    </div>
                    <!-- EDMUND REID -->
                    <div class="gameRole" data-role="<?= Game_Role::GREEN_POLICE_OFFICER ?>" data-taken="false" data-own="false">
                        <span class="gameRoleHeader"><?= Game_Role::NAME_GREEN_POLICE_OFFICER ?></span>
                        <div class="gameRoleSlot"></div>
                    </div>
                    <!-- DONALD SWANSON -->
                    <div class="gameRole" data-role="<?= Game_Role::BROWN_POLICE_OFFICER ?>" data-taken="false" data-own="false">
                        <span class="gameRoleHeader"><?= Game_Role::NAME_BROWN_POLICE_OFFICER ?></span>
                        <div class="gameRoleSlot"></div>
                    </div>
                </div>

                <!-- PLAYERS -->
                <div id="gamePlayers"></div>

                <!-- CHAT -->
                <?=View::factory('template/dashboard.chat.mixin');?>

            </div>
        </div>
    </div>
</div>