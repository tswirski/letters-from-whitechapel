<div class="popup" data-popup="account">
    <!-- FORM WIDGET -->
    <div class="avatarColumn">
        <div class="avatarBox">
            <input type="checkbox" name="checkbox-avatar-remove" id="checkbox-avatar-remove"/>
            <label for="checkbox-avatar-remove" class="avatarRemove">remove</label>
            <label>
                <img src="<%= userAvatarImg %>"/>
                <input type="file" id="input-avatar" name="input-avatar"/>
                <span class="labelText"><?= __('click to change picture'); ?></span>
            </label>
            <label for="input-avatar" class="error"></label>
        </div>
    </div>

    <div class="formColumn">
            <div class="inputBox">
                <label for="input-password" class="textInputPlaceholder"><?= __('click to change password'); ?></label>
                <input data-type="text" autocomplete="off" disabled class="hidden" id="input-password" type="password" name="password" autocomplete="off" placeholder="<?= __(' type new password'); ?>"/>
                <label  for="input-password" class="error"></label>
            </div>
    </div>
</div>
