/** Logowanie, nawi�zywanie po��czenia WS, roz��czanie */
JsonRpc.register('server.login', [Server, 'login']);
JsonRpc.register('server.canDisconnect', [Server, 'canDisconnect'])

/** STRONA STARTOWA */
JsonRpc.register('page-welcome.init', [pageWelcome, 'init'])
JsonRpc.register('page-welcome.reconnectDialog', [pageWelcome, 'reconnectDialog'])
JsonRpc.register('page-welcome.showErrors', [pageWelcome, 'showErrors'])
JsonRpc.register('page-welcome.clickSwitchToSigninForm', [pageWelcome, 'clickSwitchToSigninForm']);

/** �adowanie stron */
JsonRpc.register('page.load', [Page, 'load']);
JsonRpc.register('page.loadTemplate', [Page, 'loadTemplate']);

/** Obs�uga popup i alert, okienka modalne */
JsonRpc.register('popup.alert', $.popupManager.alert);
JsonRpc.register('popup.dialog', $.popupManager.dialog);
JsonRpc.register('popup.note', $.popupManager.note);

/** Obs�uga menu */
JsonRpc.register('menu.guest', $.fadeNavManager.showGuestMenu);

/** Popup konfiguracji konta */
JsonRpc.register('popup-account.init', [popupAccount, 'init']);
JsonRpc.register('popup-account.showErrors', [popupAccount, 'showErrors']);
JsonRpc.register('popup-account.sendAvatarDataAjax', [popupAccount, 'sendAvatarDataAjax']);
JsonRpc.register('popup-account.sendAccountData', [popupAccount, 'sendAccountData']);

JsonRpc.register('avatar.updateUrlForUserById', [Avatar, 'updateUrlForUserById']);
JsonRpc.register('avatar.announceAvatarUpdate', [Avatar, 'announceAvatarUpdate']);

/** Popup nowej gry */
JsonRpc.register('popup-new-game.init', [popupNewGame, 'init']);

/** Popup hasła gry */
JsonRpc.register('popup-game-password.init', [popupGamePassword, 'init']);

/** Dashboard - strona - startowa */
JsonRpc.register('page-dashboard-general.init', [pageDashboardGeneral, 'init']);
JsonRpc.register('page-dashboard-general.addPlayer', [pageDashboardGeneral, 'addPlayer']);
JsonRpc.register('page-dashboard-general.removePlayer', [pageDashboardGeneral, 'removePlayer']);
JsonRpc.register('page-dashboard-general.addGame', [pageDashboardGeneral, 'addGame']);
JsonRpc.register('page-dashboard-general.updateGame', [pageDashboardGeneral, 'updateGame']);
JsonRpc.register('page-dashboard-general.removeGame', [pageDashboardGeneral, 'removeGame']);
JsonRpc.register('page-dashboard-general.alertUserBanned', [pageDashboardGeneral, 'alertUserBanned']);

/** Dashboard - chat */
JsonRpc.register('general.chat.message', [dashboardChat, 'addChatMessage']);
JsonRpc.register('general.chat.message.self', [dashboardChat, 'addMyChatMessage']);
JsonRpc.register('general.chat.notification', [dashboardChat, 'addChatNotification']);

/** Dashboard - strona - gra */
JsonRpc.register('page-dashboard-game.init', [pageDashboardGame, 'init']);
JsonRpc.register('page-dashboard-game.addPlayer', [pageDashboardGame, 'addPlayer']);
JsonRpc.register('page-dashboard-game.removePlayer', [pageDashboardGame, 'removePlayer']);

JsonRpc.register('page-dashboard-game.addPlayerToRoleSlot', [pageDashboardGame, 'addPlayerToRoleSlot']);
JsonRpc.register('page-dashboard-game.removePlayerFromRoleSlot', [pageDashboardGame, 'removePlayerFromRoleSlot']);
JsonRpc.register('page-dashboard-game.animateRoleError', [pageDashboardGame, 'animateRoleError']);

/** Game - chat */
JsonRpc.register('game.chat.message', [dashboardChat, 'addChatMessage']);
JsonRpc.register('game.chat.message.self', [dashboardChat, 'addMyChatMessage']);
JsonRpc.register('game.chat.notification', [dashboardChat, 'addChatNotification']);


/** BOARD */
JsonRpc.register('page-board.init', [pageBoard, 'init']);
JsonRpc.register('page-board.popupGameOverview', [pageBoard, 'popupGameOverview']);

JsonRpc.register('page-board.switchChiefOfInvestigationByRole', [pageBoard, 'switchChiefOfInvestigationByRole']);
JsonRpc.register('page-board.switchActiveRole', [pageBoard, 'switchActiveRole']);
JsonRpc.register('page-board.setActiveRoles', [pageBoard, 'setActiveRoles']);
JsonRpc.register('page-board.setRoles', [pageBoard, 'setRoles']);
JsonRpc.register('page-board.updateMap', [pageBoard, 'updateMap']);
JsonRpc.register('page-board.getJunctionById', [pageBoard, 'getJunctionById']);
JsonRpc.register('page-board.getJunctionsByIDs', [pageBoard, 'getJunctionsByIDs']);
JsonRpc.register('page-board.disableAllJunctionHover', [pageBoard, 'disableAllJunctionHover']);
JsonRpc.register('page-board.disableJunctionHover', [pageBoard, 'disableJunctionHover']);
JsonRpc.register('page-board.disableJunctionHoverForIDs', [pageBoard, 'disableJunctionHoverForIDs']);
JsonRpc.register('page-board.enableJunctionHoverForIDs', [pageBoard, 'enableJunctionHoverForIDs']);
JsonRpc.register('page-board.getHideoutById', [pageBoard, 'getHideoutById']);
JsonRpc.register('page-board.getHideoutsByIDs', [pageBoard, 'getHideoutsByIDs']);
JsonRpc.register('page-board.disableAllHideoutHover', [pageBoard, 'disableAllHideoutHover']);
JsonRpc.register('page-board.enableHideoutHoverForIDs', [pageBoard, 'enableHideoutHoverForIDs']);
JsonRpc.register('page-board.removeHideoutClues', [pageBoard, 'removeHideoutClues']);
JsonRpc.register('page-board.putMurderToken', [pageBoard, 'putMurderToken']);
JsonRpc.register('page-board.putClueToken', [pageBoard, 'putClueToken']);
JsonRpc.register('page-board.removeClueTokens', [pageBoard, 'removeClueTokens']);
JsonRpc.register('page-board.addLogMessage', [pageBoard, 'addLogMessage']);
JsonRpc.register('page-board.setDay', [pageBoard, 'setDay']);
JsonRpc.register('page-board.setAvailableMovesTo', [pageBoard, 'setAvailableMovesTo']);
JsonRpc.register('page-board.setCurrentMoveTo', [pageBoard, 'setCurrentMoveTo']);
JsonRpc.register('page-board.putMoveToken', [pageBoard, 'putMoveToken']);
JsonRpc.register('page-board.clearMoveTrack', [pageBoard, 'clearMoveTrack']);
JsonRpc.register('page-board.setAvailableSpecialMoves', [pageBoard, 'setAvailableSpecialMoves']);
JsonRpc.register('page-board.setActionMenuForHideoutSelection', [pageBoard, 'setActionMenuForHideoutSelection']);
JsonRpc.register('page-board.setJackHideoutDisplay', [pageBoard, 'setJackHideoutDisplay']);
JsonRpc.register('page-board.setActionMenuForWretchedTokens', [pageBoard, 'setActionMenuForWretchedTokens']);
JsonRpc.register('page-board.disableHideoutHoverById', [pageBoard, 'disableHideoutHoverById']);
JsonRpc.register('page-board.setActionMenuForPolicePawnAllocation', [pageBoard, 'setActionMenuForPolicePawnAllocation']);
JsonRpc.register('page-board.putPolicePawn', [pageBoard, 'putPolicePawn']);
JsonRpc.register('page-board.putPolicePawns', [pageBoard, 'putPolicePawns']);
JsonRpc.register('page-board.putPolicePawnPlaceHolders', [pageBoard, 'putPolicePawnPlaceHolders']);
JsonRpc.register('page-board.removePolicePawns', [pageBoard, 'removePolicePawns']);
JsonRpc.register('page-board.removePolicePawn', [pageBoard, 'removePolicePawn']);
JsonRpc.register('page-board.openKillOrWaitPopup', [pageBoard, 'openKillOrWaitPopup']);
JsonRpc.register('page-board.openEnterHideoutPopup', [pageBoard, 'openEnterHideoutPopup']);
JsonRpc.register('page-board.setActionMenuForWomanTokenMove', [pageBoard, 'setActionMenuForWomanTokenMove']);
JsonRpc.register('page-board.putWretchedToken', [pageBoard, 'putWretchedToken']);
JsonRpc.register('page-board.removeWretchedTokens', [pageBoard, 'removeWretchedTokens']);
JsonRpc.register('page-board.removeWretchedToken', [pageBoard, 'removeWretchedToken']);
JsonRpc.register('page-board.putWomenTokens', [pageBoard, 'putWomenTokens']);
JsonRpc.register('page-board.moveWomanToken', [pageBoard, 'moveWomanToken']);
JsonRpc.register('page-board.setActionMenuForRevealPolicePawn', [pageBoard, 'setActionMenuForRevealPolicePawn']);
JsonRpc.register('page-board.setActionMenuForKill', [pageBoard, 'setActionMenuForKill']);
JsonRpc.register('page-board.putMurderSceneToken', [pageBoard, 'putMurderSceneToken']);
JsonRpc.register('page-board.setActionMenuForJackMove', [pageBoard, 'setActionMenuForJackMove']);
JsonRpc.register('page-board.setSubActionMenuForJackCarriageMove', [pageBoard, 'setSubActionMenuForJackCarriageMove']);
JsonRpc.register('page-board.addJackTrackElement', [pageBoard, 'addJackTrackElement']);
JsonRpc.register('page-board.setActionMenuForPoliceOfficerMove', [pageBoard, 'setActionMenuForPoliceOfficerMove']);
JsonRpc.register('page-board.setActionMenuForPoliceOfficerActions', [pageBoard, 'setActionMenuForPoliceOfficerActions']);
JsonRpc.register('page-board.putJackMarker', [pageBoard, 'putJackMarker']);
JsonRpc.register('page-board.removeJackMarker', [pageBoard, 'removeJackMarker']);
JsonRpc.register('page-board.putPolicePawnMarker', [pageBoard, 'putPolicePawnMarker']);
JsonRpc.register('page-board.removePolicePawnMarker', [pageBoard, 'removePolicePawnMarker']);
JsonRpc.register('page-board.policeActionAnimation', [pageBoard, 'policeActionAnimation']);
JsonRpc.register('page-board.setRolesReadinessMarkers', [pageBoard, 'setRolesReadinessMarkers']);
JsonRpc.register('page-board.removeRolesReadinessMarkers', [pageBoard, 'removeRolesReadinessMarkers']);
JsonRpc.register('page-board.showReadinessConfirmBox', [pageBoard, 'showReadinessConfirmBox']);
JsonRpc.register('page-board.hideReadinessConfirmBox', [pageBoard, 'hideReadinessConfirmBox']);

/** Board - chat */
JsonRpc.register('board.chat.message', [boardChat, 'addChatMessage']);
JsonRpc.register('board.chat.message.self', [boardChat, 'addMyChatMessage']);
JsonRpc.register('board.chat.notification', [boardChat, 'addChatNotification']);


