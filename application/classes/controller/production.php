<?php

class Controller_Production extends Controller {
    /**
     * Strona głowna
     */
    public function action_index() {

        $nickname = Request::current()->parameter('nickname');
        $activation_code = Request::current()->parameter('code');
        $activated = false;

        if ($nickname) {
            $user = DAO::find('User')->where(Model_User::COLUMN_NICKNAME, '=', $nickname)->getOne();
            if ($user !== null && !$user->get(Model_User::COLUMN_ACTIVE) && $user->get(Model_User::COLUMN_ACTIVATION_CODE) === $activation_code) {
                $user->set(Model_User::COLUMN_ACTIVE, 1);
                $user->update();
                $activated = true;
            }
        }

        echo View::factory('layout', [
            "content" => View::factory('welcome', ['activated' => $activated]),
            "count" => Model_User::count()
        ]);
    }
}
