<?php defined('SYSPATH') or die('No direct script access.');

class Rpc_Account {

    const FIELD_EMAIL = 'email';
    const FIELD_PASSWORD = 'password';

    public static function announceAvatarUpdate(){
        Server::getUser()->reload();

        JsonRpc::client()->notify(Server::getRegisteredSocketIDs(), 'avatar.updateUrlForUserById', [
            'userId' => Server::getUserId(),
            'url' => Server::getUser()->getAvatarPath()
        ]);
    }

    public static function popup() {
        JsonRpc::client()->notify(Server::getSocketId(), 'popup-account.init', [
            'userAvatarImg' => Server::getUser()->getAvatarPath()
        ]);
    }

    public static function validateAccountData($data){
        $validation = Validation::factory($data);
        if(Arr::get($data, self::FIELD_EMAIL) !== null) {
            $validation
                ->rule(self::FIELD_EMAIL, 'not_empty')
                ->rule(self::FIELD_EMAIL, 'email')
                ->rule(self::FIELD_EMAIL, 'email_domain')
                ->rule(self::FIELD_EMAIL, 'Model_User::email_is_available')
                ->rule(self::FIELD_EMAIL, 'max_length', [':value', 100]);
        }

        if(Arr::get($data, self::FIELD_PASSWORD) !== null){
            $validation
                ->rule(self::FIELD_PASSWORD, 'not_empty')
                ->rule(self::FIELD_PASSWORD, 'not_blank')
                ->rule(self::FIELD_PASSWORD, 'max_length', [':value', 100]);
        }

        if (!$validation->check()) {
            JsonRpc::client()->notify(Server::getSocketId(), 'popup-account.showErrors', [
                'errors' => $validation->errors(I18n::lang())
            ]);
            return false;
        }
        return true;
    }

    public static function updateAccountData($data) {
        if(self::validateAccountData($data) === false){
            return;
        }

        $user = Server::getUser();
        $user->import($data, [Model_User::COLUMN_PASSWORD]);
        $user->set(Model_User::COLUMN_WS_TOKEN, Token::random(8));
        $user->update();

        JsonRpc::client()->notify(Server::getSocketId(), 'popup-account.sendAvatarDataAjax', [
                'userId' => Server::getUserId(),
                'wsToken' => $user->get(Model_User::COLUMN_WS_TOKEN)
            ]
        );
        return true;
    }
}
