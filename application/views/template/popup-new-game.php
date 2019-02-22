<div class="popup" data-popup="new-game">
    <h2>New Game</h2>
    <form>
        <?=Form::radio('usingPassword', "no", true, ['id' => 'usingPasswordNo', 'data-using-password' => 'no']);?>
        <?=Form::radio('usingPassword', "yes", false, ['id' => 'usingPasswordYes', 'data-using-password' => 'yes']);?>

        <?=Form::label('usingPasswordNo', "Open For Everybody", ['class' => "selected"]);?>
        <?=Form::label('usingPasswordYes', "Password Protected");?>
        <?=Form::password('password', null, ["placeholder" => "Type password here", "autocomplete" => "off"]);?>

        <div class="randomJackHideout">
            <?=Form::checkbox('randomJackHideout', 'yes');?>
            <?=Form::label('randomJackHideout', "Random Jack Hideout");?>
        </div>
    </form>
</div>
