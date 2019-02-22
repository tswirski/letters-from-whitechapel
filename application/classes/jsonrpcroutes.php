<?php

class JsonRpcRoutes extends Abstract_Singleton {
    // Pusta klasa utworzona tylko po to aby łatwo było dołączyć ten plik używająć auto-loadera Kohany w miejsce require_once();
}
//
///** ACCESS */
JsonRpc::server()->register('access.logout', ['Rpc_Access', 'logout']);

/** ACCOUNT */
JsonRpc::server()->register('account.popup', ['Rpc_Account', 'popup']);
JsonRpc::server()->register('account.updateAccountData', ['Rpc_Account', 'updateAccountData']);
JsonRpc::server()->register('account.validateAccountData', ['Rpc_Account', 'validateAccountData']);
JsonRpc::server()->register('account.announceAvatarUpdate', ['Rpc_Account', 'announceAvatarUpdate']);

/** PLAYER */
JsonRpc::server()->register('player.details', ['Rpc_Player', 'details']);
JsonRpc::server()->register('player.linkFriends', ['Rpc_Player', 'linkFriends']);
JsonRpc::server()->register('player.unlinkFriends', ['Rpc_Player', 'unlinkFriends']);
JsonRpc::server()->register('player.isFriendOfMine', ['Rpc_Player', 'isFriendOfMine']);

/** CHAT */
JsonRpc::server()->register('chat.message', ['Rpc_Chat', 'message']);

/** SERVICE DASHBOARD GENERAL */
JsonRpc::server()->register('service-dashboard-general.join', ['Service_Dashboard_General', 'join']);

/** SERVICE DASHBOARD GAME */
JsonRpc::server()->register('dashboard-game.join', ['Service_Dashboard_Game', 'joinGame']);
JsonRpc::server()->register('dashboard-game.joinProtected', ['Service_Dashboard_Game', 'joinPasswordProtectedGame']);
JsonRpc::server()->register('dashboard-game.quit', ['Service_Dashboard_Game', 'quitGame']);
JsonRpc::server()->register('dashboard-game.create', ['Service_Dashboard_Game', 'createNewGame']);
JsonRpc::server()->register('dashboard-game.claimToggleSlot', ['Service_Dashboard_Game', 'claimToggleSlot']);
JsonRpc::server()->register('dashboard-game.openSlot', ['Service_Dashboard_Game', 'openSlot']);
JsonRpc::server()->register('dashboard-game.banPlayer', ['Service_Dashboard_Game', 'banUser']);
JsonRpc::server()->register('dashboard-game.kickPlayer', ['Service_Dashboard_Game', 'kickUser']);
JsonRpc::server()->register('dashboard-game.startGame', ['Service_Dashboard_Game', 'startGame']);


/** SERVICE BOARD */
JsonRpc::server()->register('board.quit', ['Service_Board', 'quitBoard']);
JsonRpc::server()->register('board.confirmPoliceReadiness', ['Service_Board', 'confirmPoliceReadiness']);
JsonRpc::server()->register('board.setJackHideout', ['Service_Board', 'setJackHideout']);
JsonRpc::server()->register('board.setWretchedToken', ['Service_Board', 'setWretchedToken']);
JsonRpc::server()->register('board.getAvailableWretchedTokens', ['Service_Board', 'getAvailableWretchedTokens']);
JsonRpc::server()->register('board.getWretchedPutTokenMenuData', ['Service_Board', 'getWretchedPutTokenMenuData']);
JsonRpc::server()->register('board.getPolicePutPawnMenuData', ['Service_Board', 'getPolicePutPawnMenuData']);
JsonRpc::server()->register('board.setPolicePawn', ['Service_Board', 'setPolicePawn']);
JsonRpc::server()->register('board.killOrWait', ['Service_Board', 'killOrWait']);
JsonRpc::server()->register('board.kill', ['Service_Board', 'kill']);
JsonRpc::server()->register('board.getKillWretchedMenuData', ['Service_Board', 'getKillWretchedMenuData']);
JsonRpc::server()->register('board.moveWoman', ['Service_Board', 'moveWoman']);
JsonRpc::server()->register('board.revealPolicePawn', ['Service_Board', 'revealPolicePawn']);
JsonRpc::server()->register('board.getWomanMovesMenuData', ['Service_Board', 'getWomanMovesMenuData']);
JsonRpc::server()->register('board.moveJack', ['Service_Board', 'moveJack']);
JsonRpc::server()->register('board.enterHideout', ['Service_Board', 'enterHideout']);
JsonRpc::server()->register('board.movePoliceOfficer', ['Service_Board', 'movePoliceOfficer']);
JsonRpc::server()->register('board.policeOfficerAction', ['Service_Board', 'policeOfficerAction']);




/** DEBUG */
JsonRpc::server()->register('board.debugWoman', ['Service_Board', 'debugWoman']);
JsonRpc::server()->register('board.debugPolice', ['Service_Board', 'debugPolice']);
JsonRpc::server()->register('board.debugJack', ['Service_Board', 'debugJack']);
JsonRpc::server()->register('board.debugStorage', ['Service_Board', 'debugStorage']);
JsonRpc::server()->register('board.debugSetDay', ['Service_Board', 'debugSetDay']);
JsonRpc::server()->register('board.debugSet', ['Service_Board', 'debugSet']);
JsonRpc::server()->register('board.debugSkipPoliceTurn', ['Service_Board', 'debugSkipPoliceTurn']);



JsonRpc::server()->register('debug.dashboard-game', ['Service_Dashboard_Game', 'debug']);
JsonRpc::server()->register('debug.board', ['Service_Board', 'debug']);
JsonRpc::server()->register('debug.dashboard-general', ['Service_Dashboard_General', 'getSocketIDs']);
JsonRpc::server()->register('debug.getSockets', function(){
    return Server::getRegisteredSocketIDs();
});

JsonRpc::server()->register('debug.getUsers', function(){
   return Server::getRegisteredUserIDs();
});
