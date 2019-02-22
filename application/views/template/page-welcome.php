<div class="page" data-page="welcome">
    
    <!-- FACEBOOK ICON -->
    <a class="facebookIcon" href="https://www.facebook.com/fantasybyte/" target="_blank"></a>

    <!-- BLOOD PUDDLE GFX -->
    <div class="blood"></div>

    <!-- TITLE "LETTERS FROM WHITECHAPEL -->
    <div class="title">
        <span data-text="letters-from">Letters from</span>
        <span data-text="whitechapel">Whitechapel</span>
    </div>

    <!-- WEBSOCKET LACK WARNING MESSAGE -->
    <div class="websocketLackMessage">
        Web-Socket are not supported by this browser.
    </div>

    <!-- FORM WIDGET -->
    <form>
        <div class="buttonGroup">
            <label  class="active">
                <input type="radio" name="action" value="signin" checked="checked"/>
                <span><?= __('Sign In'); ?></span>
            </label>
            <label>
                <input type="radio" name="action" value="signup"/>
                <span><?= __('Sign Up'); ?></span>
            </label>
        </div>

        <div class="inputGroup">
            <div class="inputBox">
                <input id="input-nickname" type="text" name="nickname" autocomplete="off" placeholder="<?= __('Nickname'); ?>"/>
                <label for="input-nickname"  id="error-nickname" class="error"></label>
            </div>
			
            <div class="inputBox">
                <input id="input-password" type="password" name="password" autocomplete="off" placeholder="<?= __('Password'); ?>"/>
                <label  for="input-password" id="error-password" class="error"></label>
            </div>

        </div>

        <div class="submitButtonBox">
            <input type="submit" value="<?= __('proceed'); ?>"/>
        </div>
    </form>
</div>