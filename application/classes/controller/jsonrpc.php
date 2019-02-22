<?php

class Controller_JsonRpc extends Controller {

    public function before() {
        JsonRpc::server()
                ->register('avatar.update', [(new Rpc_Http_Avatar()), 'updateAvatar'])
                ->register('access.signin', [(new Rpc_Http_Access()), 'signin'])
                ->register('access.signup', [(new Rpc_Http_Access()), 'signup']);
    }

    public function action_index() {
        $json = file_get_contents("php://input");

        /** Hack używany przy transferze plików */
        if( ! $json){
            $json = Arr::get($_POST, 'json');
        }

        echo JsonRpc::dispatch($json);
    }
}
