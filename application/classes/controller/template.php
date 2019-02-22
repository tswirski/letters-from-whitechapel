<?php

class Controller_Template extends Controller{
    protected $templates = [
        'page-welcome' => 'page-welcome',
        'page-dashboard-general' => 'page-dashboard-general',
        'page-dashboard-game' => 'page-dashboard-game',
        'page-board' => 'page-board',

        'popup-account' => 'popup-account',
        'popup-player-details' => 'popup-player-details',
        'popup-player-kick-ban' => 'popup-player-kick-ban',
        'popup-new-game' => 'popup-new-game',
        'popup-game-password' => 'popup-game-password',
        'popup-game-end-overview' => 'popup-game-end-overview',

        'dashboard.game' => 'dashboard.game',
        'chat.message' => 'chat.message',
        'chat.message.self' => 'chat.message.self',
        'chat.message.notification' => 'chat.message.notification',
        'player' => 'player',
        'dashboard.player.details' => 'dashboard.player.details',

        'board.log.entry' => 'board.log.entry',
    ];

    public function action_get(){
        $templateName = Arr::get($_GET, 'template');
        if(empty($templateName) || !isset($this->templates[$templateName])){
            return;
        }
        echo View::factory('template/'.$this->templates[$templateName]);
    }
}