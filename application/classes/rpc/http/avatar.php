<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Created by PhpStorm.
 * User: lama
 * Date: 2015-11-05
 * Time: 22:17
 */

class Rpc_Http_Avatar {
    protected function getRandomUserAvatarName($userId){
        return $userId . '.' . Token::random(20) . '.jpg';
    }

    /**
     * Upload obrazka u�ytkownika.
     * @param {int} $userId,
     * @param {string} $wsToken,
     * @param {boolean} $delete,
     * @returns {string}
     */
    public function updateAvatar($userId, $wsToken, $delete = false){
        $user = DAO::factory('User', $userId);

        if($user->notExists() || $user->isEmpty(Model_User::COLUMN_WS_TOKEN)
            || $user->notEqual(Model_User::COLUMN_WS_TOKEN, $wsToken)){
            return JsonRpc::client()->getNotificationObject('popup.alert', [
                'payload' => "Access denied, invalid authorisation token."
            ]);
        }

        $user->set(Model_User::COLUMN_WS_TOKEN, null);
        $user->update();

        /** @var {string} $currentAvatarFilename */
        $currentAvatarPath = $user->getAvatarPath();

        /** Delete current avatar */
        if($delete){
            if($currentAvatarPath !== null && $currentAvatarPath !== $user->getDefaultAvatarPath()){
                File::delete($currentAvatarPath);
            }
            $user->set(Model_User::COLUMN_AVATAR, null);
            $user->update();
            return JsonRpc::client()->getNotificationObject('avatar.announceAvatarUpdate');
        }

        /** Upload new avatar */
        try {
            /** Create new name for avatar file */
            $newAvatarFilename = $this->getRandomUserAvatarName($userId);
            $user->set(Model_User::COLUMN_AVATAR, $newAvatarFilename);

            /** Save (resize and compress) new avatar file */
            $image = Kohana_Image::factory($_FILES[0]['tmp_name']);
            $image->resize(160, 160);
            $image->save($user->getAvatarPath(), 70);

            /** Save new filename */
            $user->update();

            /** Delete old avatar file if available */
            if($currentAvatarPath !== null && $currentAvatarPath !== $user->getDefaultAvatarPath()){
                File::delete($currentAvatarPath);
            }
            return JsonRpc::client()->getNotificationObject('avatar.announceAvatarUpdate');

        } catch(Exception $e) {
            return JsonRpc::client()->getNotificationObject('popup.alert', [
                'payload' => "Image processing error"
            ]);
        }
    }
}