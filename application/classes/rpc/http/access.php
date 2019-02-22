<?php defined('SYSPATH') or die('No direct script access.');

class Rpc_Http_Access {

    const FIELD_PASSWORD = 'password';
    const FIELD_ACTION = 'action';
    const FIELD_NICKNAME = 'nickname';
    const ERROR_NOT_REGISTERED = "not registered";

    protected function getRandomPassword($min = 8, $max = 15) {
        return Token::random(rand($min, $max));
    }

    /**
     * Akcja logowania użytkownika.
     * Funkcja JSON RPC generująca odpowiedź w formacie JSON RPC.
     * @param  {string} $email
     * @param {string} $password
     * @return {array} Json Rpc Notification
     */
    public function signin($nickname, $password) {
        $data = [
            self::FIELD_NICKNAME => $nickname,
            self::FIELD_PASSWORD => $password
        ];

        $validation = Validation::factory($data)
                ->rule(self::FIELD_NICKNAME, 'not_empty')
                ->rule(self::FIELD_NICKNAME, 'max_length', [':value', 100])
                ->rule(self::FIELD_PASSWORD, 'not_empty')
                ->rule(self::FIELD_PASSWORD, 'max_length', [':value', 100]);

        if (!$validation->check()) {
            return JsonRpc::client()->getNotificationObject('page-welcome.showErrors', [
                        'errors' => $validation->errors(I18n::lang())]);
        }

        $user = DAO::find('User')
                ->where(Model_User::COLUMN_NICKNAME, '=', $data[self::FIELD_NICKNAME])
                ->getOne();

        if ($user === null) {
            return JsonRpc::client()->getNotificationObject('page-welcome.showErrors', [
                        'errors' => [self::FIELD_NICKNAME => self::ERROR_NOT_REGISTERED]]);
        }
        if (!Blowfish::verify($data[self::FIELD_PASSWORD], $user->get(Model_User::COLUMN_PASSWORD))) {
            return JsonRpc::client()->getNotificationObject('page-welcome.showErrors', [
                        'errors' => [self::FIELD_PASSWORD => 'Invalid password']]);
        }

        $user->set(Model_User::COLUMN_WS_TOKEN, $this->getRandomPassword(15, 32));
        $user->set(Model_User::COLUMN_WS_TIMESTAMP, Time::getCurrentTimestamp());
        $user->update();

        return JsonRpc::client()->getNotificationObject('server.login', [
                    'userId' => $user->get(Model_User::COLUMN_ID),
                    'wsToken' => $user->get(Model_User::COLUMN_WS_TOKEN)]);
    }


    /**
     * Rejestracja nowego użytkownika w serwisie
     * @param {string} $email
     * @param {string} $name
     * @param {bool} @randompassword
     * @param {string | null} $password
     */
    public function signup($nickname, $password) {
        $data = [
            self::FIELD_PASSWORD => $password,
            self::FIELD_NICKNAME => $nickname
        ];

        $validation = Validation::factory($data)

                ->rule(self::FIELD_NICKNAME, 'not_empty')
                ->rule(self::FIELD_NICKNAME, 'not_blank')
                ->rule(self::FIELD_NICKNAME, 'nickname')
                ->rule(self::FIELD_NICKNAME, 'Model_User::nickname_is_available')
                ->rule(self::FIELD_NICKNAME, 'max_length', [':value', 20])
				->rule(self::FIELD_PASSWORD, 'not_empty')
				->rule(self::FIELD_PASSWORD, 'not_blank')
				->rule(self::FIELD_PASSWORD, 'max_length', [':value', 100]);

        if (!$validation->check()) {
            return JsonRpc::client()->getNotificationObject('page-welcome.showErrors', [
                        'errors' => $validation->errors(I18n::lang())]);
        }

        $user = DAO::factory('User', NULL);
        $user->set(Model_User::COLUMN_PASSWORD, $password);
        $user->set(Model_User::COLUMN_NICKNAME, $nickname);
        $user->insert();

        return JsonRpc::client()
                        ->batch()
                        ->notify('page-welcome.clickSwitchToSigninForm')
                        ->notify('popup.alert', [
                            'payload' => "Account created. Log in."
                        ])->get();
    }
}
