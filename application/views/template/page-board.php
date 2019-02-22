<div class="page" data-page="board">

    <!-- CHAT -->
    <div class="boardGameChat">
        <div class="boardGameChatMessages"></div>
        <i class="boardGameChatIconButton"></i>
        <div class="boardGameChatInputBox">
            <textarea placeholder="<?=__('What would you like to say')?>?" id="boardGameChatInput"></textarea>
        </div>
    </div>

    <!-- DAY COUNTER -->
    <div class="dayCounterBox">
        <div class="whitePawn" data-visible="true" data-pawn="1"></div>
        <div class="whitePawn" data-visible="false" data-pawn="2"></div>
        <div class="bloodPuddle"></div>
        <div class="dayCounterPrefix">day</div>
        <div class="dayCounterDisplay"><span></span></div>

        <div class="availableSpecialMoves">
            <div class="carriageCounter"></div>
            <div class="alleyCounter"></div>
        </div>
    </div>


    <!-- GAME LOG -->
    <div class="boardLog">
        <i class="boardLogIcon"></i>

        <div class="boardLogHistoryPlacement">
            <div class="boardLogHistoryHook">
                <div class="boardLogHistoryClose">close</div>
                <div class="boardLogHistory">
                    <!-- log archive -->
                </div>
            </div>
        </div>

        <div class="boardLogRecent">
            <!-- current log -->
        </div>
    </div>


    <!-- GUI - JACK MOVES TRACK -->
    <div class="jackMovesBox">
        <div class="jackMovesOrdination">
            <?php for($i=1; $i<=15; $i++):?>
                <span data-hidden="false" data-selected="false" data-enabled="false" data-id="<?= $i ?>"></span>
            <?php endfor; ?>


            <?php for($i=16; $i<=20; $i++):?>
                <span data-hidden="false" data-selected="false" data-enabled="false" data-id="<?= $i ?>"></span>
            <?php endfor; ?>
        </div>
        <div class="jackMoveTokens"></div>
        <div class="jackTrackDisplay"></div>
    </div>


    <!-- GUI - BOARD ROLES -->
    <div class="boardRoles">

        <?php foreach(Game_Role::getAllRolesArray() as $role): ?>
        <div class="boardRole" data-role="<?=$role?>">
            <span class="readinessMarker"><?= __('READY') ?></span>
            <div class="portrait"></div>
            <div class="playerSlot"></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="policeOfficerReadiness">
        <span class="message"><?=__('Ready for a next day') ?> ?</span>
        <span class="button"><?=__('yes') ?></span>
    </div>

    <!-- MAP -->
    <div class="mapBox">
        <span class="mapCloseButton"></span>
        <div class="mapMarkerBox">
            <div class="mapMarker"></div>
        </div>
        <img class="mapImage" src="./resources/images/jack_map.gif"/>
    </div>

    <!-- BOARD ACTION MENU -->
    <div class="boardActionMenuPlacement">
        <div class="boardActionMenuClickPoint">
            <div class="boardActionMenuBox"></div>
        </div>
    </div>


    <!-- JACK KILL OR WAIT POPUP -->
    <div class="jackDecideToKillPopup">
        <span class="button" data-action="kill">Kill</span>
        <span class="button" data-action="wait">Wait</span>
    </div>

    <!-- JACK ENTER HIDEOUT POPUP -->
    <div class="jackDecideToEnterHideoutPopup">
        <span class="button" data-action="enter">Enter Hideout</span>
        <span class="button" data-action="pass">Not Now</span>
    </div>

    <!-- BOARD -->
    <div class="boardTable">
        <div class="boardTableCell">
            <div class="board" data-image="./resources/images/jack_board.jpg">

                <?php foreach(DAO::find(Model_Hideout::class)->getAll() as $hideout):
                    $left = $hideout->get(Model_Hideout::COLUMN_POSITION_X);
                    $top = $hideout->get(Model_Hideout::COLUMN_POSITION_Y);
                    $id = $hideout->get(Model_Hideout::COLUMN_ID);
                    $isVictimStartPoint = $hideout->get(Model_Hideout::COLUMN_IS_WRETCHED_STARTPOINT);
                    ?>
                    <div class="hideout"
                        data-id="<?= $id ?>"
                        style="left: <?= $left ?>%; top: <?= $top ?>%;"
                        data-wretched-startpoint="<?= $isVictimStartPoint ? 'true' : 'false' ?>">
                        <div class="token"></div>
                        <div class="animation"></div>
                    </div>
                <?php endforeach; ?>

                <?php foreach(DAO::find(Model_Junction::class)->getAll() as $junction):
                    $left = $junction->get(Model_Junction::COLUMN_POSITION_X);
                    $top = $junction->get(Model_Junction::COLUMN_POSITION_Y);
                    $id = $junction->get(Model_Junction::COLUMN_ID);
                    $isPoliceOfficerStartPoint = $junction->get(Model_Junction::COLUMN_IS_POLICEMAN_STARTPOINT);
                    ?>
                <div class="junction"
                    data-id="<?= $id ?>"
                    style="left: <?= $left ?>%; top: <?= $top ?>%;"
                    data-police-startpoint="<?=$isPoliceOfficerStartPoint ? 'true' : 'false';?>">
                    <div class="token"></div>
                    <div class="animation"></div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
